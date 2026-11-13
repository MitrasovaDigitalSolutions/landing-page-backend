<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('grouped')) {
            $categories = FaqCategory::forSite()
                ->with(['faqs' => fn ($query) => $query->where('is_active', true)->orderBy('order', 'asc')])
                ->orderBy('order', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        }

        $faqs = Faq::forSite()
            ->where('is_active', true)
            ->with('category')
            ->orderBy('order', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $faqs,
        ]);
    }
}
