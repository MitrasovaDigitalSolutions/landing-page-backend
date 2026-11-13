<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('api_key')->unique();
            $table->string('domain')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('site_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('editor');
            $table->timestamps();
            $table->unique(['site_id', 'user_id']);
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('group')->default('general');
            $table->string('key');
            $table->json('value');
            $table->timestamps();
            $table->unique(['site_id', 'key']);
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->string('meta_title')->nullable();
            $table->string('meta_desc')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->unique(['site_id', 'slug']);
        });

        Schema::create('page_sections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('key');
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('body')->nullable();
            $table->json('payload')->nullable();
            $table->string('image_path')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->unique(['page_id', 'key']);
        });

        Schema::create('service_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->unique(['site_id', 'slug']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained('service_types')->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('short_desc');
            $table->text('body');
            $table->string('icon_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->unique(['site_id', 'slug']);
        });

        Schema::create('article_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->unique(['site_id', 'slug']);
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('article_category_id')->nullable()->constrained('article_categories')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('excerpt')->nullable();
            $table->text('body');
            $table->string('thumbnail_path')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['site_id', 'slug']);
        });

        Schema::create('faq_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->unique(['site_id', 'slug']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('faq_category_id')->nullable()->constrained('faq_categories')->nullOnDelete();
            $table->text('question');
            $table->text('answer');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('avatar_path')->nullable();
            $table->text('message');
            $table->smallInteger('rating')->default(5);
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('portfolios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('client_name')->nullable();
            $table->date('completion_date')->nullable();
            $table->text('description')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->json('gallery_images')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->unique(['site_id', 'slug']);
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('name');
            $table->string('logo_path');
            $table->string('website_url')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('inquiries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status')->default('new');
            $table->string('attachment_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('portfolios');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('faq_categories');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('article_categories');
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_types');
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('site_user');
        Schema::dropIfExists('sites');
    }
};
