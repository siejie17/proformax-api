<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    private const PROFILE_PICTURE_MAX_BYTES = 2 * 1024 * 1024;

    private const PROFILE_PICTURE_MAX_DIMENSION = 4096;

    private const PROFILE_PICTURE_MAX_PIXELS = 16_000_000;

    private const PROFILE_PICTURE_MAX_DATA_URL_LENGTH = 2_800_000;

    private const PROFILE_PICTURE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function roles(Request $request): JsonResponse
    {
        $roles = Role::query()
            ->orderBy('level')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'roles' => $roles,
        ]);
    }

    public function getUserById(Request $request, int $userId): JsonResponse
    {
        abort_unless((int) $request->user()->id === $userId, 403);

        return response()->json([
            'success' => true,
            'user' => new UserResource($request->user()),
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

    public function updateImage(Request $request): JsonResponse
    {
        $request->validate([
            'profile_pic' => [
                'required',
                'string',
                'max:'.self::PROFILE_PICTURE_MAX_DATA_URL_LENGTH,
            ],
        ], [
            'profile_pic.max' => 'The profile picture must not exceed 2 MB.',
        ]);

        $profilePicture = $this->validateProfilePicture($request->string('profile_pic')->toString());
        $user = $request->user();

        $user->update([
            'profile_pic' => $profilePicture,
        ]);

        return response()->json([
            'message' => 'Photo updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    private function validateProfilePicture(string $dataUrl): string
    {
        if (! preg_match('/\Adata:(image\/(?:jpeg|png|webp));base64,([A-Za-z0-9+\/=]+)\z/i', $dataUrl, $matches)) {
            $this->invalidProfilePicture('The profile picture must be a base64-encoded JPEG, PNG, or WebP image.');
        }

        $declaredMime = strtolower($matches[1]);
        $imageBytes = base64_decode($matches[2], true);

        if ($imageBytes === false) {
            $this->invalidProfilePicture('The profile picture contains invalid base64 data.');
        }

        if (strlen($imageBytes) > self::PROFILE_PICTURE_MAX_BYTES) {
            $this->invalidProfilePicture('The profile picture must not exceed 2 MB.');
        }

        $imageInfo = @getimagesizefromstring($imageBytes);
        $detectedMime = is_array($imageInfo) ? strtolower((string) ($imageInfo['mime'] ?? '')) : '';

        if (! in_array($detectedMime, self::PROFILE_PICTURE_MIME_TYPES, true) || $detectedMime !== $declaredMime) {
            $this->invalidProfilePicture('The profile picture content does not match an allowed image type.');
        }

        [$width, $height] = $imageInfo;
        if ($width < 1 || $height < 1
            || $width > self::PROFILE_PICTURE_MAX_DIMENSION
            || $height > self::PROFILE_PICTURE_MAX_DIMENSION
            || $width * $height > self::PROFILE_PICTURE_MAX_PIXELS) {
            $this->invalidProfilePicture('The profile picture dimensions must not exceed 4096 by 4096 pixels or 16 megapixels.');
        }

        return 'data:'.$detectedMime.';base64,'.base64_encode($imageBytes);
    }

    private function invalidProfilePicture(string $message): never
    {
        throw ValidationException::withMessages([
            'profile_pic' => [$message],
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
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (! Hash::check($request->string('current_password')->toString(), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The current password is incorrect.',
                'errors' => [
                    'current_password' => ['The current password is incorrect.'],
                ],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->string('new_password')->toString())
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
    public function updatePreferences(Request $request, int $userId): JsonResponse
    {
        $user = $request->user();

        if ($user->getKey() !== $userId) {
            return response()->json([
                'message' => 'You may only update your own preferences.',
                'status' => false,
            ], 403);
        }

        $validatedData = $request->validate([
            'email_notifications' => 'sometimes|boolean',
            'push_notifications' => 'sometimes|boolean',
        ]);

        $user->update($validatedData);

        return response()->json([
            'message' => 'Preferences updated successfully',
            'status' => true,
            'user' => $user->fresh(),
            'preferences' => [
                'email_notifications' => $user->email_notifications,
                'push_notifications' => $user->push_notifications,
            ]
        ], 200);
    }

    /**
     * Fetch user preferences
     * GET /users/{userId}/preferences
     */
    public function getPreferences(Request $request, int $userId): JsonResponse
    {
        $user = $request->user();

        if ($user->getKey() !== $userId) {
            return response()->json([
                'message' => 'You may only view your own preferences.',
                'status' => false,
            ], 403);
        }

        return response()->json([
            'status' => true,
            'preferences' => [
                'email_notifications' => $user->email_notifications ?? false,
                'push_notifications' => $user->push_notifications ?? false,
            ]
        ], 200);
    }
}
