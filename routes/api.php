<?php

use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\FaqController;
use App\Http\Controllers\Api\V1\InquiryController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PartnerController;
use App\Http\Controllers\Api\V1\PortfolioController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\SiteSettingController;
use App\Http\Controllers\Api\V1\TestimonialController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('tenant')->group(function () {
    Route::get('/settings', [SiteSettingController::class, 'index']);

    Route::get('/pages/{slug}', [PageController::class, 'show']);

    Route::get('/service-types', [ServiceController::class, 'types']);
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{slug}', [ServiceController::class, 'show']);

    Route::get('/portfolios', [PortfolioController::class, 'index']);
    Route::get('/portfolios/{slug}', [PortfolioController::class, 'show']);

    Route::get('/article-categories', [ArticleController::class, 'categories']);
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::get('/articles/{slug}', [ArticleController::class, 'show']);

    Route::get('/faqs', [FaqController::class, 'index']);

    Route::get('/testimonials', [TestimonialController::class, 'index']);

    Route::get('/partners', [PartnerController::class, 'index']);

    Route::post('/inquiries', [InquiryController::class, 'store']);
});
