<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PosDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create Categories
        $categories = [
            ['id' => Str::uuid(), 'name' => 'Freebase', 'slug' => 'freebase', 'is_active' => true],
            ['id' => Str::uuid(), 'name' => 'Nicotine Salt', 'slug' => 'nicotine-salt', 'is_active' => true],
            ['id' => Str::uuid(), 'name' => 'Pod Device', 'slug' => 'pod-device', 'is_active' => true],
            ['id' => Str::uuid(), 'name' => 'Mod Device', 'slug' => 'mod-device', 'is_active' => true],
            ['id' => Str::uuid(), 'name' => 'Liquid', 'slug' => 'liquid', 'is_active' => true],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }

        // Get category IDs for products
        $freebaseId = $categories[0]['id'];
        $saltId = $categories[1]['id'];
        $podId = $categories[2]['id'];
        $modId = $categories[3]['id'];
        $liquidId = $categories[4]['id'];

        // Create Products
        $products = [
            [
                'id' => Str::uuid(),
                'name' => 'Nasty Juice Slow Blow',
                'code' => 'NSB-001',
                'category_id' => $freebaseId,
                'flavor' => 'Pineapple Lime',
                'base_price' => 180000,
                'size_ml' => 60,
                'nicotine_strength' => 3,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Nasty Juice Bad Blood',
                'code' => 'NBB-002',
                'category_id' => $freebaseId,
                'flavor' => 'Blackcurrant',
                'base_price' => 180000,
                'size_ml' => 60,
                'nicotine_strength' => 3,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Drip N Vape Mango',
                'code' => 'DNV-003',
                'category_id' => $saltId,
                'flavor' => 'Sweet Mango',
                'base_price' => 120000,
                'size_ml' => 30,
                'nicotine_strength' => 35,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Drip N Vape Grape',
                'code' => 'DNV-004',
                'category_id' => $saltId,
                'flavor' => 'Purple Grape',
                'base_price' => 120000,
                'size_ml' => 30,
                'nicotine_strength' => 35,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Vaporesso XROS 3',
                'code' => 'VPX-005',
                'category_id' => $podId,
                'flavor' => 'Device Only',
                'base_price' => 350000,
                'size_ml' => 0,
                'nicotine_strength' => 0,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Voopoo Drag X',
                'code' => 'VPD-006',
                'category_id' => $modId,
                'flavor' => 'Device Only',
                'base_price' => 650000,
                'size_ml' => 0,
                'nicotine_strength' => 0,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Elf Bar BC5000',
                'code' => 'EBB-007',
                'category_id' => $podId,
                'flavor' => 'Blue Razz Ice',
                'base_price' => 150000,
                'size_ml' => 10,
                'nicotine_strength' => 50,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Geek Bar Meloso',
                'code' => 'GBM-008',
                'category_id' => $podId,
                'flavor' => 'Strawberry Banana',
                'base_price' => 160000,
                'size_ml' => 10,
                'nicotine_strength' => 50,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Just Juice Berry Blast',
                'code' => 'JJB-009',
                'category_id' => $liquidId,
                'flavor' => 'Mixed Berries',
                'base_price' => 130000,
                'size_ml' => 60,
                'nicotine_strength' => 6,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Just Juice Tropical',
                'code' => 'JJT-010',
                'category_id' => $liquidId,
                'flavor' => 'Mango Pineapple',
                'base_price' => 130000,
                'size_ml' => 60,
                'nicotine_strength' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($products as $prod) {
            Product::create($prod);
        }

        // Create Batches for each product
        foreach ($products as $prod) {
            // Batch 1 - Good stock, cukai tahun ini
            Batch::create([
                'id' => Str::uuid(),
                'product_id' => $prod['id'],
                'lot_number' => 'LOT-'.strtoupper(Str::random(6)),
                'stock_quantity' => rand(20, 100),
                'expired_date' => now()->addMonths(rand(6, 12)),
                'cost_price' => $prod['base_price'] * 0.7,
                'cukai_year' => (int) now()->format('Y'),
                'is_promo' => false,
            ]);

            // Batch 2 - Old cukai year (for promo testing)
            Batch::create([
                'id' => Str::uuid(),
                'product_id' => $prod['id'],
                'lot_number' => 'LOT-'.strtoupper(Str::random(6)),
                'stock_quantity' => rand(5, 15),
                'expired_date' => now()->subMonths(2), // Sudah lewat
                'cost_price' => $prod['base_price'] * 0.7,
                'cukai_year' => (int) now()->subYear()->format('Y'), // Tahun lalu
                'is_promo' => true,
            ]);
        }
    }
}
