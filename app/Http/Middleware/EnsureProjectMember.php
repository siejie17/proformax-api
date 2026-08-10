<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project');

        // Route model binding may not have run yet (middleware order), so the
        // value is the id string. Resolve the model ourselves if needed.
        if (! $project instanceof Project) {
            $project = Project::find($project);
        }

        if (! $project
            || ($project->user_id !== $request->user()->id
                && ! $project->members()->where('user_id', $request->user()->id)->exists())) {
            return response()->json(['message' => 'You are not a member of this project.'], 403);
        }

        // Re-bind so downstream code sees the model (controllers expect `Project $project`).
        $request->route()->setParameter('project', $project);

        return $next($request);
    }
}
