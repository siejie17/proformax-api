<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\AdminReference;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $rawStatusCounts = Project::query()
            ->selectRaw('assessment_status, count(*) as total')
            ->groupBy('assessment_status')
            ->pluck('total', 'assessment_status');
        $statusCounts = collect([
            'awaiting_verification' => (int) ($rawStatusCounts['submitted'] ?? 0) + (int) ($rawStatusCounts['pending_verification'] ?? 0),
            'changes_requested' => (int) ($rawStatusCounts['requires_changes'] ?? 0),
            'verified' => (int) ($rawStatusCounts['verified'] ?? 0),
            'certified' => (int) ($rawStatusCounts['certified'] ?? 0),
        ]);

        $certificationCounts = Project::query()
            ->selectRaw("COALESCE(NULLIF(target_certification, ''), 'Unclassified') as certification_level, count(*) as total")
            ->groupBy('certification_level')
            ->orderByDesc('total')
            ->pluck('total', 'certification_level');

        return response()->json([
            'metrics' => [
                'users' => User::where('system_role', 'user')->count(),
                'assessments' => Project::count(),
                'pending' => Project::whereIn('assessment_status', ['submitted', 'pending_verification'])->count(),
                'verified' => Project::where('assessment_status', 'verified')->count(),
                'facilitators' => User::where('system_role', 'facilitator_admin')->count(),
            ],
            'status_distribution' => $statusCounts,
            'certification_distribution' => $certificationCounts,
            'recent_assessments' => Project::with('owner:id,first_name,last_name,email')
                ->latest('created_at')->limit(6)->get(),
            'recent_references' => AdminReference::latest()->limit(5)->get(),
        ]);
    }
}
