<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSuperController extends Controller
{
    // --- Site Management ---
    public function indexSites(): JsonResponse
    {
        $sites = Site::withCount(['users', 'services', 'articles'])->get();

        return response()->json([
            'success' => true,
            'data' => $sites,
        ]);
    }

    public function storeSite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:50', 'unique:sites,slug'],
            'domain' => ['nullable', 'string', 'max:150'],
            'is_active' => ['boolean'],
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);

        $site = Site::create([
            'id' => (string) Str::uuid(),
            'name' => $validated['name'],
            'slug' => $slug,
            'api_key' => 'key-' . $slug . '-' . Str::random(8),
            'domain' => $validated['domain'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Site created successfully',
            'data' => $site,
        ], 201);
    }

    public function updateSite(Request $request, string $id): JsonResponse
    {
        $site = Site::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:50', 'unique:sites,slug,' . $site->id],
            'domain' => ['nullable', 'string', 'max:150'],
            'is_active' => ['boolean'],
        ]);

        if (isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        $site->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Site updated successfully',
            'data' => $site,
        ]);
    }

    // --- User & Site Role Assignment ---
    public function indexUsers(): JsonResponse
    {
        $users = User::with('sites')->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function assignSiteUser(Request $request, string $siteId): JsonResponse
    {
        $site = Site::findOrFail($siteId);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', 'in:admin,editor'],
        ]);

        $site->users()->syncWithoutDetaching([
            $validated['user_id'] => ['role' => $validated['role']],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User assigned to site successfully',
        ]);
    }

    public function removeSiteUser(string $siteId, int $userId): JsonResponse
    {
        $site = Site::findOrFail($siteId);
        $site->users()->detach($userId);

        return response()->json([
            'success' => true,
            'message' => 'User removed from site successfully',
        ]);
    }

    public function destroySite(string $id): JsonResponse
    {
        $site = Site::withCount(['services', 'articles', 'portfolios'])->findOrFail($id);

        if ($site->services_count || $site->articles_count || $site->portfolios_count) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete site that still has services, articles or portfolios',
            ], 422);
        }

        $site->delete();

        return response()->json([
            'success' => true,
            'message' => 'Site deleted successfully',
        ]);
    }

    public function storeUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'is_superadmin' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_superadmin' => $validated['is_superadmin'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user,
        ], 201);
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:150', 'unique:users,email,' . $user->id],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'is_superadmin' => ['boolean'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->fresh('sites'),
        ]);
    }
}
