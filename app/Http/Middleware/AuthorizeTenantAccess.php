<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Superadmin has global access to all tenants
        if ($user->is_superadmin) {
            return $next($request);
        }

        $site = app()->bound('current_site') ? app('current_site') : null;
        if (! $site) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant site not identified.',
            ], 400);
        }

        $hasAccess = $user->sites()->where('site_id', $site->id)->exists();
        if (! $hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to manage this site.',
            ], 403);
        }

        return $next($request);
    }
}
