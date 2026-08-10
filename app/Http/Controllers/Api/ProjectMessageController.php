<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Project;
use App\Models\ProjectMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class ProjectMessageController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $before = $request->string('before')->toString();
        $limit  = min((int) $request->input('limit', 25), 100);

        $query = ProjectMessage::query()
            ->where('project_id', $project->id)
            ->with(['attachment', 'reactions', 'user']);

        if ($before) $query->where('created_at', '<', $before);

        $messages = $query->orderBy('created_at', 'desc')->limit($limit + 1)->get();
        $hasMore  = $messages->count() > $limit;
        $messages = $messages->take($limit)->sortBy('created_at')->values();

        return response()->json([
            'messages' => MessageResource::collection($messages),
            'hasMore'  => $hasMore,
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'message'      => ['required_without:attachment_id', 'string', 'max:2000'],
            'attachment_id'=> ['nullable', 'exists:attachments,id'],
            'reply_to_id'  => ['nullable', 'exists:project_messages,id'],
        ]);

        $message = ProjectMessage::create([
            'project_id'    => $project->id,
            'user_id'       => $request->user()->id,
            'body'          => $data['message'] ?? '',
            'attachment_id' => $data['attachment_id'] ?? null,
            'reply_to_id'   => $data['reply_to_id'] ?? null,
            'is_system'     => false,
        ])->load(['attachment', 'reactions', 'user', 'replyTo']);

        Broadcast::on('projects.'.$project->id)->as('message.created')->with([
            'message' => (new MessageResource($message))->resolve(request()),
        ])->send();

        return (new MessageResource($message))->response()->setStatusCode(201);
    }
}
