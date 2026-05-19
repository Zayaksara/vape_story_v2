<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('promo_discount', 12, 2)->default(0)->after('discount');
            $table->unsignedInteger('promo_units')->default(0)->after('promo_discount');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['promo_discount', 'promo_units']);
        });
    }
};
