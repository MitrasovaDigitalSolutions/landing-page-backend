<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('website_url');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->json('images')->nullable()->after('thumbnail_path');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('images');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
