<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class OpeningBalance extends Model
{
    protected $fillable = [
        'as_of_date',
        'cash',
        'bank',
        'inventory_value',
        'fixed_assets',
        'accounts_payable',
        'other_liabilities',
        'equity',
        'retained_earnings',
        'notes',
    ];

    protected $casts = [
        'as_of_date'        => 'date',
        'cash'              => 'decimal:2',
        'bank'              => 'decimal:2',
        'inventory_value'   => 'decimal:2',
        'fixed_assets'      => 'decimal:2',
        'accounts_payable'  => 'decimal:2',
        'other_liabilities' => 'decimal:2',
        'equity'            => 'decimal:2',
        'retained_earnings' => 'decimal:2',
    ];

    /**
     * Singleton accessor. Selalu return satu row aktif —
     * kalau belum ada, return instance default (semua nol) tanpa di-save.
     */
    public static function current(): self
    {
        return self::query()->orderByDesc('id')->first() ?? new self([
            'as_of_date'        => Carbon::today()->toDateString(),
            'cash'              => 0,
            'bank'              => 0,
            'inventory_value'   => 0,
            'fixed_assets'      => 0,
            'accounts_payable'  => 0,
            'other_liabilities' => 0,
            'equity'            => 0,
            'retained_earnings' => 0,
        ]);
    }
}
