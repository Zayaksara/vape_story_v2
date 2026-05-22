<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['description', 'battery_mah', 'coil_type', 'pod_type', 'resistance_ohm'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->integer('battery_mah')->nullable();
            $table->string('coil_type', 50)->nullable();
            $table->string('pod_type', 50)->nullable();
            $table->decimal('resistance_ohm', 5, 2)->nullable();
        });
    }
};
