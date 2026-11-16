<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSiteSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $site = app('current_site');
        $settings = SiteSetting::where('site_id', $site->id)->get();

        $grouped = [];
        foreach ($settings as $setting) {
            $grouped[$setting->group][$setting->key] = $setting->value;
        }

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.group' => ['required', 'string', 'max:50'],
            'settings.*.key' => ['required', 'string', 'max:50'],
            'settings.*.value' => ['present'],
        ]);

        $site = app('current_site');

        foreach ($validated['settings'] as $item) {
            SiteSetting::updateOrCreate(
                [
                    'site_id' => $site->id,
                    'key' => $item['key'],
                ],
                [
                    'group' => $item['group'],
                    'value' => $item['value'],
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
        ]);
    }
}
