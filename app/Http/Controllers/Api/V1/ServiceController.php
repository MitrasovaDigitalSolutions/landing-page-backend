<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function types(): JsonResponse
    {
        $types = ServiceType::forSite()
            ->withCount(['services' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('order', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Service::forSite()
            ->where('is_active', true)
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
        $services = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $services->items(),
            'meta' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $service = Service::forSite()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with('serviceType')
            ->first();

        if (! $service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $service,
        ]);
    }
}
