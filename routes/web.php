<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', function () {
    return view('swagger');
});

Route::get('/docs/openapi.json', function () {
    $path = public_path('docs/openapi.json');
    if (! file_exists($path)) {
        abort(404, 'OpenAPI specification not found');
    }

    return response()->file($path, [
        'Content-Type' => 'application/json',
    ]);
});
