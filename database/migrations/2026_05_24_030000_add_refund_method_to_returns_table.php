<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('returns', function (Blueprint $table) {
            // Metode pengembalian uang ke customer. Default null = sama dengan
            // metode bayar sale aslinya. Diisi kalau kasir refund pakai metode
            // berbeda (mis. sale via QRIS, refund cash dari laci).
            $table->string('refund_method', 20)->nullable()->after('cashier_id');
        });
    }

    public function down(): void
    {
        Schema::table('returns', function (Blueprint $table) {
            $table->dropColumn('refund_method');
        });
    }
};
