<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInquiryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $site = app('current_site');
        $query = Inquiry::where('site_id', $site->id);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(20),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $site = app('current_site');
        $inquiry = Inquiry::where('site_id', $site->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $inquiry,
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $site = app('current_site');
        $inquiry = Inquiry::where('site_id', $site->id)->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:new,contacted,closed'],
            'notes' => ['nullable', 'string'],
        ]);

        $inquiry->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inquiry status updated successfully',
            'data' => $inquiry,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $site = app('current_site');
        $inquiry = Inquiry::where('site_id', $site->id)->findOrFail($id);
        $inquiry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inquiry deleted successfully',
        ]);
    }
}
