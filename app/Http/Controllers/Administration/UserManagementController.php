<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->select('id', 'first_name', 'last_name', 'email', 'email_verified_at', 'system_role', 'created_at')
            ->when($request->filled('role'), fn ($query) => $query->where('system_role', $request->string('role')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = '%' . $request->string('q')->trim() . '%';
                $query->where(fn ($inner) => $inner
                    ->where('first_name', 'like', $q)
                    ->orWhere('last_name', 'like', $q)
                    ->orWhere('email', 'like', $q));
            })
            ->orderByRaw("CASE system_role
                WHEN 'super_admin' THEN 1
                WHEN 'admin' THEN 2
                WHEN 'facilitator_admin' THEN 3
                WHEN 'user' THEN 4
                ELSE 5
            END")
            ->latest('created_at')
            ->latest('id');

        if ($request->boolean('include_assignments')) {
            $query->with(['facilitatorAssignments' => fn ($assignments) => $assignments
                ->where('status', 'active')
                ->latest('appointed_at')
                ->with('project.owner:id,first_name,last_name,email')]);
        }

        $users = $query->paginate(20);

        return response()->json($users);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();

        if ($actor->is($user)) {
            return response()->json(['message' => 'You cannot change your own system role.'], 422);
        }

        $isAdminModule = $request->is('api/administration/admin/*');
        $allowed = $actor->system_role === 'super_admin' && ! $isAdminModule
            ? ['user', 'facilitator_admin', 'admin']
            : ['user', 'facilitator_admin'];

        $validated = $request->validate([
            'system_role' => ['required', Rule::in($allowed)],
        ]);

        if ($user->system_role === 'super_admin') {
            return response()->json(['message' => 'SuperAdmin accounts cannot be changed here.'], 403);
        }

        if (($actor->system_role === 'admin' || $isAdminModule) && ! in_array($user->system_role, ['user', 'facilitator_admin'], true)) {
            return response()->json(['message' => 'Admins may only manage Users and Facilitator Admins.'], 403);
        }

        if ($actor->system_role === 'super_admin' && ! $isAdminModule && ! in_array($user->system_role, ['user', 'facilitator_admin', 'admin'], true)) {
            return response()->json(['message' => 'SuperAdmins may manage User, Facilitator Admin, and Admin accounts from this module.'], 422);
        }

        $previousRole = $user->system_role;
        $newRole = $validated['system_role'];

        DB::transaction(function () use ($actor, $user, $previousRole, $newRole) {
            $user->update(['system_role' => $newRole]);

            $revokedAssignments = 0;
            if ($previousRole === 'facilitator_admin' && $newRole !== 'facilitator_admin') {
                $revokedAssignments = DB::table('facilitator_assignments')
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'revoked',
                        'revoked_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            ActivityLogger::record($actor, 'system_role_changed', $user, 'success', [
                'from' => $previousRole,
                'to' => $newRole,
                'revoked_facilitator_assignments' => $revokedAssignments,
            ]);
        });

        return response()->json(['message' => 'System role updated.', 'user' => $user->fresh()]);
    }

    public function updateAccount(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();

        if ($user->system_role === 'super_admin') {
            return response()->json(['message' => 'SuperAdmin accounts cannot be changed here.'], 403);
        }

        if (! in_array($user->system_role, ['user', 'facilitator_admin', 'admin'], true)) {
            return response()->json(['message' => 'This account cannot be managed from this module.'], 403);
        }

        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $previous = $user->only(['first_name', 'last_name', 'email']);
        $emailChanged = $user->email !== $validated['email'];

        DB::transaction(function () use ($actor, $user, $validated, $previous, $emailChanged) {
            $user->forceFill([
                ...$validated,
                'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
            ])->save();

            ActivityLogger::record($actor, 'account_details_updated', $user, 'success', [
                'before' => $previous,
                'after' => $validated,
                'email_verification_reset' => $emailChanged,
            ]);
        });

        return response()->json([
            'message' => 'Account details updated.',
            'user' => $user->fresh()->only(['id', 'first_name', 'last_name', 'email', 'email_verified_at', 'system_role', 'created_at']),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();

        if ($actor->is($user)) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        if ($user->system_role === 'super_admin') {
            return response()->json(['message' => 'SuperAdmin accounts cannot be deleted here.'], 403);
        }

        $isAdminModule = $request->is('api/administration/admin/*');
        $manageableRoles = $actor->system_role === 'super_admin' && ! $isAdminModule
            ? ['user', 'facilitator_admin', 'admin']
            : ['user', 'facilitator_admin'];

        if (! in_array($user->system_role, $manageableRoles, true)) {
            return response()->json(['message' => 'You are not allowed to delete this account.'], 403);
        }

        DB::transaction(function () use ($actor, $user) {
            ActivityLogger::record($actor, 'account_deleted', $user, 'success', [
                'deleted_role' => $user->system_role,
                'deleted_email' => $user->email,
            ]);

            $messageIds = DB::table('project_messages')->where('user_id', $user->id)->pluck('id');
            if ($messageIds->isNotEmpty()) {
                DB::table('project_members')->whereIn('last_read_message_id', $messageIds)->update(['last_read_message_id' => null]);
            }

            DB::table('project_members')->where('added_by', $user->id)->update(['added_by' => $actor->id]);
            DB::table('project_messages')->where('user_id', $user->id)->delete();
            DB::table('attachments')->where('user_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->tokens()->delete();
            $user->delete();
        });

        return response()->json(['message' => 'Account deleted.']);
    }
}
