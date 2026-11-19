<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBranchController extends Controller
{
    public function index(): JsonResponse
    {
        $site = app('current_site');

        return response()->json([
            'success' => true,
            'data' => Branch::where('site_id', $site->id)->orderBy('order')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $site = app('current_site');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:40'],
            'is_primary' => ['boolean'],
            'order' => ['integer'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $validated['images'] = $this->storeImages($request, $site->slug);
        $validated['site_id'] = $site->id;

        $branch = Branch::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully',
            'data' => $branch,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $site = app('current_site');
        $branch = Branch::where('site_id', $site->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'address' => ['sometimes', 'required', 'string'],
            'phone' => ['nullable', 'string', 'max:40'],
            'is_primary' => ['boolean'],
            'order' => ['integer'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if ($request->hasFile('images')) {
            $validated['images'] = $this->storeImages($request, $site->slug);
        } else {
            unset($validated['images']);
        }

        $branch->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully',
            'data' => $branch->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $site = app('current_site');
        $branch = Branch::where('site_id', $site->id)->findOrFail($id);
        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Branch deleted successfully',
        ]);
    }

    private function storeImages(Request $request, string $slug): array
    {
        return collect($request->file('images') ?? [])
            ->map(fn ($file) => $file->store("branches/{$slug}", 'public'))
            ->all();
    }
}
