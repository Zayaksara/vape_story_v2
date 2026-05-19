<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $resetBeforeSeed = (bool) env('POS_SEED_RESET', false);

        // Scale data using env:
        // POS_SEED_MULTIPLIER=2 => 2x product count
        // POS_SEED_PRODUCTS=120 => explicit total products
        $multiplier = max(1, (int) env('POS_SEED_MULTIPLIER', 1));
        $productCount = (int) env('POS_SEED_PRODUCTS', 60 * $multiplier);
        $maxBatchesPerProduct = max(2, (int) env('POS_SEED_MAX_BATCH', 4));

        if ($resetBeforeSeed) {
            Schema::disableForeignKeyConstraints();
            DB::table('batches')->truncate();
            DB::table('products')->truncate();
            DB::table('categories')->truncate();
            Schema::enableForeignKeyConstraints();
        }

        // ==================== CATEGORIES ====================
        $categoriesData = [
            ['name' => 'Freebase',      'slug' => 'freebase'],
            ['name' => 'Nicotine Salt', 'slug' => 'nicotine-salt'],
            ['name' => 'Pod Device',    'slug' => 'pod-device'],
            ['name' => 'Mod Device',    'slug' => 'mod-device'],
            ['name' => 'Disposable',    'slug' => 'disposable'],
            ['name' => 'Coil & Accessories', 'slug' => 'coil-accessories'],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[] = Category::updateOrCreate([
                'slug' => $cat['slug'],
            ], [
                'id' => (string) Str::uuid(),
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'is_active' => true,
            ]);
        }

        // ==================== DATA POOL UNTUK RANDOM ====================
        $brands = [
            'Elf Bar', 'Geek Bar', 'Lost Vape', 'Vaporesso', 'Voopoo', 'OXVA', 'Uwell',
            'Nasty Juice', 'Just Juice', 'Dinner Lady', 'Vampire Vape', 'Riyal',
            'Relx', 'HQD', 'Vuse', 'Aspire', 'Smok', 'Innokin', 'Caliburn', 'Geekvape',
        ];

        $flavorBases = [
            'Mango', 'Blue Razz', 'Strawberry', 'Watermelon', 'Grape', 'Pineapple',
            'Kiwi', 'Apple', 'Banana', 'Mixed Berry', 'Lemon Mint', 'Peach Ice',
            'Cola', 'Energy Drink', 'Menthol', 'Tobacco', 'Coffee', 'Vanilla',
            'Lychee', 'Dragon Fruit', 'Blackcurrant', 'Cherry', 'Orange',
        ];

        // Mapping kategori dengan aturan harga, nicotine, size
        $categoryRules = [
            $categories[0]->id => ['type' => 'liquid',   'price' => [120000, 195000], 'nic' => [3, 6],     'size' => [60, 100]],
            $categories[1]->id => ['type' => 'salt',     'price' => [95000,  165000], 'nic' => [20, 50],   'size' => [30, 50]],
            $categories[2]->id => ['type' => 'pod',      'price' => [220000, 450000], 'nic' => [0, 0],     'size' => [0]],
            $categories[3]->id => ['type' => 'mod',      'price' => [450000, 1250000], 'nic' => [0, 0],     'size' => [0]],
            $categories[4]->id => ['type' => 'disposable', 'price' => [125000, 280000], 'nic' => [35, 50],   'size' => [10, 20]],
            $categories[5]->id => ['type' => 'coil',     'price' => [45000,  120000], 'nic' => [0, 0],     'size' => [0]],
        ];

        $products = [];

        // ==================== GENERATE RANDOM PRODUCTS ====================
        for ($i = 1; $i <= $productCount; $i++) {
            $category = $categories[array_rand($categories)];
            $rule = $categoryRules[$category->id];
            $brand = $brands[array_rand($brands)];
            $flavor = $flavorBases[array_rand($flavorBases)];
            $suffix = strtoupper(Str::random(2));

            // Nama produk
            if (in_array($rule['type'], ['pod', 'mod'])) {
                $name = "{$brand} ".['XROS', 'Drag', 'Argus', 'Centaurus', 'Aegis', 'Luxe', 'Target', 'T200'][rand(0, 7)]." {$suffix}";
            } elseif ($rule['type'] === 'disposable') {
                $name = "{$brand} ".['BC5000', 'Meloso', 'Crystal', 'Pulse', '6000', '10000'][rand(0, 5)]." {$suffix}";
            } else {
                $name = "{$brand} {$flavor} {$suffix}";
            }

            // Harga random dalam range
            $basePrice = rand($rule['price'][0], $rule['price'][1]);
            $basePrice = round($basePrice / 5000) * 5000; // rapihkan ke kelipatan 5000

            $size = $rule['size'][0] === 0
                ? 0
                : $faker->randomElement([$rule['size'][0], $rule['size'][1], rand($rule['size'][0], $rule['size'][1])]);

            $nic = $rule['nic'][0] === 0 && $rule['nic'][1] === 0
                ? 0
                : $faker->randomElement([$rule['nic'][0], $rule['nic'][1], rand($rule['nic'][0], $rule['nic'][1])]);

            $product = Product::create([
                'id' => (string) Str::uuid(),
                'code' => strtoupper(substr($brand, 0, 3)).'-'.str_pad($i, 4, '0', STR_PAD_LEFT),
                'name' => $name,
                'category_id' => $category->id,
                'brand_id' => null,           // kalau ada tabel brand nanti diisi
                'flavor' => in_array($rule['type'], ['pod', 'mod', 'coil']) ? 'Device Only' : $flavor,
                'base_price' => $basePrice,
                'size_ml' => $size,
                'battery_mah' => in_array($rule['type'], ['pod', 'mod']) ? $faker->randomElement([650, 1000, 1500, 2000, 3000]) : null,
                'nicotine_strength' => $nic,
                'is_active' => true,
                'description' => "Produk {$brand} varian {$flavor} generated seed.",
            ]);

            $products[] = $product;
        }

        // ==================== BUAT BATCH UNTUK SETIAP PRODUK ====================
        foreach ($products as $product) {
            $batchCount = rand(2, $maxBatchesPerProduct);

            for ($batchIndex = 1; $batchIndex <= $batchCount; $batchIndex++) {
                // Distribusi stok:
                // 10% habis, 25% stok tipis, sisanya stok normal
                $stockMode = rand(1, 100);
                if ($stockMode <= 10) {
                    $stockQty = 0;
                } elseif ($stockMode <= 35) {
                    $stockQty = rand(1, 20);
                } else {
                    $stockQty = rand(21, 180);
                }

                $isPromo = $faker->boolean(30);
                $expiredDate = $isPromo
                    ? now()->addDays(rand(-60, 45))
                    : now()->addDays(rand(60, 540));

                Batch::create([
                    'id' => (string) Str::uuid(),
                    'product_id' => $product->id,
                    'lot_number' => 'LOT-'.strtoupper(Str::random(8)),
                    'stock_quantity' => $stockQty,
                    'expired_date' => $expiredDate,
                    'cost_price' => (int) round($product->base_price * $faker->randomFloat(2, 0.58, 0.78)),
                    'cukai_year' => (int) ($isPromo ? now()->subYear()->format('Y') : now()->format('Y')),
                    'is_promo' => $isPromo,
                ]);
            }
        }

        $this->command->info('✅ Seed POS random selesai: '.$productCount.' produk, multiplier '.$multiplier.'x');
        $this->command->info('Tip env: POS_SEED_MULTIPLIER=2, POS_SEED_PRODUCTS=120, POS_SEED_MAX_BATCH=5, POS_SEED_RESET=true');
    }
}
