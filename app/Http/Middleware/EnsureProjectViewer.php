<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectViewer
{
    /**
     * Handle an incoming request.
     *
     * Grants access to project owners and members whose role level
     * is at least the 'member' level (level >= 10).
     * This is essentially the same as EnsureProjectMember but
     * goes through the role system for consistency.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project');

        if (! $project instanceof Project) {
            $project = Project::find($project);
        }

        if (! $project) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        $user = $request->user();

        // Project owner always has access.
        if ($project->user_id === $user->id) {
            $request->route()->setParameter('project', $project);
            return $next($request);
        }

        // Check if the user is a member with at least 'member' level.
        $member = $project->members()
            ->where('user_id', $user->id)
            ->with('role')
            ->first();

        if (! $member || ! $member->role) {
            return response()->json(['message' => 'You are not a member of this project.'], 403);
        }

        $minLevel = Role::where('name', 'member')->value('level') ?? 10;

        if ($member->role->level < $minLevel) {
            return response()->json(['message' => 'You are not a member of this project.'], 403);
        }

        $request->route()->setParameter('project', $project);
        return $next($request);
    }
}
