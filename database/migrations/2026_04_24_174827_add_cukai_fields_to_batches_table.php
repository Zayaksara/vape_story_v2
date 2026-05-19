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
        Schema::table('batches', function (Blueprint $table) {
            $table->integer('cukai_year')->nullable()->comment('Tahun cukai (misal: 2025, 2026)');
            $table->boolean('is_promo')->default(false)->comment('Flag untuk diskon cukai lama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn(['cukai_year', 'is_promo']);
        });
    }
};
