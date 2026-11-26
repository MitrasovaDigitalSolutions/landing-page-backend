<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'settings' => ['required_without:logo', 'array'],
            'settings.*.group' => ['required', 'string', 'max:50'],
            'settings.*.key' => ['required', 'string', 'max:50'],
            'settings.*.value' => ['present'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
        ]);

        $site = app('current_site');

        foreach ($validated['settings'] ?? [] as $item) {
            // key *_url adalah nilai turunan dari *_path (dibuat otomatis oleh endpoint publik), jangan disimpan
            if (str_ends_with($item['key'], '_url')) {
                continue;
            }

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

        $logo = null;

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $name = "{$site->slug}-logo-" . now()->timestamp . '-' . Str::lower(Str::random(4)) . '.' . $file->extension();
            $path = $file->storeAs('branding', $name, 'public');

            SiteSetting::updateOrCreate(
                ['site_id' => $site->id, 'key' => 'logo_path'],
                ['group' => 'general', 'value' => $path]
            );

            $logo = [
                'logo_path' => $path,
                'logo_url' => url('storage/' . ltrim($path, '/')),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => $logo,
        ]);
    }
}
