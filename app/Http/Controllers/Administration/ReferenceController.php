<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\AdminReference;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReferenceController extends Controller
{
    public function publicIndex(Request $request): JsonResponse
    {
        return response()->json(AdminReference::query()
            ->select('id', 'title', 'description', 'category', 'file_url', 'updated_at')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = '%' . $request->string('q')->trim() . '%';
                $query->where(fn ($inner) => $inner->where('title', 'like', $q)->orWhere('description', 'like', $q));
            })->latest()->get());
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(AdminReference::with('creator:id,first_name,last_name')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = '%' . $request->string('q')->trim() . '%';
                $query->where(fn ($inner) => $inner->where('title', 'like', $q)->orWhere('description', 'like', $q));
            })->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $reference = AdminReference::create([...$data, 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        ActivityLogger::record($request->user(), 'reference_created', $reference);

        return response()->json($reference, 201);
    }

    public function update(Request $request, AdminReference $reference): JsonResponse
    {
        $data = $this->validated($request);
        if (($data['file_url'] ?? null) !== $reference->file_url) {
            $this->deleteManagedFile($reference->file_url);
        }
        $reference->update([...$data, 'updated_by' => $request->user()->id]);
        ActivityLogger::record($request->user(), 'reference_updated', $reference);

        return response()->json($reference->fresh());
    }

    public function destroy(Request $request, AdminReference $reference): JsonResponse
    {
        ActivityLogger::record($request->user(), 'reference_deleted', $reference);
        $this->deleteManagedFile($reference->file_url);
        $reference->delete();

        return response()->json(['message' => 'Reference deleted.']);
    }

    public function upload(Request $request): JsonResponse
    {
        $originalName = rawurldecode((string) $request->header('X-File-Name'));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

        if (! in_array($extension, $allowed, true)) {
            return response()->json(['message' => 'Upload a PDF, Word, Excel, or PowerPoint document.'], 422);
        }

        $contents = $request->getContent();
        if ($contents === '' || strlen($contents) > 60 * 1024 * 1024) {
            return response()->json(['message' => 'The document must be between 1 byte and 60 MB.'], 422);
        }

        $path = 'admin-references/' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->put($path, $contents);

        return response()->json([
            'file_url' => url(Storage::url($path)),
            'original_name' => $originalName,
        ], 201);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:100'],
            'file_url' => ['nullable', 'url', 'max:2048'],
        ]);
    }

    private function deleteManagedFile(?string $url): void
    {
        if (! $url) return;

        $path = (string) parse_url($url, PHP_URL_PATH);
        if (! str_starts_with($path, '/storage/admin-references/')) return;

        Storage::disk('public')->delete(Str::after($path, '/storage/'));
    }
}
