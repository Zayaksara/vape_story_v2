<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            if (Schema::hasColumn('batches', 'expired_date')) {
                $table->dropColumn('expired_date');
            }
            $table->decimal('promo_price', 12, 2)->nullable()->after('cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->date('expired_date')->nullable();
            $table->dropColumn('promo_price');
        });
    }
};
