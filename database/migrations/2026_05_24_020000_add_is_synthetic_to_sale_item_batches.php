<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_item_batches', function (Blueprint $table) {
            // true = alokasi sintetis hasil backfill dari sale legacy (sebelum FIFO aktif).
            // false = alokasi nyata hasil ProcessPaymentController saat checkout.
            $table->boolean('is_synthetic')->default(false)->after('returned_quantity');
            $table->index('is_synthetic');
        });
    }

    public function down(): void
    {
        Schema::table('sale_item_batches', function (Blueprint $table) {
            $table->dropIndex(['is_synthetic']);
            $table->dropColumn('is_synthetic');
        });
    }
};
