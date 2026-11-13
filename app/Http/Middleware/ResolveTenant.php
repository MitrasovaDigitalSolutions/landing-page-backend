<?php

namespace App\Http\Middleware;

use App\Models\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $siteKey = $request->header('X-Site-Key');

        if (! $siteKey) {
            return response()->json([
                'success' => false,
                'message' => 'Header X-Site-Key is required',
            ], 400);
        }

        $site = Site::query()
            ->where('is_active', true)
            ->where(function ($query) use ($siteKey) {
                $query->where('api_key', $siteKey)
                    ->orWhere('slug', $siteKey);
            })
            ->first();

        if (! $site) {
            return response()->json([
                'success' => false,
                'message' => 'Site not found or inactive',
            ], 404);
        }

        app()->instance('current_site', $site);
        $request->attributes->set('site', $site);

        return $next($request);
    }
}
