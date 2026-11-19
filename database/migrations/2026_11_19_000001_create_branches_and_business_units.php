<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('name');
            $table->text('address');
            $table->string('phone')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->json('images')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->index(['site_id', 'order']);
        });

        Schema::create('business_units', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('image_path')->nullable();
            $table->string('url')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->index(['site_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_units');
        Schema::dropIfExists('branches');
    }
};
