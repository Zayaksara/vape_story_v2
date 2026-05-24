<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_item_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_item_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('batch_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 2)->comment('HPP per unit saat konsumsi batch (snapshot)');
            $table->decimal('unit_price', 12, 2)->comment('Harga jual per unit dari batch ini (snapshot, bisa promo)');
            $table->boolean('is_promo')->default(false);
            $table->integer('returned_quantity')->default(0)->comment('Qty yang sudah dikembalikan dari alokasi ini');
            $table->timestamps();

            $table->index('sale_item_id');
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_item_batches');
    }
};
