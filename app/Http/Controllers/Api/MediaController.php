<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function show(Request $request, string $filename): BinaryFileResponse
    {
        $attachment = $this->authorizeAttachment($request, $filename);

        $path = Storage::disk('public')->path($attachment->path);

        return response()->file(
            $path,
            [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"',
            ]
        );
    }

    public function download(Request $request, string $filename): BinaryFileResponse
    {
        $attachment = $this->authorizeAttachment($request, $filename);

        $path = Storage::disk('public')->path($attachment->path);

        return response()->download(
            $path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            ]
        );
    }

    private function authorizeAttachment(
        Request $request,
        string $filename
    ): Attachment {
        $attachment = Attachment::where('filename', $filename)->firstOrFail();

        $user = $request->user();
        $project = $attachment->project;

        $isOwner = $project->user_id === $user->id;
        $isMember = $project->members()
            ->where('user_id', $user->id)
            ->exists();

        if (! $isOwner && ! $isMember) {
            abort(403, 'You are not a member of this project.');
        }

        if (! Storage::disk('public')->exists($attachment->path)) {
            abort(404, 'File not found.');
        }

        return $attachment;
    }
}