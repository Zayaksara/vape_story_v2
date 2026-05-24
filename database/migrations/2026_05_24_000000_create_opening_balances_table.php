<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_balances', function (Blueprint $table) {
            $table->id();
            $table->date('as_of_date')->comment('Tanggal cutoff pembukuan lama (saldo awal di sistem ini)');
            $table->decimal('cash', 14, 2)->default(0)->comment('Kas tunai di kasir/brankas saat as_of_date');
            $table->decimal('bank', 14, 2)->default(0)->comment('Saldo bank + e-wallet + QRIS gabungan saat as_of_date');
            $table->decimal('inventory_value', 14, 2)->default(0)->comment('Nilai persediaan saat as_of_date (referensi audit)');
            $table->decimal('fixed_assets', 14, 2)->default(0)->comment('Total aset tetap (etalase, komputer, dll) saat as_of_date');
            $table->decimal('accounts_payable', 14, 2)->default(0)->comment('Hutang usaha ke supplier');
            $table->decimal('other_liabilities', 14, 2)->default(0)->comment('Hutang lain (pinjaman, dll)');
            $table->decimal('equity', 14, 2)->default(0)->comment('Modal awal pemilik');
            $table->decimal('retained_earnings', 14, 2)->default(0)->comment('Laba ditahan dari pembukuan lama');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_balances');
    }
};
