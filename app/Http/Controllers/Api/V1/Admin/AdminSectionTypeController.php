<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SectionType;
use Illuminate\Http\JsonResponse;

class AdminSectionTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = SectionType::orderBy('key')->get();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }
}
