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
        'receipt_options',
    ];

    protected $casts = [
        'show_logo_on_receipt' => 'boolean',
        'receipt_options' => 'array',
    ];

    protected $appends = ['logo_url', 'receipt_options_resolved'];

    /**
     * Default toggle state — semua section ditampilkan kecuali yang umumnya off.
     * Owner bisa ubah lewat halaman Settings.
     */
    public const DEFAULT_RECEIPT_OPTIONS = [
        'show_logo'            => false,
        'show_store_name'      => true,
        'show_address'         => true,
        'show_phone'           => true,
        'show_status_badge'    => true,
        'show_datetime'        => true,
        'show_invoice_number'  => true,
        'show_transaction_id'  => true,
        'show_item_unit_line'  => true,
        'show_subtotal'        => true,
        'show_discount_row'    => true,
        'show_payment_method'  => true,
        'show_cash_received'   => true,
        'show_change'          => true,
        'show_cashier'         => true,
        'show_header_text'     => true,
        'show_footer_text'     => true,
    ];

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

    /**
     * Merge user-saved options dengan default supaya field baru selalu ada.
     */
    public function getReceiptOptionsResolvedAttribute(): array
    {
        $saved = is_array($this->receipt_options) ? $this->receipt_options : [];
        $merged = array_merge(self::DEFAULT_RECEIPT_OPTIONS, $saved);

        // Backward-compat: kalau `show_logo` belum pernah disimpan di JSON, ambil dari kolom lama.
        if (! array_key_exists('show_logo', $saved)) {
            $merged['show_logo'] = (bool) $this->show_logo_on_receipt;
        }

        return $merged;
    }
}
