<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\ProjectMessage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $project = $this->resolveProject($request);

        if (! $project) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        $user = $request->user();
        if ((int) $project->user_id === (int) $user->id) {
            $this->bindProject($request, $project);

            return $next($request);
        }

        $membership = $project->members()
            ->where('user_id', $user->id)
            ->with('role')
            ->first();

        if (! $membership || ! $membership->getRelation('role')?->hasPermission($permission)) {
            return response()->json([
                'message' => 'You do not have permission to perform this project action.',
            ], 403);
        }

        $this->bindProject($request, $project);

        return $next($request);
    }

    private function resolveProject(Request $request): ?Project
    {
        $project = $request->route('project') ?? $request->route('projectId');

        if ($project instanceof Project) {
            return $project;
        }

        if ($project !== null) {
            return Project::find($project);
        }

        $message = $request->route('message');
        if (! $message instanceof ProjectMessage) {
            $message = ProjectMessage::find($message);
        }

        return $message?->project;
    }

    private function bindProject(Request $request, Project $project): void
    {
        if ($request->route('project') !== null) {
            $request->route()->setParameter('project', $project);
        }
    }
}
