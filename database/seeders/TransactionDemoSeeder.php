<?php

namespace Database\Seeders;

use App\Enums\MutationType;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemBatch;
use App\Models\StockMutation;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Membuat ~90 hari riwayat transaksi POS agar dashboard & laporan terlihat penuh.
 *
 * Meniru logika FIFO di ProcessPaymentController:
 *  - Batch promo (cukai lama) dikonsumsi lebih dulu, lalu urut created_at.
 *  - Mencatat sale_item_batches + stock_mutations dan mengurangi stok batch.
 *
 * Konfigurasi via env:
 *  - TX_SEED_DAYS=90          jumlah hari ke belakang
 *  - TX_SEED_MIN_PER_DAY=4    minimal transaksi per hari
 *  - TX_SEED_MAX_PER_DAY=18   maksimal transaksi per hari
 */
class TransactionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        $days       = max(1, (int) env('TX_SEED_DAYS', 90));
        $minPerDay  = max(1, (int) env('TX_SEED_MIN_PER_DAY', 4));
        $maxPerDay  = max($minPerDay, (int) env('TX_SEED_MAX_PER_DAY', 18));

        $cashiers = User::whereIn('role', [UserRole::CASHIER, UserRole::ADMIN])->get();
        if ($cashiers->isEmpty()) {
            $this->command->warn('TransactionDemoSeeder dilewati: tidak ada user kasir/admin. Jalankan UserSeeder dulu.');

            return;
        }

        $products = Product::where('is_active', true)->get();
        if ($products->isEmpty()) {
            $this->command->warn('TransactionDemoSeeder dilewati: belum ada produk. Jalankan PosDemoSeeder dulu.');

            return;
        }

        $paymentMethods = ['cash', 'cash', 'cash', 'qris', 'qris', 'bank_transfer', 'e_wallet'];

        // Voucher contoh yang sesekali dipakai (diskon transaksi).
        $vouchers = [
            ['code' => 'HEMAT10K',  'label' => 'Potongan Rp10.000',  'amount' => 10000],
            ['code' => 'NGORDER25', 'label' => 'Potongan Rp25.000',  'amount' => 25000],
            ['code' => 'MEMBER5',   'label' => 'Diskon Member 5rb',  'amount' => 5000],
        ];

        $totalSales = 0;
        $totalSkipped = 0;
        $start = Carbon::today()->subDays($days - 1);

        for ($d = 0; $d < $days; $d++) {
            $date = (clone $start)->addDays($d);

            // Akhir pekan lebih ramai.
            $isWeekend = in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY], true);
            $count = rand($minPerDay, $maxPerDay) + ($isWeekend ? rand(2, 6) : 0);

            for ($s = 0; $s < $count; $s++) {
                // Jam buka toko 10:00 - 21:59.
                $createdAt = (clone $date)
                    ->setTime(rand(10, 21), rand(0, 59), rand(0, 59));

                $cashier = $cashiers->random();

                $itemCount = $faker->randomElement([1, 1, 2, 2, 2, 3, 3, 4]);
                $picked = $products->random(min($itemCount, $products->count()));
                if (! $picked instanceof \Illuminate\Support\Collection) {
                    $picked = collect([$picked]);
                }

                $built = [];      // item siap simpan
                $saleSubtotal = 0.0;

                foreach ($picked as $product) {
                    $quantity  = $faker->randomElement([1, 1, 1, 2, 2, 3]);
                    $basePrice = (float) $product->base_price;
                    $remaining = $quantity;

                    $batches = Batch::query()
                        ->where('product_id', $product->id)
                        ->where('stock_quantity', '>', 0)
                        ->orderByDesc('is_promo')
                        ->orderBy('created_at')
                        ->get();

                    if ((int) $batches->sum('stock_quantity') < $remaining) {
                        continue; // stok tidak cukup, lewati produk ini
                    }

                    $actualRevenue = 0.0;
                    $promoUnits    = 0;
                    $allocations   = [];

                    foreach ($batches as $batch) {
                        if ($remaining <= 0) {
                            break;
                        }
                        $take = min($remaining, (int) $batch->stock_quantity);
                        if ($take <= 0) {
                            continue;
                        }

                        $isPromoBatch = (bool) $batch->is_promo && $batch->promo_price !== null;
                        $unitPrice = $isPromoBatch ? (float) $batch->promo_price : $basePrice;
                        $unitCost  = (float) ($batch->cost_price ?? 0);

                        $actualRevenue += $unitPrice * $take;
                        if ($isPromoBatch) {
                            $promoUnits += $take;
                        }

                        $allocations[] = [
                            'batch'      => $batch,
                            'quantity'   => $take,
                            'unit_cost'  => $unitCost,
                            'unit_price' => $unitPrice,
                            'is_promo'   => $isPromoBatch,
                        ];

                        $batch->decrement('stock_quantity', $take);
                        $remaining -= $take;
                    }

                    if (empty($allocations)) {
                        continue;
                    }

                    $listTotal     = $basePrice * $quantity;
                    $promoDiscount = max(0, $listTotal - $actualRevenue);

                    // Sesekali diskon manual per item.
                    $manualDiscount = $faker->boolean(12)
                        ? (float) (rand(1, 5) * 5000)
                        : 0.0;
                    $manualDiscount = min($manualDiscount, $actualRevenue);

                    $itemTotal = round($actualRevenue - $manualDiscount, 2);
                    $saleSubtotal += $itemTotal;

                    $built[] = [
                        'product_id'     => $product->id,
                        'quantity'       => $quantity,
                        'unit_price'     => $basePrice,
                        'discount'       => $manualDiscount,
                        'promo_discount' => $promoDiscount,
                        'promo_units'    => $promoUnits,
                        'total'          => $itemTotal,
                        'allocations'    => $allocations,
                    ];
                }

                if (empty($built)) {
                    $totalSkipped++;
                    continue;
                }

                // Diskon transaksi (voucher) sesekali.
                $useVoucher = $faker->boolean(18);
                $voucher = $useVoucher ? $faker->randomElement($vouchers) : null;
                $discountAmount = $voucher ? min((float) $voucher['amount'], $saleSubtotal) : 0.0;

                $finalTotal = max(0, round($saleSubtotal - $discountAmount, 2));

                // Pembayaran.
                $method = $faker->randomElement($paymentMethods);
                if ($method === 'cash') {
                    // Bulatkan ke atas kelipatan 5rb sebagai uang dibayar.
                    $paid = (float) (ceil($finalTotal / 5000) * 5000);
                    if ($paid < $finalTotal) {
                        $paid = $finalTotal;
                    }
                } else {
                    $paid = $finalTotal;
                }

                $sale = new Sale([
                    'user_id'         => $cashier->id,
                    'total_amount'    => $finalTotal,
                    'paid_amount'     => $paid,
                    'discount_amount' => $discountAmount,
                    'discount_code'   => $voucher['code'] ?? null,
                    'discount_label'  => $voucher['label'] ?? null,
                    'tax_amount'      => 0,
                    'payment_method'  => $method,
                    'status'          => 'completed',
                ]);
                $sale->created_at = $createdAt;
                $sale->updated_at = $createdAt;
                $sale->save();

                foreach ($built as $row) {
                    $saleItem = new SaleItem([
                        'sale_id'        => $sale->id,
                        'product_id'     => $row['product_id'],
                        'quantity'       => $row['quantity'],
                        'unit_price'     => $row['unit_price'],
                        'discount'       => $row['discount'],
                        'promo_discount' => $row['promo_discount'],
                        'promo_units'    => $row['promo_units'],
                        'total'          => $row['total'],
                    ]);
                    $saleItem->created_at = $createdAt;
                    $saleItem->updated_at = $createdAt;
                    $saleItem->save();

                    foreach ($row['allocations'] as $alloc) {
                        $sib = new SaleItemBatch([
                            'sale_item_id' => $saleItem->id,
                            'batch_id'     => $alloc['batch']->id,
                            'quantity'     => $alloc['quantity'],
                            'unit_cost'    => $alloc['unit_cost'],
                            'unit_price'   => $alloc['unit_price'],
                            'is_promo'     => $alloc['is_promo'],
                        ]);
                        $sib->created_at = $createdAt;
                        $sib->updated_at = $createdAt;
                        $sib->save();

                        $mutation = new StockMutation([
                            'batch_id'       => $alloc['batch']->id,
                            'mutation_type'  => MutationType::SALE,
                            'quantity'       => $alloc['quantity'],
                            'reference_type' => Product::class,
                            'reference_id'   => $row['product_id'],
                            'notes'          => $alloc['is_promo']
                                ? 'Seed: POS sale (cukai lama)'
                                : 'Seed: POS sale',
                        ]);
                        $mutation->id = (string) Str::uuid();
                        $mutation->created_at = $createdAt;
                        $mutation->updated_at = $createdAt;
                        $mutation->save();
                    }
                }

                $totalSales++;
            }
        }

        $this->command->info("✅ Seed transaksi selesai: {$totalSales} transaksi dalam {$days} hari (dilewati {$totalSkipped} karena stok habis).");
    }
}
