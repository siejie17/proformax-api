<?php

namespace App\Http\Controllers\Api;

use App\Models\MessageReaction;
use App\Models\ProjectMessage;
use App\Http\Controllers\Controller; // Ensure this is imported if extending
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Resources\MessageResource;

class MessageReactionController extends Controller
{
    public function toggle(Request $request, ProjectMessage $message): JsonResponse
    {
        // membership is enforced upstream by the project.member middleware
        $data = $request->validate([
            'emoji' => ['required', 'string', 'in:👍,❤️,🎉,👀']
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
                'user_id'    => $user->id,
                'emoji'      => $data['emoji'],
            ]);
        }

        // Freshly load relationship counts/states after mutation
        $message->load('reactions');
        
        $reactionsShape = (new MessageResource($message))->reactionsShape();

        // Broadcast the fresh reactions to other users
        Broadcast::on('projects.'.$message->project_id)
            ->as('reactions.updated')
            ->with([
                'messageId' => (string) $message->id,
                'reactions' => $reactionsShape,
            ])->send();

        // Return the same payload to the user who clicked the reaction
        return response()->json([
            'message_id' => (string) $message->id, 
            'reactions'  => $reactionsShape
        ]);
    }
}