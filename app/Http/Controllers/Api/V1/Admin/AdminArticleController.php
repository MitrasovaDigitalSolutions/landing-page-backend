<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminArticleController extends Controller
{
    public function indexCategories(): JsonResponse
    {
        $site = app('current_site');
        $categories = ArticleCategory::where('site_id', $site->id)->withCount('articles')->get();

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
            'description' => ['nullable', 'string'],
            'order' => ['integer'],
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);

        $category = ArticleCategory::create([
            'site_id' => $site->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Article category created successfully',
            'data' => $category,
        ], 201);
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $site = app('current_site');
        $category = ArticleCategory::where('site_id', $site->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'order' => ['integer'],
        ]);

        if (isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Article category updated successfully',
            'data' => $category,
        ]);
    }

    public function destroyCategory(int $id): JsonResponse
    {
        $site = app('current_site');
        $category = ArticleCategory::where('site_id', $site->id)->findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article category deleted successfully',
        ]);
    }

    // --- Articles ---

    public function index(Request $request): JsonResponse
    {
        $site = app('current_site');
        $query = Article::where('site_id', $site->id)->with(['category', 'author']);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(20),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $site = app('current_site');
        $user = $request->user();

        $validated = $request->validate([
            'article_category_id' => ['nullable', 'exists:article_categories,id'],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220'],
            'excerpt' => ['nullable', 'string', 'max:350'],
            'body' => ['required', 'string'],
            'status' => ['required', 'in:draft,published,archived'],
            'published_at' => ['nullable', 'date'],
            'thumbnail' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store("articles/{$site->slug}", 'public');
        }

        $publishedAt = $validated['published_at'] ?? null;
        if ($validated['status'] === 'published' && ! $publishedAt) {
            $publishedAt = now();
        }

        $article = Article::create([
            'site_id' => $site->id,
            'article_category_id' => $validated['article_category_id'] ?? null,
            'author_id' => $user->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $validated['body'],
            'status' => $validated['status'],
            'published_at' => $publishedAt,
            'thumbnail_path' => $thumbnailPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Article created successfully',
            'data' => $article->load(['category', 'author']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $site = app('current_site');
        $article = Article::where('site_id', $site->id)->with(['category', 'author'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $article,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $site = app('current_site');
        $article = Article::where('site_id', $site->id)->findOrFail($id);

        $validated = $request->validate([
            'article_category_id' => ['nullable', 'exists:article_categories,id'],
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:220'],
            'excerpt' => ['nullable', 'string', 'max:350'],
            'body' => ['sometimes', 'required', 'string'],
            'status' => ['sometimes', 'required', 'in:draft,published,archived'],
            'published_at' => ['nullable', 'date'],
            'thumbnail' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if (isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail_path'] = $request->file('thumbnail')->store("articles/{$site->slug}", 'public');
        }
        unset($validated['thumbnail']);

        $article->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Article updated successfully',
            'data' => $article->fresh(['category', 'author']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $site = app('current_site');
        $article = Article::where('site_id', $site->id)->findOrFail($id);
        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article deleted successfully',
        ]);
    }
}
