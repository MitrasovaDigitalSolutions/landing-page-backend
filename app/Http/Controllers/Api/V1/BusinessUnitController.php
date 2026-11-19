<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use Illuminate\Http\JsonResponse;

class BusinessUnitController extends Controller
{
    public function index(): JsonResponse
    {
        $units = BusinessUnit::forSite()
            ->orderBy('order', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $units,
        ]);
    }
}
