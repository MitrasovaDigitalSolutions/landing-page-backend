<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SectionType;
use Illuminate\Http\JsonResponse;

class SectionTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = SectionType::where('is_active', true)->orderBy('key')->get();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }
}
