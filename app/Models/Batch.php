<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'product_id',
        'lot_number',
        'stock_quantity',
        'cost_price',
        'promo_price',
        'cukai_year',
        'is_promo',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'promo_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'cukai_year' => 'integer',
        'is_promo' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockMutations()
    {
        return $this->hasMany(StockMutation::class);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function isOldCukai()
    {
        if (! $this->cukai_year) {
            return false;
        }

        return $this->cukai_year < now()->year;
    }

    public function returnItems()
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function scopeAvailableForProduct($query, $productId)
    {
        return $query->where('product_id', $productId)
            ->where('stock_quantity', '>', 0);
    }
}
