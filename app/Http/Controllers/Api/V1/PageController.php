<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    public function index(): JsonResponse
    {
        $pages = Page::forSite()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'slug', 'title', 'meta_title', 'meta_desc']);

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $page = Page::forSite()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['sections' => fn ($query) => $query->where('is_active', true)->orderBy('order', 'asc')])
            ->first();

        if (! $page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $page,
        ]);
    }
}
