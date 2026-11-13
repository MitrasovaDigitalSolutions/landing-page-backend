<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Portfolio::forSite()
            ->with('serviceType')
            ->orderBy('order', 'asc');

        if ($request->filled('service_type')) {
            $serviceType = $request->query('service_type');
            $query->whereHas('serviceType', function ($q) use ($serviceType) {
                $q->where('slug', $serviceType)->orWhere('id', $serviceType);
            });
        }

        if ($request->has('featured')) {
            $query->where('is_featured', $request->boolean('featured'));
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $portfolios = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $portfolios->items(),
            'meta' => [
                'current_page' => $portfolios->currentPage(),
                'last_page' => $portfolios->lastPage(),
                'per_page' => $portfolios->perPage(),
                'total' => $portfolios->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $portfolio = Portfolio::forSite()
            ->where('slug', $slug)
            ->with('serviceType')
            ->first();

        if (! $portfolio) {
            return response()->json([
                'success' => false,
                'message' => 'Portfolio not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $portfolio,
        ]);
    }
}
