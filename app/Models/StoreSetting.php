<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StoreSetting extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'tagline',
        'logo_path',
        'receipt_header',
        'receipt_footer',
        'show_logo_on_receipt',
    ];

    protected $casts = [
        'show_logo_on_receipt' => 'boolean',
    ];

    protected $appends = ['logo_url'];

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], ['name' => config('app.name', 'Story Vape')]);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }
}
