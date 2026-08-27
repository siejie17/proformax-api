<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\MessageReaction;
use App\Models\ProjectMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class MessageReactionController extends Controller
{
    public function toggle(Request $request, ProjectMessage $message): JsonResponse
    {
        $data = $request->validate([
            'emoji' => ['required', 'string', 'in:👍,❤️,🎉,👀'],
        ]);
        $user = $request->user();

        $existing = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', $data['emoji'])
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            MessageReaction::create([
                'project_id' => $message->project_id,
                'message_id' => $message->id,
                'user_id' => $user->id,
                'emoji' => $data['emoji'],
            ]);
        }

        $message->touch();
        $message->load('reactions');
        $reactionsShape = (new MessageResource($message))->reactionsShape();

        Broadcast::on('projects.'.$message->project_id)
            ->as('reactions.updated')
            ->with([
                'messageId' => (string) $message->id,
                'reactions' => $reactionsShape,
            ])->send();

        return response()->json([
            'message_id' => (string) $message->id,
            'reactions' => $reactionsShape,
        ]);
    }
}
