<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BatchController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'lot_number'     => ['nullable', 'string', 'max:100'],
            'expired_date'   => ['required', 'date'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'cost_price'     => ['required', 'numeric', 'min:0'],
            'cukai_year'     => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'is_promo'       => ['boolean'],
        ]);

        $batch = new Batch();
        $batch->id             = (string) Str::uuid();
        $batch->product_id     = $product->getKey();
        $batch->lot_number     = $data['lot_number'] ?: 'LOT-'.strtoupper(Str::random(6));
        $batch->expired_date   = $data['expired_date'];
        $batch->stock_quantity = $data['stock_quantity'];
        $batch->cost_price     = $data['cost_price'];
        $batch->cukai_year     = $data['cukai_year'] ?? null;
        $batch->is_promo       = $data['is_promo'] ?? false;
        $batch->save();

        return back()->with('success', 'Batch stok berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product, Batch $batch): RedirectResponse
    {
        $data = $request->validate([
            'lot_number'     => ['nullable', 'string', 'max:100'],
            'expired_date'   => ['required', 'date'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'cost_price'     => ['required', 'numeric', 'min:0'],
            'cukai_year'     => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'is_promo'       => ['boolean'],
        ]);

        $batch->update($data);

        return back()->with('success', 'Batch stok berhasil diperbarui.');
    }

    public function destroy(Product $product, Batch $batch): RedirectResponse
    {
        $batch->delete();

        return back()->with('success', 'Batch stok berhasil dihapus.');
    }
}
