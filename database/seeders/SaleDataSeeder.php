<?php

namespace Database\Seeders;

use App\Models\{Brand, Category, Product, Sale, SaleItem, User};
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SaleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil user admin (uuid)
        $adminUser = User::where('email', 'admin@vape.com')->first();

        // Buat kategori minimal
        $catNames = ['Pod', 'Liquid', 'RDA/RTA', 'Coil', 'Aksesoris'];
        foreach ($catNames as $name) {
            Category::firstOrCreate(['name' => $name]);
        }

        // Buat merk minimal
        $brandNames = ['Vaporesso', 'Smok', 'Geekvape', 'Uwell', 'Lost Vape'];
        foreach ($brandNames as $name) {
            Brand::firstOrCreate(['name' => $name]);
        }

        $categories = Category::all();
        $brands     = Brand::all();

        // Buat 30 produk (idempotent: skip by code)
        $products = [];
        foreach (range(1, 30) as $i) {
            $code = 'PROD-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $products[] = Product::updateOrCreate(
                ['code' => $code],
                [
                    'name'              => "Produk {$i}",
                    'category_id'       => $categories->random()->id,
                    'brand_id'          => $brands->random()->id,
                    'base_price'        => fake()->numberBetween(100000, 500000),
                    'nicotine_strength' => fake()->optional()->numberBetween(0, 50),
                    'flavor'            => fake()->optional()->word,
                    'size_ml'           => fake()->optional()->numberBetween(10, 100),
                    'battery_mah'       => fake()->optional()->numberBetween(800, 2000),
                    'coil_type'         => fake()->optional()->word,
                    'pod_type'          => fake()->optional()->word,
                    'resistance_ohm'    => fake()->optional()->numberBetween(0.5, 2),
                    'description'       => fake()->optional()->sentence,
                    'is_active'         => true,
                ]
            );
        }

        // Buat batch untuk setiap produk (required by POS ProcessPayment)
        foreach ($products as $product) {
            \App\Models\Batch::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'lot_number' => 'LOT-'.strtoupper(fake()->bothify('??###')), // unique enough rand
                ],
                [
                    'cost_price'     => fake()->numberBetween(50000, 300000),
                    'stock_quantity' => fake()->numberBetween(50, 200),
                    'expired_date'   => now()->addMonths(rand(6, 24)),
                    'cukai_year'     => now()->year + rand(0, 2),
                    'is_promo'       => fake()->boolean(20),
                ]
            );
        }

        // Buat 100 transaksi completed (7 hari terakhir)
        $now   = Carbon::now();
        $start = $now->copy()->subDays(7);

        foreach (range(1, 100) as $_) {
            $saleDate = Carbon::createFromTimestamp(
                rand($start->timestamp, $now->timestamp)
            );

            $sale = Sale::create([
                'user_id'        => $adminUser?->id ?? 1,
                'total_amount'   => 0,
                'paid_amount'    => 0,
                'discount_amount'=> 0,
                'tax_amount'     => 0,
                'payment_method' => fake()->randomElement(['cash', 'e_wallet', 'bank_transfer', 'qris']),
                'status'         => 'completed',
                'created_at'     => $saleDate,
                'updated_at'     => $saleDate,
            ]);

            // 1-6 item per transaksi
            $items = collect($products)->random(rand(1, 6));
            $total = 0;
            foreach ($items as $product) {
                $qty      = rand(1, 3);
                $unitPrice = $product->price;
                $lineTotal = $unitPrice * $qty;

                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $product->id,
                    'quantity'   => $qty,
                    'unit_price' => $unitPrice,
                    'discount'   => 0,
                    'total'      => $lineTotal,
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate,
                ]);
                $total += $lineTotal;
            }

            $sale->update([
                'total_amount'  => $total,
                'paid_amount'   => $total,
                'discount_amount' => 0,
            ]);
        }
    }
}
