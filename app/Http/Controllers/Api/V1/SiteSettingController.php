<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

class SiteSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = SiteSetting::forSite()->get();

        $grouped = [];
        foreach ($settings as $setting) {
            // key *_url tidak pernah disimpan; selalu diturunkan dari *_path di bawah
            if (str_ends_with($setting->key, '_url')) {
                continue;
            }

            $grouped[$setting->group][$setting->key] = $setting->value;

            // expose absolute URL for any *_path setting so the frontend never builds storage URLs itself
            if (str_ends_with($setting->key, '_path') && is_string($setting->value) && $setting->value !== '') {
                $grouped[$setting->group][substr($setting->key, 0, -5) . '_url'] = str_starts_with($setting->value, 'http')
                    ? $setting->value
                    : url('storage/' . ltrim($setting->value, '/'));
            }
        }

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }
}
