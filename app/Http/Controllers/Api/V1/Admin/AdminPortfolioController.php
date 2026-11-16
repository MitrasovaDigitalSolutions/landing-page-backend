<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminPortfolioController extends Controller
{
    public function index(): JsonResponse
    {
        $site = app('current_site');
        $portfolios = Portfolio::where('site_id', $site->id)->with('serviceType')->orderBy('order')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $portfolios,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $site = app('current_site');

        $validated = $request->validate([
            'service_type_id' => ['nullable', 'exists:service_types,id'],
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180'],
            'client_name' => ['nullable', 'string', 'max:100'],
            'completion_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'is_featured' => ['boolean'],
            'order' => ['integer'],
            'thumbnail' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);

        $thumbnailPath = $request->file('thumbnail')->store("portfolios/{$site->slug}", 'public');

        $galleryPaths = [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $galleryPaths[] = $file->store("portfolios/gallery/{$site->slug}", 'public');
            }
        }

        $portfolio = Portfolio::create([
            'site_id' => $site->id,
            'service_type_id' => $validated['service_type_id'] ?? null,
            'title' => $validated['title'],
            'slug' => $slug,
            'client_name' => $validated['client_name'] ?? null,
            'completion_date' => $validated['completion_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'thumbnail_path' => $thumbnailPath,
            'gallery_images' => !empty($galleryPaths) ? $galleryPaths : null,
            'is_featured' => $validated['is_featured'] ?? false,
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Portfolio created successfully',
            'data' => $portfolio->load('serviceType'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $site = app('current_site');
        $portfolio = Portfolio::where('site_id', $site->id)->with('serviceType')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $portfolio,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $site = app('current_site');
        $portfolio = Portfolio::where('site_id', $site->id)->findOrFail($id);

        $validated = $request->validate([
            'service_type_id' => ['nullable', 'exists:service_types,id'],
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:180'],
            'client_name' => ['nullable', 'string', 'max:100'],
            'completion_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'is_featured' => ['boolean'],
            'order' => ['integer'],
            'thumbnail' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if (isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail_path'] = $request->file('thumbnail')->store("portfolios/{$site->slug}", 'public');
        }
        unset($validated['thumbnail']);

        $portfolio->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Portfolio updated successfully',
            'data' => $portfolio->fresh(['serviceType']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $site = app('current_site');
        $portfolio = Portfolio::where('site_id', $site->id)->findOrFail($id);
        $portfolio->delete();

        return response()->json([
            'success' => true,
            'message' => 'Portfolio deleted successfully',
        ]);
    }
}
