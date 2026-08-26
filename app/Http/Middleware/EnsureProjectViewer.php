<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectViewer
{
    /**
     * Handle an incoming request.
     *
     * Grants read access to project owners and project members.
     * Role permissions govern capabilities inside the project, not whether
     * an existing membership can open the shared project.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project') ?? $request->route('projectId');

        if (! $project instanceof Project) {
            $project = Project::find($project);
        }

        if (! $project) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        $user = $request->user();

        // Project owner always has access.
        if ($project->user_id === $user->id) {
            if ($request->route('project') !== null) {
                $request->route()->setParameter('project', $project);
            }

            return $next($request);
        }

        $isMember = $project->members()
            ->where('user_id', $user->id)
            ->exists();

        if (! $isMember) {
            return response()->json(['message' => 'You are not a member of this project.'], 403);
        }

        if ($request->route('project') !== null) {
            $request->route()->setParameter('project', $project);
        }

        return $next($request);
    }
}
