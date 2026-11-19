<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBusinessUnitController extends Controller
{
    public function index(): JsonResponse
    {
        $site = app('current_site');

        return response()->json([
            'success' => true,
            'data' => BusinessUnit::where('site_id', $site->id)->orderBy('order')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $site = app('current_site');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
            'url' => ['nullable', 'url', 'max:255'],
            'order' => ['integer'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store("business-units/{$site->slug}", 'public');
        }
        unset($validated['image']);

        $images = collect($request->file('images') ?? [])
            ->map(fn ($file) => $file->store("business-units/{$site->slug}", 'public'))
            ->all();

        if ($images) {
            $validated['images'] = $images;
            $validated['image_path'] ??= $images[0];
        } else {
            unset($validated['images']);
        }

        $validated['site_id'] = $site->id;

        $unit = BusinessUnit::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Business unit created successfully',
            'data' => $unit,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $site = app('current_site');
        $unit = BusinessUnit::where('site_id', $site->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
            'url' => ['nullable', 'url', 'max:255'],
            'order' => ['integer'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store("business-units/{$site->slug}", 'public');
        }
        unset($validated['image']);

        if ($request->hasFile('images')) {
            $images = collect($request->file('images'))
                ->map(fn ($file) => $file->store("business-units/{$site->slug}", 'public'))
                ->all();
            $validated['images'] = $images;
            $validated['image_path'] ??= $images[0];
        } else {
            unset($validated['images']);
        }

        $unit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Business unit updated successfully',
            'data' => $unit->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $site = app('current_site');
        $unit = BusinessUnit::where('site_id', $site->id)->findOrFail($id);
        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Business unit deleted successfully',
        ]);
    }
}
