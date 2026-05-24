<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OpeningBalance;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OpeningBalanceController extends Controller
{
    public function edit()
    {
        $opening = OpeningBalance::current();

        return Inertia::render('admin/OpeningBalance', [
            'opening' => [
                'id'                => $opening->id,
                'as_of_date'        => $opening->as_of_date?->toDateString(),
                'cash'              => (float) $opening->cash,
                'bank'              => (float) $opening->bank,
                'inventory_value'   => (float) $opening->inventory_value,
                'fixed_assets'      => (float) $opening->fixed_assets,
                'accounts_payable'  => (float) $opening->accounts_payable,
                'other_liabilities' => (float) $opening->other_liabilities,
                'equity'            => (float) $opening->equity,
                'retained_earnings' => (float) $opening->retained_earnings,
                'notes'             => $opening->notes,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'as_of_date'        => 'required|date',
            'cash'              => 'required|numeric|min:0',
            'bank'              => 'required|numeric|min:0',
            'inventory_value'   => 'required|numeric|min:0',
            'fixed_assets'      => 'required|numeric|min:0',
            'accounts_payable'  => 'required|numeric|min:0',
            'other_liabilities' => 'required|numeric|min:0',
            'equity'            => 'required|numeric|min:0',
            'retained_earnings' => 'required|numeric',
            'notes'             => 'nullable|string|max:1000',
        ]);

        $opening = OpeningBalance::query()->orderByDesc('id')->first();
        if ($opening) {
            $opening->update($data);
        } else {
            OpeningBalance::create($data);
        }

        return redirect()->route('admin.audit.opening-balance.edit')
            ->with('success', 'Saldo awal berhasil disimpan.');
    }
}
