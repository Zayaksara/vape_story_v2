<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Exceptions\UrlGenerationException;

class Product extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'category_id',
        'brand_id',           // ← BARU
        'base_price',
        'nicotine_strength',
        'flavor',
        'size_ml',
        'battery_mah',
        'coil_type',
        'pod_type',
        'resistance_ohm',
        'description',
        'image',              // ← BARU
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'nicotine_strength' => 'decimal:2',
        'size_ml' => 'decimal:2',
        'resistance_ohm' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Append custom attributes to JSON/array serialization.
     * These are computed accessors needed by the frontend.
     */
    protected $appends = ['price', 'stock', 'sku', 'image_url', 'volume'];

    // ==================== RELATIONSHIPS ====================
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()                    // ← BARU
    {
        return $this->belongsTo(Brand::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    // ==================== SCOPES & HELPERS ====================
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeLowStock($query, $threshold = 20)
    {
        return $query->whereHas('batches', function ($q) use ($threshold) {
            $q->where('stock_quantity', '<=', $threshold);
        });
    }

    public function scopeNearExpiry($query, $days = 30)
    {
        return $query->whereHas('batches', function ($q) use ($days) {
            $q->where('expired_date', '<=', now()->addDays($days));
        });
    }

    public function totalStock()
    {
        return $this->batches()->sum('stock_quantity');
    }

    public function stockValue()
    {
        return $this->batches()->sum(DB::raw('stock_quantity * cost_price'));
    }

    // Accessors for frontend compatibility
    public function getPriceAttribute()
    {
        // Cast to float to ensure numeric value for JavaScript
        return (float) $this->base_price;
    }

    public function getStockAttribute()
    {
        // If batches relationship is loaded, compute sum; otherwise query
        if ($this->relationLoaded('batches')) {
            return $this->batches->sum('stock_quantity');
        }

        return $this->batches()->sum('stock_quantity');
    }

    public function getSkuAttribute()
    {
        return $this->code ?? '';
    }

    public function getImageUrlAttribute()
    {
        if (! $this->image) {
            return null;
        }

        return Storage::disk('public')->url($this->image);
    }

    public function getVolumeAttribute()
    {
        if ($this->size_ml) {
            $value = (float) $this->size_ml;

            // Format: integer values without decimal, e.g., "60ml"; floats keep decimals
            return $value == (int) $value ? (int) $value.'ml' : $value.'ml';
        }

        if ($this->battery_mah) {
            return (int) $this->battery_mah.'mAh';
        }

        return null;
    }
}
