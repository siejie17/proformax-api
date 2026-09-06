<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BuildingType;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AnalyticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string'],
        ]);
        $type = $validated['type'] ?? null;

        $projectQuery = Project::query()->where('user_id', $request->user()->id);
        $this->applyTypeFilter($projectQuery, $type);

        $projects = $projectQuery
            ->with([
                'category:id,category',
                'buildingType:id,name',
            ])
            ->select(['id', 'name', 'building_type_id', 'category_id', 'budget', 'adjusted_cost', 'rating', 'assessment_status', 'created_at'])
            ->get();

        $actualCosts = DB::table('costs as costs')
            ->whereIn('costs.project_id', $projects->pluck('id'))
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('costs as children')
                    ->whereColumn('children.parent_id', 'costs.id');
            })
            ->select('costs.project_id', DB::raw('SUM(costs.actual_cost) as total'))
            ->groupBy('costs.project_id')
            ->pluck('total', 'project_id');

        $recentProjects = Project::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'buildingType:id,name',
            ])
            ->select([
                'id',
                'name',
                'building_type_id',
                'adjusted_cost',
                'rating',
                'target_certification',
                'changed_cert',
                'created_at',
            ])
            ->latest('created_at')
            ->take(3)
            ->get();

        $allProjects = Project::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'buildingType:id,name',
            ])
            ->select([
                'id',
                'name',
                'building_type_id',
                'adjusted_cost',
                'rating',
                'changed_cert',
                'target_certification',
                'created_at',
            ])
            ->latest('created_at')
            ->get();

        $actualCosts = DB::table('costs as costs')
            ->whereIn('costs.project_id', $allProjects->pluck('id'))
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('costs as children')
                    ->whereColumn('children.parent_id', 'costs.id');
            })
            ->select('costs.project_id', DB::raw('SUM(costs.actual_cost) as total'))
            ->groupBy('costs.project_id')
            ->pluck('total', 'project_id');

        $totalPredictedCost = $allProjects->sum(fn($project) => (float) $project->adjusted_cost);
        $totalActualCost = $allProjects->sum(fn($project) => (float) ($actualCosts[$project->id] ?? 0));
        $totalProjects = $allProjects->count();

        return response()->json([
            'types' => BuildingType::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                ])
                ->values()
                ->all(),
            'filters' => ['type' => $type],
            'metrics' => [
                'total_projects' => $totalProjects,
                'potential_cost_savings' => round($totalActualCost - $totalPredictedCost, 2),
                'average_predicted_gbi_score' => ($validProjects = $allProjects->filter(
                    fn($project) =>
                        !(
                            $project->target_certification === "Not Certified"
                            && $project->changed_cert === 0
                        )
                ))->count() > 0
                    ? round(
                        $validProjects->sum(fn($project) => (float) $project->rating)
                        / $validProjects->count(),
                        2
                    )
                    : 0,
                'certified_projects' => $allProjects->where('assessment_status', 'certified')->count(),
            ],
            'cost_trend' => $this->buildCostTrend($projects, $actualCosts),
            'recent_projects' => $recentProjects
                ->map(fn($project) => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'type' => $project->buildingType?->name,
                    'savings' => round(
                        (float) ($actualCosts[$project->id] ?? 0)
                            - (float) $project->adjusted_cost,
                        2
                    ),
                    'target_certification' => $project->target_certification,
                    'changed_cert' => $project->changed_cert,
                    'predicted_score' => $project->rating,
                    'created_at' => Carbon::parse($project->created_at)->toDateTimeString(),
                ])
                ->all(),
        ]);
    }

    private function applyTypeFilter($query, ?string $type): void
    {
        if ($type !== null) {
            $query->whereHas('buildingType', function ($typeQuery) use ($type) {
                $typeQuery->where('name', $type);
            });
        }
    }

    private function buildCostTrend($projects, $actualCosts): array
    {
        $start = now()->startOfMonth()->subMonths(5);
        $months = collect(range(0, 5))->map(fn($offset) => $start->copy()->addMonths($offset));
        $runningBudget = 0;
        $runningActual = 0;

        return $months->map(function (Carbon $month) use ($projects, $actualCosts, &$runningBudget, &$runningActual) {
            $monthProjects = $projects->filter(
                fn($project) =>
                $project->created_at !== null
                    && Carbon::parse($project->created_at)->isSameMonth($month)
            );
            $runningBudget += $monthProjects->sum(fn($project) => (float) $project->adjusted_cost);
            $runningActual += $monthProjects->sum(fn($project) => (float) ($actualCosts[$project->id] ?? 0));

            return [
                'month' => $month->format('Y-m'),
                'budgeted_cost' => round($runningBudget, 2),
                'projected_actual_cost' => round($runningActual, 2),
            ];
        })->all();
    }
}
