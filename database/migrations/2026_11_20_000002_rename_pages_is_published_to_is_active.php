<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Samakan nama flag di semua level: is_active.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->renameColumn('is_published', 'is_active');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->renameColumn('is_active', 'is_published');
        });
    }
};
