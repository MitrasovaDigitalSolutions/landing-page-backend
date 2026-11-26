<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminServiceController extends Controller
{
    public function indexTypes(): JsonResponse
    {
        $site = app('current_site');
        $types = ServiceType::where('site_id', $site->id)->withCount('services')->get();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    public function storeType(Request $request): JsonResponse
    {
        $site = app('current_site');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'order' => ['integer'],
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);

        $type = ServiceType::create([
            'site_id' => $site->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Service type created successfully',
            'data' => $type,
        ], 201);
    }

    public function updateType(Request $request, int $id): JsonResponse
    {
        $site = app('current_site');
        $type = ServiceType::where('site_id', $site->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'order' => ['integer'],
        ]);

        if (isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        $type->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Service type updated successfully',
            'data' => $type,
        ]);
    }

    public function destroyType(int $id): JsonResponse
    {
        $site = app('current_site');
        $type = ServiceType::where('site_id', $site->id)->findOrFail($id);

        if ($type->services()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete service type that still has associated services',
            ], 422);
        }

        $type->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service type deleted successfully',
        ]);
    }

    // --- Services ---

    public function index(Request $request): JsonResponse
    {
        $site = app('current_site');
        $query = Service::where('site_id', $site->id)->with('serviceType');

        if ($request->filled('service_type_id')) {
            $query->where('service_type_id', $request->query('service_type_id'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('order')->paginate(20),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $site = app('current_site');

        $validated = $request->validate([
            'service_type_id' => ['required', 'exists:service_types,id'],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180'],
            'short_desc' => ['required', 'string', 'max:300'],
            'body' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
            'price' => ['nullable', 'integer', 'min:0'],
            'price_note' => ['nullable', 'string', 'max:30'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'order' => ['integer'],
            'thumbnail' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'icon' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store("services/{$site->slug}", 'public');
        }

        $iconPath = null;
        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store("services/icons/{$site->slug}", 'public');
        }

        $images = collect($request->file('images') ?? [])
            ->map(fn ($file) => $file->store("services/{$site->slug}", 'public'))
            ->all();

        $service = Service::create([
            'site_id' => $site->id,
            'service_type_id' => $validated['service_type_id'],
            'name' => $validated['name'],
            'slug' => $slug,
            'short_desc' => $validated['short_desc'],
            'body' => $validated['body'] ?? null,
            'features' => $validated['features'] ?? null,
            'price' => $validated['price'] ?? null,
            'price_note' => $validated['price_note'] ?? null,
            'thumbnail_path' => $thumbnailPath,
            'icon_path' => $iconPath,
            'images' => $images ?: null,
            'is_featured' => $validated['is_featured'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Service created successfully',
            'data' => $service->load('serviceType'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $site = app('current_site');
        $service = Service::where('site_id', $site->id)->with('serviceType')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $service,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $site = app('current_site');
        $service = Service::where('site_id', $site->id)->findOrFail($id);

        $validated = $request->validate([
            'service_type_id' => ['sometimes', 'required', 'exists:service_types,id'],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:180'],
            'short_desc' => ['sometimes', 'required', 'string', 'max:300'],
            'body' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
            'price' => ['nullable', 'integer', 'min:0'],
            'price_note' => ['nullable', 'string', 'max:30'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'order' => ['integer'],
            'thumbnail' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'icon' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if (isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail_path'] = $request->file('thumbnail')->store("services/{$site->slug}", 'public');
        }
        unset($validated['thumbnail']);

        if ($request->hasFile('icon')) {
            $validated['icon_path'] = $request->file('icon')->store("services/icons/{$site->slug}", 'public');
        }
        unset($validated['icon']);

        if ($request->hasFile('images')) {
            $validated['images'] = collect($request->file('images'))
                ->map(fn ($file) => $file->store("services/{$site->slug}", 'public'))
                ->all();
        } else {
            unset($validated['images']);
        }

        $service->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Service updated successfully',
            'data' => $service->fresh(['serviceType']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $site = app('current_site');
        $service = Service::where('site_id', $site->id)->findOrFail($id);
        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully',
        ]);
    }
}
