<?php

use App\Http\Controllers\Api\V1\Admin\AdminArticleController;
use App\Http\Controllers\Api\V1\Admin\AdminBranchController;
use App\Http\Controllers\Api\V1\Admin\AdminBusinessUnitController;
use App\Http\Controllers\Api\V1\Admin\AdminFaqController;
use App\Http\Controllers\Api\V1\Admin\AdminInquiryController;
use App\Http\Controllers\Api\V1\Admin\AdminMediaController;
use App\Http\Controllers\Api\V1\Admin\AdminMiscController;
use App\Http\Controllers\Api\V1\Admin\AdminPageController;
use App\Http\Controllers\Api\V1\Admin\AdminPortfolioController;
use App\Http\Controllers\Api\V1\Admin\AdminSectionTypeController;
use App\Http\Controllers\Api\V1\Admin\AdminServiceController;
use App\Http\Controllers\Api\V1\Admin\AdminSiteSettingController;
use App\Http\Controllers\Api\V1\Admin\AdminSuperController;
use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\BusinessUnitController;
use App\Http\Controllers\Api\V1\FaqController;
use App\Http\Controllers\Api\V1\InquiryController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PartnerController;
use App\Http\Controllers\Api\V1\PortfolioController;
use App\Http\Controllers\Api\V1\SectionTypeController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\SiteSettingController;
use App\Http\Controllers\Api\V1\TestimonialController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // --- Authentication Routes ---
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // --- Public Frontend Tenant Endpoints (Read-only + Inquiry Form) ---
    Route::middleware('tenant')->group(function () {
        Route::get('/settings', [SiteSettingController::class, 'index']);
        Route::get('/section-types', [SectionTypeController::class, 'index']);
        Route::get('/pages', [PageController::class, 'index']);
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
        Route::get('/branches', [BranchController::class, 'index']);
        Route::get('/business-units', [BusinessUnitController::class, 'index']);
        Route::post('/inquiries', [InquiryController::class, 'store']);
    });

    // --- Superadmin Global Management Routes ---
    Route::prefix('admin')->middleware(['auth:sanctum', 'superadmin'])->group(function () {
        Route::get('/sites', [AdminSuperController::class, 'indexSites']);
        Route::post('/sites', [AdminSuperController::class, 'storeSite']);
        Route::put('/sites/{id}', [AdminSuperController::class, 'updateSite']);
        Route::delete('/sites/{id}', [AdminSuperController::class, 'destroySite']);

        Route::get('/users', [AdminSuperController::class, 'indexUsers']);
        Route::post('/users', [AdminSuperController::class, 'storeUser']);
        Route::put('/users/{id}', [AdminSuperController::class, 'updateUser']);
        Route::post('/sites/{siteId}/users', [AdminSuperController::class, 'assignSiteUser']);
        Route::delete('/sites/{siteId}/users/{userId}', [AdminSuperController::class, 'removeSiteUser']);
    });

    // --- Tenant-Scoped Admin CRUD Endpoints ---
    Route::prefix('admin')->middleware(['auth:sanctum', 'tenant', 'tenant.auth'])->group(function () {
        // Settings
        Route::get('/settings', [AdminSiteSettingController::class, 'index']);
        Route::post('/settings', [AdminSiteSettingController::class, 'update']);

        // Section Types
        Route::get('/section-types', [AdminSectionTypeController::class, 'index']);

        // Pages & Sections
        Route::get('/pages', [AdminPageController::class, 'index']);
        Route::post('/pages', [AdminPageController::class, 'store']);
        Route::get('/pages/{id}', [AdminPageController::class, 'show']);
        Route::put('/pages/{id}', [AdminPageController::class, 'update']);
        Route::delete('/pages/{id}', [AdminPageController::class, 'destroy']);
        Route::post('/pages/{pageId}/sections', [AdminPageController::class, 'storeSection']);
        Route::put('/sections/{id}', [AdminPageController::class, 'updateSection']);
        Route::delete('/sections/{id}', [AdminPageController::class, 'destroySection']);

        // Service Types & Services
        Route::get('/service-types', [AdminServiceController::class, 'indexTypes']);
        Route::post('/service-types', [AdminServiceController::class, 'storeType']);
        Route::put('/service-types/{id}', [AdminServiceController::class, 'updateType']);
        Route::delete('/service-types/{id}', [AdminServiceController::class, 'destroyType']);

        Route::get('/services', [AdminServiceController::class, 'index']);
        Route::post('/services', [AdminServiceController::class, 'store']);
        Route::get('/services/{id}', [AdminServiceController::class, 'show']);
        Route::put('/services/{id}', [AdminServiceController::class, 'update']);
        Route::delete('/services/{id}', [AdminServiceController::class, 'destroy']);

        // Portfolios
        Route::get('/portfolios', [AdminPortfolioController::class, 'index']);
        Route::post('/portfolios', [AdminPortfolioController::class, 'store']);
        Route::get('/portfolios/{id}', [AdminPortfolioController::class, 'show']);
        Route::put('/portfolios/{id}', [AdminPortfolioController::class, 'update']);
        Route::delete('/portfolios/{id}', [AdminPortfolioController::class, 'destroy']);

        // Articles & Categories
        Route::get('/article-categories', [AdminArticleController::class, 'indexCategories']);
        Route::post('/article-categories', [AdminArticleController::class, 'storeCategory']);
        Route::put('/article-categories/{id}', [AdminArticleController::class, 'updateCategory']);
        Route::delete('/article-categories/{id}', [AdminArticleController::class, 'destroyCategory']);

        Route::get('/articles', [AdminArticleController::class, 'index']);
        Route::post('/articles', [AdminArticleController::class, 'store']);
        Route::get('/articles/{id}', [AdminArticleController::class, 'show']);
        Route::put('/articles/{id}', [AdminArticleController::class, 'update']);
        Route::delete('/articles/{id}', [AdminArticleController::class, 'destroy']);

        // FAQs
        Route::get('/faq-categories', [AdminFaqController::class, 'indexCategories']);
        Route::post('/faq-categories', [AdminFaqController::class, 'storeCategory']);
        Route::put('/faq-categories/{id}', [AdminFaqController::class, 'updateCategory']);
        Route::delete('/faq-categories/{id}', [AdminFaqController::class, 'destroyCategory']);

        Route::get('/faqs', [AdminFaqController::class, 'index']);
        Route::post('/faqs', [AdminFaqController::class, 'store']);
        Route::put('/faqs/{id}', [AdminFaqController::class, 'update']);
        Route::delete('/faqs/{id}', [AdminFaqController::class, 'destroy']);

        // Testimonials & Partners
        Route::get('/testimonials', [AdminMiscController::class, 'indexTestimonials']);
        Route::post('/testimonials', [AdminMiscController::class, 'storeTestimonial']);
        Route::put('/testimonials/{id}', [AdminMiscController::class, 'updateTestimonial']);
        Route::delete('/testimonials/{id}', [AdminMiscController::class, 'destroyTestimonial']);

        Route::get('/partners', [AdminMiscController::class, 'indexPartners']);
        Route::post('/partners', [AdminMiscController::class, 'storePartner']);
        Route::put('/partners/{id}', [AdminMiscController::class, 'updatePartner']);
        Route::delete('/partners/{id}', [AdminMiscController::class, 'destroyPartner']);

        // Branches & Business Units
        Route::get('/branches', [AdminBranchController::class, 'index']);
        Route::post('/branches', [AdminBranchController::class, 'store']);
        Route::put('/branches/{id}', [AdminBranchController::class, 'update']);
        Route::delete('/branches/{id}', [AdminBranchController::class, 'destroy']);

        Route::get('/business-units', [AdminBusinessUnitController::class, 'index']);
        Route::post('/business-units', [AdminBusinessUnitController::class, 'store']);
        Route::put('/business-units/{id}', [AdminBusinessUnitController::class, 'update']);
        Route::delete('/business-units/{id}', [AdminBusinessUnitController::class, 'destroy']);

        // Media Library (gambar + dipakai di mana)
        Route::get('/media', [AdminMediaController::class, 'index']);
        Route::post('/media/replace', [AdminMediaController::class, 'replace']);
        Route::delete('/media', [AdminMediaController::class, 'destroy']);

        // Inquiries (Inbox)
        Route::get('/inquiries', [AdminInquiryController::class, 'index']);
        Route::get('/inquiries/{id}', [AdminInquiryController::class, 'show']);
        Route::patch('/inquiries/{id}/status', [AdminInquiryController::class, 'updateStatus']);
        Route::delete('/inquiries/{id}', [AdminInquiryController::class, 'destroy']);
    });
});
