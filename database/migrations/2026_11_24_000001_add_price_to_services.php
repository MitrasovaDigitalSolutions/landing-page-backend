<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // harga per produk (rupiah, tanpa desimal); null = tidak menampilkan harga
            $table->unsignedBigInteger('price')->nullable()->after('features');
            $table->string('price_note', 30)->nullable()->after('price');
        });

        // produk non-jasa (mis. konveksi) sering tidak punya deskripsi panjang
        Schema::table('services', function (Blueprint $table) {
            $table->text('body')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['price', 'price_note']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->text('body')->nullable(false)->change();
        });
    }
};
