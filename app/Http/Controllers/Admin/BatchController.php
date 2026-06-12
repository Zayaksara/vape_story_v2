<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BatchController extends Controller
{
    private function rules(): array
    {
        return [
            'lot_number'     => ['nullable', 'string', 'max:100'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'cost_price'     => ['required', 'numeric', 'min:0'],
            'cukai_year'     => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'is_promo'       => ['nullable', 'boolean'],
            'promo_price'    => ['nullable', 'numeric', 'min:0', 'required_if:is_promo,true,1'],
        ];
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $batch = new Batch();
        $batch->id             = (string) Str::uuid();
        $batch->product_id     = $product->getKey();
        $batch->lot_number     = $data['lot_number'] ?: 'LOT-'.strtoupper(Str::random(6));
        $batch->stock_quantity = $data['stock_quantity'];
        $batch->cost_price     = $data['cost_price'];
        $batch->cukai_year     = $data['cukai_year'] ?? null;
        $batch->is_promo       = (bool) ($data['is_promo'] ?? false);
        $batch->promo_price    = $batch->is_promo ? ($data['promo_price'] ?? null) : null;
        $batch->save();

        return back()->with('success', 'Batch stok berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product, Batch $batch): RedirectResponse
    {
        $this->ensureBelongsToProduct($product, $batch);

        $data = $request->validate($this->rules());

        $data['is_promo']    = (bool) ($data['is_promo'] ?? false);
        $data['promo_price'] = $data['is_promo'] ? ($data['promo_price'] ?? null) : null;

        $batch->update($data);

        return back()->with('success', 'Batch stok berhasil diperbarui.');
    }

    public function destroy(Product $product, Batch $batch): RedirectResponse
    {
        $this->ensureBelongsToProduct($product, $batch);

        if ($batch->stock_quantity > 0) {
            return back()->withErrors([
                'batch' => 'Batch masih memiliki stok. Kosongkan stok (penyesuaian) sebelum menghapus.',
            ]);
        }

        // Hard delete batch men-cascade alokasi penjualan, mutasi stok, dan retur.
        if ($this->hasHistory($batch)) {
            return back()->withErrors([
                'batch' => 'Batch tidak dapat dihapus karena memiliki riwayat penjualan/retur.',
            ]);
        }

        $batch->delete();

        return back()->with('success', 'Batch stok berhasil dihapus.');
    }

    private function ensureBelongsToProduct(Product $product, Batch $batch): void
    {
        if ($batch->product_id !== $product->getKey()) {
            abort(404);
        }
    }

    private function hasHistory(Batch $batch): bool
    {
        return DB::table('sale_item_batches')->where('batch_id', $batch->id)->exists()
            || DB::table('order_items')->where('batch_id', $batch->id)->exists()
            || DB::table('return_items')->where('batch_id', $batch->id)->exists();
    }
}
