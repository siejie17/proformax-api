<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Recommendation;
use App\Models\RecommendationSection;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function publicIndex(): JsonResponse
    {
        return response()->json([
            'sections' => RecommendationSection::query()
                ->select('id', 'certification_level', 'title')
                ->orderBy('certification_level')
                ->get(),
            'recommendations' => Recommendation::query()
                ->select('id', 'certification_level', 'title', 'content', 'is_active')
                ->where('is_active', true)
                ->orderBy('certification_level')
                ->latest('updated_at')
                ->get(),
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'sections' => RecommendationSection::orderBy('certification_level')->get(),
            'recommendations' => Recommendation::orderBy('certification_level')->latest('updated_at')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $item = Recommendation::create([...$data, 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        ActivityLogger::record($request->user(), 'recommendation_created', $item);

        return response()->json($item, 201);
    }

    public function update(Request $request, Recommendation $recommendation): JsonResponse
    {
        $recommendation->update([...$this->validated($request, $recommendation), 'updated_by' => $request->user()->id]);
        ActivityLogger::record($request->user(), 'recommendation_updated', $recommendation);

        return response()->json($recommendation->fresh());
    }

    public function destroy(Request $request, Recommendation $recommendation): JsonResponse
    {
        ActivityLogger::record($request->user(), 'recommendation_deleted', $recommendation);
        $recommendation->delete();

        return response()->json(['message' => 'Recommendation deleted.']);
    }

    public function updateSection(Request $request): JsonResponse
    {
        $data = $request->validate([
            'certification_level' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:255'],
        ]);

        $section = RecommendationSection::updateOrCreate(
            ['certification_level' => $data['certification_level']],
            ['title' => $data['title'], 'updated_by' => $request->user()->id],
        );
        ActivityLogger::record($request->user(), 'recommendation_section_updated', $section);

        return response()->json($section);
    }

    private function validated(Request $request, ?Recommendation $recommendation = null): array
    {
        $data = $request->validate([
            'certification_level' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['present', 'nullable', 'string', 'max:10000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $data['content'] ??= '';

        return $data;
    }
}
