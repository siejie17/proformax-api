<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function getUserById($userId): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }

    public function search(Request $request)
    {
        $q = $request->string('q')->trim()->toString();

        $exclude = collect($request->input('exclude_ids', []))
            ->map(fn($v) => (int) $v)
            ->all();

        $users = User::query()
            ->select('id', 'first_name', 'last_name', 'email', 'role_id', 'profile_pic')
            ->where('id', '!=', $request->user()->id)
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($w) use ($q) {
                    $w->where('first_name', 'like', '%' . $q . '%')
                        ->orWhere('last_name', 'like', '%' . $q . '%')
                        ->orWhere('email', 'like', '%' . $q . '%')
                        ->orWhereRaw(
                            "CONCAT(first_name, ' ', last_name) LIKE ?",
                            ['%' . $q . '%']
                        );
                });
            })
            ->when($exclude, fn($qb) => $qb->whereNotIn('id', $exclude))
            ->limit(12)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return UserResource::collection($users);
    }

    public function updateImage(Request $request)
    {
        $request->validate([
            'profile_pic' => 'required|string',
        ]);

        $base64String = $request->input('profile_pic');
        $user = $request->user(); // Get authenticated user

        $user->update([
            'profile_pic' => $base64String,
        ]);

        return response()->json([
            'message' => 'Photo updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        // Define validation rules for each field
        $rules = [];
        $messages = [];

        // Only validate fields that are actually being updated
        if ($request->has('first_name')) {
            $rules['first_name'] = 'required|string|max:255';
            $messages['first_name.required'] = 'First name is required.';
        }

        if ($request->has('last_name')) {
            $rules['last_name'] = 'required|string|max:255';
            $messages['last_name.required'] = 'Last name is required.';
        }

        // Validate the request
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ]);
        }

        // Update only the fields that were sent in the request
        $updateData = $request->only(array_keys($rules));

        // Update the user
        $user->update($updateData);

        // Refresh the user instance
        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => $user
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ]);
        }

        $user = $request->user();
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.'
        ]);
    }

    /**
     * Update user preferences
     * PATCH /users/{userId}/preferences
     */
    public function updatePreferences(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);

            // Define allowed preference fields
            $allowedPreferences = [
                'email_notifications',
                'push_notifications'
            ];

            $validatedData = $request->validate([
                'email_notifications' => 'sometimes|boolean',
                'push_notifications' => 'sometimes|boolean',
            ]);

            // Update preferences (either create or update)
            foreach ($validatedData as $key => $value) {
                $user->update([$key => $value]);
            }

            return response()->json([
                'message' => 'Preferences updated successfully',
                'status' => true,
                'user' => $user,
                'preferences' => [
                    'email_notifications' => $user->email_notifications,
                    'push_notifications' => $user->push_notifications,
                ]
            ], 200);
        } catch (\Exception $error) {
            return response()->json([
                'message' => 'Error updating preferences',
                'status' => false,
                'error' => $error->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch user preferences
     * GET /users/{userId}/preferences
     */
    public function getPreferences($userId)
    {
        try {
            $user = User::findOrFail($userId);

            return response()->json([
                'status' => true,
                'preferences' => [
                    'email_notifications' => $user->email_notifications ?? false,
                    'push_notifications' => $user->push_notifications ?? false,
                ]
            ], 200);
        } catch (\Exception $error) {
            return response()->json([
                'message' => 'Error fetching preferences',
                'status' => false,
                'error' => $error->getMessage()
            ], 500);
        }
    }
}
