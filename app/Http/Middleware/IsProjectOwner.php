<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsProjectOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project');
        if (! $project instanceof Project) {
            $project = Project::find($project);
        }
        if (! $project || $project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Only the project owner can perform this action.'], 403);
        }
        $request->route()->setParameter('project', $project);
        return $next($request);
    }
}
