<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItemBatch extends Model
{
    protected $fillable = [
        'sale_item_id',
        'batch_id',
        'quantity',
        'unit_cost',
        'unit_price',
        'is_promo',
        'returned_quantity',
        'is_synthetic',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'returned_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'is_promo' => 'boolean',
        'is_synthetic' => 'boolean',
    ];

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function remainingQuantity(): int
    {
        return max(0, (int) $this->quantity - (int) $this->returned_quantity);
    }
}
