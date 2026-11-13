<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = ArticleCategory::forSite()
            ->withCount(['articles' => fn ($query) => $query->where('status', 'published')])
            ->orderBy('order', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Article::forSite()
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->with(['category', 'author:id,name'])
            ->orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('category')) {
            $category = $request->query('category');
            $query->whereHas('category', function ($q) use ($category) {
                $q->where('slug', $category)->orWhere('id', $category);
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $articles = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $articles->items(),
            'meta' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $article = Article::forSite()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->with(['category', 'author:id,name'])
            ->first();

        if (! $article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $article,
        ]);
    }
}
