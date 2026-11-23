<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\ResolveTenant::class,
            'tenant.auth' => \App\Http\Middleware\AuthorizeTenantAccess::class,
            'superadmin' => \App\Http\Middleware\RequireSuperadmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Ubah pelanggaran constraint DB jadi 422, bukan 500 (mis. slug duplikat saat admin menyimpan)
        $exceptions->render(function (\Illuminate\Database\UniqueConstraintViolationException $e, \Illuminate\Http\Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Data sudah ada (duplikat).',
            ], 422);
        });

        $exceptions->render(function (\Illuminate\Database\NotNullConstraintViolationException $e, \Illuminate\Http\Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Ada kolom wajib yang kosong.',
            ], 422);
        });

        $exceptions->render(function (\Illuminate\Database\ForeignKeyConstraintViolationException $e, \Illuminate\Http\Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Data terkait tidak ditemukan.',
            ], 422);
        });
    })->create();
