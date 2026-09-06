<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Role;
use App\Services\NotificationDeliveryService;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;

class ProjectMemberController extends Controller
{
    public function __construct(private readonly NotificationDeliveryService $notifications) {}

    public function index(Request $request, Project $project)
    {
        $members = $project->members()->with(['user', 'role', 'project'])->get();

        $members = $members->sortByDesc(
            fn ($m) => $m->user_id === $project->user_id
        );

        return MemberResource::collection($members->values());
    }

    public function store(Request $request, Project $project)
    {
        $request->validate(['user_ids' => ['required', 'array', 'min:1'], 'user_ids.*' => ['exists:users,id']]);
        $memberRoleId = Role::query()->where('name', 'member')->value('id');

        abort_unless($memberRoleId, 500, 'The default project role is not configured.');

        $added = [];
        $actor = $request->user();

        foreach ($request->user_ids as $userId) {
        $membership = ProjectMember::firstOrCreate(
            [
                'project_id' => $project->id,
                'user_id' => $userId,
            ],
            [
                'added_by' => $actor->id,
                'role_id' => $memberRoleId,
            ]
        );

        if ($membership->wasRecentlyCreated) {
            $added[] = $membership->load('user');
        }
    }

        if ($added) {
            $members = json_encode($this->index($request, $project)->resolve(request()), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

            Broadcast::on('projects.'.$project->id)
                ->as('members.updated')
                ->with([
                    'members' => $members,
                ])
                ->send();

            foreach ($added as $membership) {
                if ((int) $membership->user_id !== (int) $request->user()->id) {
                    $this->notifications->deliver(
                        $membership->user,
                        'Added to '.$project->name,
                        $request->user()->first_name.' '.$request->user()->last_name.' added you to the project.',
                        '/projects/'.$project->id,
                        true,
                        $request->user(),
                        $project->id,
                    );
                }
            }
        }

        return response()->json(MemberResource::collection($added), 201);
    }

    public function destroy(Request $request, Project $project, $userId)
    {
        if ($project->user_id === (int) $userId) {
            return response()->json(['message' => 'The owner cannot be removed.'], 422);
        }
        ProjectMember::where('project_id', $project->id)->where('user_id', $userId)->delete();

        return response()->noContent();
    }

    public function updateRole(Request $request, Project $project, int $userId)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        if ((int) $project->user_id === $userId) {
            return response()->json([
                'message' => 'The project owner role cannot be changed.',
            ], 422);
        }

        $membership = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $userId)
            ->first();

        if (! $membership) {
            return response()->json([
                'message' => 'Project member not found.',
            ], 404);
        }

        $previousRoleId = $membership->role_id;
        $newRoleId = (int) $validated['role_id'];

        DB::transaction(function () use ($request, $project, $membership, $previousRoleId, $newRoleId, $userId) {
            $membership->update(['role_id' => $newRoleId]);

            ActivityLogger::record($request->user(), 'project_member_role_changed', $membership, 'success', [
                'project_id' => $project->id,
                'target_user_id' => $userId,
                'from_role_id' => $previousRoleId,
                'to_role_id' => $newRoleId,
            ]);
        });

        return response()->json([
            'message' => 'Project member role updated.',
            'member' => new MemberResource($membership->fresh(['role', 'user', 'project'])),
        ]);
    }
}
