<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SectionType;
use App\Support\SectionPayloadValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminPageController extends Controller
{
    public function index(): JsonResponse
    {
        $site = app('current_site');
        $pages = Page::where('site_id', $site->id)->withCount('sections')->get();

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $site = app('current_site');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:50'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_desc' => ['nullable', 'string', 'max:300'],
            'is_active' => ['boolean'],
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);

        $page = Page::create([
            'site_id' => $site->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_desc' => $validated['meta_desc'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Page created successfully',
            'data' => $page,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $site = app('current_site');
        $page = Page::where('site_id', $site->id)->with('sections')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $page,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $site = app('current_site');
        $page = Page::where('site_id', $site->id)->findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:50'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_desc' => ['nullable', 'string', 'max:300'],
            'is_active' => ['boolean'],
        ]);

        if (isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        $page->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Page updated successfully',
            'data' => $page,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $site = app('current_site');
        $page = Page::where('site_id', $site->id)->findOrFail($id);
        $page->delete();

        return response()->json([
            'success' => true,
            'message' => 'Page deleted successfully',
        ]);
    }

    public function storeSection(Request $request, int $pageId): JsonResponse
    {
        $site = app('current_site');
        $page = Page::where('site_id', $site->id)->findOrFail($pageId);

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'payload' => ['nullable', 'array'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
            'order' => ['integer'],
            'is_active' => ['boolean'],
        ]);

        $warnings = $this->guardSectionPayload($validated['key'], $validated['payload'] ?? null);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store("sections/{$site->slug}", 'public');
        }

        $section = PageSection::create([
            'page_id' => $page->id,
            'key' => $validated['key'],
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'body' => $validated['body'] ?? null,
            'payload' => $validated['payload'] ?? null,
            'image_path' => $imagePath,
            'order' => $validated['order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully',
            'data' => $section,
            'warnings' => $warnings,
        ], 201);
    }

    public function updateSection(Request $request, int $sectionId): JsonResponse
    {
        $site = app('current_site');
        $section = PageSection::whereHas('page', fn ($q) => $q->where('site_id', $site->id))->findOrFail($sectionId);

        $validated = $request->validate([
            'key' => ['sometimes', 'required', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'payload' => ['nullable', 'array'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
            'order' => ['integer'],
            'is_active' => ['boolean'],
        ]);

        $warnings = $this->guardSectionPayload($validated['key'] ?? $section->key, $validated['payload'] ?? null);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store("sections/{$site->slug}", 'public');
        }
        unset($validated['image']);

        $section->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully',
            'data' => $section,
            'warnings' => $warnings,
        ]);
    }

    public function destroySection(int $sectionId): JsonResponse
    {
        $site = app('current_site');
        $section = PageSection::whereHas('page', fn ($q) => $q->where('site_id', $site->id))->findOrFail($sectionId);
        $section->delete();

        return response()->json([
            'success' => true,
            'message' => 'Section deleted successfully',
        ]);
    }

    /**
     * Validate the payload against the resolved section type, returning warnings.
     */
    private function guardSectionPayload(string $key, ?array $payload): array
    {
        $type = SectionType::resolveForKey($key);

        if ($type) {
            if (! empty($payload)) {
                $errors = SectionPayloadValidator::validate($type->fields ?? [], $payload);

                if ($errors !== []) {
                    throw ValidationException::withMessages($errors);
                }
            }

            return [];
        }

        if (config('cms.section_types.strict')) {
            throw ValidationException::withMessages(['key' => 'Tipe section tidak dikenal.']);
        }

        return ["Tipe section '{$key}' belum terdaftar; payload disimpan apa adanya."];
    }
}
