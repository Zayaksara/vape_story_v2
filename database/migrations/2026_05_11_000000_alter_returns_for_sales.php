<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('returns', function (Blueprint $table) {
            // Sale-based POS return (sales.id is bigint)
            $table->unsignedBigInteger('sale_id')->nullable()->after('return_number');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->index('sale_id');
        });

        // Make order_id nullable so POS returns (Sale-based) work without an Order
        DB::statement('ALTER TABLE returns ALTER COLUMN order_id DROP NOT NULL');
    }

    public function down(): void
    {
        Schema::table('returns', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropIndex(['sale_id']);
            $table->dropColumn('sale_id');
        });

        DB::statement('ALTER TABLE returns ALTER COLUMN order_id SET NOT NULL');
    }
};
