<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = ActivityLog::with('user:id,first_name,last_name,email')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = '%' . $request->string('q')->trim() . '%';
                $query->where(fn ($inner) => $inner->where('action', 'like', $q)->orWhere('target_label', 'like', $q));
            })
            ->latest()
            ->paginate(25);

        return response()->json($logs);
    }
}
