<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMiscController extends Controller
{
    // --- Testimonials ---
    public function indexTestimonials(): JsonResponse
    {
        $site = app('current_site');
        $testimonials = Testimonial::where('site_id', $site->id)->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'data' => $testimonials,
        ]);
    }

    public function storeTestimonial(Request $request): JsonResponse
    {
        $site = app('current_site');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:100'],
            'message' => ['required', 'string'],
            'rating' => ['integer', 'min:1', 'max:5'],
            'order' => ['integer'],
            'is_active' => ['boolean'],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store("testimonials/{$site->slug}", 'public');
        }

        $testimonial = Testimonial::create([
            'site_id' => $site->id,
            'name' => $validated['name'],
            'title' => $validated['title'] ?? null,
            'message' => $validated['message'],
            'rating' => $validated['rating'] ?? 5,
            'avatar_path' => $avatarPath,
            'order' => $validated['order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Testimonial created successfully',
            'data' => $testimonial,
        ], 201);
    }

    public function destroyTestimonial(int $id): JsonResponse
    {
        $site = app('current_site');
        $testimonial = Testimonial::where('site_id', $site->id)->findOrFail($id);
        $testimonial->delete();

        return response()->json([
            'success' => true,
            'message' => 'Testimonial deleted successfully',
        ]);
    }

    public function updateTestimonial(Request $request, int $id): JsonResponse
    {
        $site = app('current_site');
        $testimonial = Testimonial::where('site_id', $site->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:100'],
            'message' => ['sometimes', 'required', 'string'],
            'rating' => ['integer', 'min:1', 'max:5'],
            'order' => ['integer'],
            'is_active' => ['boolean'],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar_path'] = $request->file('avatar')->store("testimonials/{$site->slug}", 'public');
        }
        unset($validated['avatar']);

        $testimonial->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Testimonial updated successfully',
            'data' => $testimonial->fresh(),
        ]);
    }

    // --- Partners ---
    public function indexPartners(): JsonResponse
    {
        $site = app('current_site');
        $partners = Partner::where('site_id', $site->id)->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'data' => $partners,
        ]);
    }

    public function storePartner(Request $request): JsonResponse
    {
        $site = app('current_site');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'order' => ['integer'],
            'logo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
        ]);

        $logoPath = $request->file('logo')->store("partners/{$site->slug}", 'public');

        $partner = Partner::create([
            'site_id' => $site->id,
            'name' => $validated['name'],
            'website_url' => $validated['website_url'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'logo_path' => $logoPath,
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Partner created successfully',
            'data' => $partner,
        ], 201);
    }

    public function destroyPartner(int $id): JsonResponse
    {
        $site = app('current_site');
        $partner = Partner::where('site_id', $site->id)->findOrFail($id);
        $partner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Partner deleted successfully',
        ]);
    }

    public function updatePartner(Request $request, int $id): JsonResponse
    {
        $site = app('current_site');
        $partner = Partner::where('site_id', $site->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'order' => ['integer'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store("partners/{$site->slug}", 'public');
        }
        unset($validated['logo']);

        $partner->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Partner updated successfully',
            'data' => $partner->fresh(),
        ]);
    }
}
