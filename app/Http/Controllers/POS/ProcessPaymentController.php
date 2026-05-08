<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Batch;
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
            'tax_amount' => 'required|numeric',
            'payment_method' => 'required|string|in:cash,bank_transfer,qris,e_wallet',
        ]);

        try {
            DB::beginTransaction();

            $sale = Sale::create([
                'user_id' => auth()->id(),
                'total_amount' => $validated['total_amount'],
                'paid_amount' => $validated['paid_amount'],
                'discount_amount' => $validated['discount_amount'],
                'tax_amount' => $validated['tax_amount'],
                'payment_method' => $validated['payment_method'],
                'status' => 'completed',
            ]);

            foreach ($validated['items'] as $item) {
                $remaining = (int) $item['quantity'];
                $batches = Batch::query()
                    ->where('product_id', $item['product_id'])
                    ->where('stock_quantity', '>', 0)
                    ->orderBy('expired_date')
                    ->lockForUpdate()
                    ->get();

                if ((int) $batches->sum('stock_quantity') < $remaining) {
                    throw new \RuntimeException('Stok tidak mencukupi untuk salah satu produk.');
                }

                foreach ($batches as $batch) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $take = min($remaining, (int) $batch->stock_quantity);
                    if ($take <= 0) {
                        continue;
                    }

                    $batch->decrement('stock_quantity', $take);

                    StockMutation::create([
                        'batch_id' => $batch->id,
                        'mutation_type' => MutationType::SALE,
                        'quantity' => $take,
                        'reference_type' => \App\Models\Product::class,
                        'reference_id' => $item['product_id'],
                        'notes' => 'POS sale item deduction',
                    ]);

                    $remaining -= $take;
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'total' => $item['total'],
                ]);
            }

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
