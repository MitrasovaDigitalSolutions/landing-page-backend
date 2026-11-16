<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminFaqController extends Controller
{
    public function indexCategories(): JsonResponse
    {
        $site = app('current_site');
        $categories = FaqCategory::where('site_id', $site->id)->withCount('faqs')->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $site = app('current_site');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120'],
            'order' => ['integer'],
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);

        $category = FaqCategory::create([
            'site_id' => $site->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FAQ category created successfully',
            'data' => $category,
        ], 201);
    }

    public function destroyCategory(int $id): JsonResponse
    {
        $site = app('current_site');
        $category = FaqCategory::where('site_id', $site->id)->findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'FAQ category deleted successfully',
        ]);
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $site = app('current_site');
        $category = FaqCategory::where('site_id', $site->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:120'],
            'order' => ['integer'],
        ]);

        if (isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'FAQ category updated successfully',
            'data' => $category,
        ]);
    }

    // --- FAQs ---

    public function index(): JsonResponse
    {
        $site = app('current_site');
        $faqs = Faq::where('site_id', $site->id)->with('category')->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'data' => $faqs,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $site = app('current_site');

        $validated = $request->validate([
            'faq_category_id' => ['nullable', 'exists:faq_categories,id'],
            'question' => ['required', 'string'],
            'answer' => ['required', 'string'],
            'order' => ['integer'],
            'is_active' => ['boolean'],
        ]);

        $faq = Faq::create([
            'site_id' => $site->id,
            'faq_category_id' => $validated['faq_category_id'] ?? null,
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'order' => $validated['order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FAQ created successfully',
            'data' => $faq->load('category'),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $site = app('current_site');
        $faq = Faq::where('site_id', $site->id)->findOrFail($id);

        $validated = $request->validate([
            'faq_category_id' => ['nullable', 'exists:faq_categories,id'],
            'question' => ['sometimes', 'required', 'string'],
            'answer' => ['sometimes', 'required', 'string'],
            'order' => ['integer'],
            'is_active' => ['boolean'],
        ]);

        $faq->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'FAQ updated successfully',
            'data' => $faq->fresh('category'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $site = app('current_site');
        $faq = Faq::where('site_id', $site->id)->findOrFail($id);
        $faq->delete();

        return response()->json([
            'success' => true,
            'message' => 'FAQ deleted successfully',
        ]);
    }
}
