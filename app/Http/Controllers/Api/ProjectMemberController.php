<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class ProjectMemberController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $members = $project->members()->with('user')->get();

        $members = $members->sortByDesc(
            fn($m) => $m->user_id === $project->user_id
        );

        return MemberResource::collection($members->values());
    }

    public function store(Request $request, Project $project)
    {
        $request->validate(['user_ids' => ['required', 'array', 'min:1'], 'user_ids.*' => ['exists:users,id']]);

        $added = [];
        foreach ($request->user_ids as $userId) {
            $didCreate = ProjectMember::firstOrCreate([
                'project_id' => $project->id,
                'user_id'    => $userId,
            ], ['added_by' => $request->user()->id]);
            if ($didCreate->wasRecentlyCreated) $added[] = $didCreate->load('user');
        }

        if ($added) {
            $members = json_encode($this->index($request, $project)->resolve(request()), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

            Broadcast::on('projects.' . $project->id)
                ->as('members.updated')
                ->with([
                    'members' => $members,
                ])
                ->send();
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
}
