<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMutation;
use App\Enums\MutationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcessPaymentController extends Controller
{
    public function process(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric',
            'paid_amount' => 'required|numeric',
            'discount_amount' => 'required|numeric',
            'discount_code' => 'nullable|string|max:64',
            'discount_label' => 'nullable|string|max:120',
            'tax_amount' => 'required|numeric',
            'payment_method' => 'required|string|in:cash,bank_transfer,qris,e_wallet',
        ]);

        try {
            DB::beginTransaction();

            $sale = Sale::create([
                'user_id' => auth()->id(),
                'total_amount' => 0, // direkalkulasi setelah konsumsi batch
                'paid_amount' => $validated['paid_amount'],
                'discount_amount' => $validated['discount_amount'],
                'discount_code'   => $validated['discount_code']  ?? null,
                'discount_label'  => $validated['discount_label'] ?? null,
                'tax_amount' => $validated['tax_amount'],
                'payment_method' => $validated['payment_method'],
                'status' => 'completed',
            ]);

            $saleSubtotal = 0.0; // total setelah promo cukai + diskon manual per item (sebelum diskon transaksi)

            foreach ($validated['items'] as $item) {
                $product   = Product::find($item['product_id']);
                $basePrice = (float) ($product?->base_price ?? $item['unit_price']);
                $quantity  = (int) $item['quantity'];
                $remaining = $quantity;

                // FIFO: prioritaskan batch promo (cukai lama) lebih dulu, lalu yang lebih dulu masuk.
                $batches = Batch::query()
                    ->where('product_id', $item['product_id'])
                    ->where('stock_quantity', '>', 0)
                    ->orderByDesc('is_promo')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                if ((int) $batches->sum('stock_quantity') < $remaining) {
                    throw new \RuntimeException('Stok tidak mencukupi untuk salah satu produk.');
                }

                $actualRevenue = 0.0; // total dibayar customer untuk item ini (mixed pricing)
                $promoUnits    = 0;   // berapa unit yang diambil dari batch promo

                foreach ($batches as $batch) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $take = min($remaining, (int) $batch->stock_quantity);
                    if ($take <= 0) {
                        continue;
                    }

                    // Harga unit yang berlaku saat batch ini di-konsumsi.
                    $isPromoBatch = (bool) $batch->is_promo && $batch->promo_price !== null;
                    $unitPriceForBatch = $isPromoBatch ? (float) $batch->promo_price : $basePrice;

                    $actualRevenue += $unitPriceForBatch * $take;
                    if ($isPromoBatch) {
                        $promoUnits += $take;
                    }

                    $batch->decrement('stock_quantity', $take);

                    StockMutation::create([
                        'batch_id' => $batch->id,
                        'mutation_type' => MutationType::SALE,
                        'quantity' => $take,
                        'reference_type' => Product::class,
                        'reference_id' => $item['product_id'],
                        'notes' => $isPromoBatch
                            ? 'POS sale item deduction (cukai lama)'
                            : 'POS sale item deduction',
                    ]);

                    $remaining -= $take;
                }

                // Pendapatan kalau dijual dengan harga normal seluruhnya.
                $listTotal     = $basePrice * $quantity;
                // Penghematan customer karena cukai lama.
                $promoDiscount = max(0, $listTotal - $actualRevenue);
                $manualDiscount = (float) ($item['discount'] ?? 0);

                $itemTotal = round($actualRevenue - $manualDiscount, 2);
                $saleSubtotal += $itemTotal;

                SaleItem::create([
                    'sale_id'        => $sale->id,
                    'product_id'     => $item['product_id'],
                    'quantity'       => $quantity,
                    'unit_price'     => $basePrice,
                    'discount'       => $manualDiscount,
                    'promo_discount' => $promoDiscount,
                    'promo_units'    => $promoUnits,
                    'total'          => $itemTotal,
                ]);
            }

            // total_amount = subtotal item (sudah include promo cukai) − diskon transaksi.
            $finalTotal = max(0, round($saleSubtotal - (float) $validated['discount_amount'], 2));
            $sale->update(['total_amount' => $finalTotal]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully!',
                'sale' => $sale->fresh('items'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: '.$e->getMessage(),
            ], 422);
        }
    }
}
