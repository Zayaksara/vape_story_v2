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

class PosWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $resetBeforeSeed = (bool) env('POS_WORKFLOW_RESET', false);

        $productCount = (int) env('POS_WORKFLOW_PRODUCTS', 50);

        if ($resetBeforeSeed) {
            Schema::disableForeignKeyConstraints();
            DB::table('products')->truncate();
            DB::table('categories')->truncate();
            Schema::enableForeignKeyConstraints();
        }

        // ==================== CATEGORIES ====================
        $categoriesData = [
            ['name' => 'Freebase',           'slug' => 'freebase'],
            ['name' => 'Nicotine Salt',       'slug' => 'nicotine-salt'],
            ['name' => 'Pod Device',          'slug' => 'pod-device'],
            ['name' => 'Mod Device',          'slug' => 'mod-device'],
            ['name' => 'Disposable',          'slug' => 'disposable'],
            ['name' => 'Coil & Accessories',  'slug' => 'coil-accessories'],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[] = Category::firstOrCreate(
                ['slug' => $cat['slug']],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $cat['name'],
                    'slug' => $cat['slug'],
                    'is_active' => true,
                ]
            );
        }

        // ==================== DATA POOL ====================
        $brands = [
            'Elf Bar', 'Geek Bar', 'Lost Vape', 'Vaporesso', 'Voopoo', 'OXVA', 'Uwell',
            'Nasty Juice', 'Just Juice', 'Dinner Lady', 'Vampire Vape', 'Riyal',
            'Relx', 'HQD', 'Vuse', 'Aspire', 'Smok', 'Innokin', 'Caliburn',
        ];

        $flavorBases = [
            'Mango', 'Blue Razz', 'Strawberry', 'Watermelon', 'Grape', 'Pineapple',
            'Kiwi', 'Apple', 'Banana', 'Mixed Berry', 'Lemon Mint', 'Peach Ice',
            'Cola', 'Energy Drink', 'Menthol', 'Tobacco', 'Coffee', 'Vanilla',
            'Lychee', 'Dragon Fruit', 'Blackcurrant', 'Cherry', 'Orange',
        ];

        $categoryRules = [
            $categories[0]->id => ['type' => 'liquid',     'price' => [120000, 195000], 'nic' => [3, 6],   'size' => [60, 100]],
            $categories[1]->id => ['type' => 'salt',       'price' => [95000,  165000], 'nic' => [20, 50], 'size' => [30, 50]],
            $categories[2]->id => ['type' => 'pod',        'price' => [220000, 450000], 'nic' => [0, 0],   'size' => [0]],
            $categories[3]->id => ['type' => 'mod',        'price' => [450000, 1250000], 'nic' => [0, 0],   'size' => [0]],
            $categories[4]->id => ['type' => 'disposable', 'price' => [125000, 280000], 'nic' => [35, 50], 'size' => [10, 20]],
            $categories[5]->id => ['type' => 'coil',       'price' => [45000,  120000], 'nic' => [0, 0],   'size' => [0]],
        ];

        // ==================== GENERATE 50 PRODUCTS ====================
        for ($i = 1; $i <= $productCount; $i++) {
            $category = $categories[array_rand($categories)];
            $rule = $categoryRules[$category->id];
            $brand = $brands[array_rand($brands)];
            $flavor = $flavorBases[array_rand($flavorBases)];
            $suffix = strtoupper(Str::random(2));

            if (in_array($rule['type'], ['pod', 'mod'])) {
                $name = "{$brand} ".['XROS', 'Drag', 'Argus', 'Centaurus', 'Aegis', 'Luxe', 'Target', 'T200'][rand(0, 7)]." {$suffix}";
            } elseif ($rule['type'] === 'disposable') {
                $name = "{$brand} ".['BC5000', 'Meloso', 'Crystal', 'Pulse', '6000', '10000'][rand(0, 5)]." {$suffix}";
            } else {
                $name = "{$brand} {$flavor} {$suffix}";
            }

            $basePrice = round(rand($rule['price'][0], $rule['price'][1]) / 5000) * 5000;

            $size = $rule['size'][0] === 0
                ? 0
                : $faker->randomElement([$rule['size'][0], $rule['size'][1], rand($rule['size'][0], $rule['size'][1])]);

            $nic = ($rule['nic'][0] === 0 && $rule['nic'][1] === 0)
                ? 0
                : $faker->randomElement([$rule['nic'][0], $rule['nic'][1], rand($rule['nic'][0], $rule['nic'][1])]);

            // Stock simulation untuk workflow testing
            // Mix berbagai kondisi stok: habis, tipis, normal, banyak
            $stockRoll = rand(1, 100);
            if ($stockRoll <= 20) {
                $stock = 0; // 20% habis
            } elseif ($stockRoll <= 40) {
                $stock = rand(1, 3); // 20% stok sangat tipis
            } elseif ($stockRoll <= 70) {
                $stock = rand(4, 10); // 30% stok tipis
            } else {
                $stock = rand(11, 50); // 30% stok normal sampai banyak
            }

            $product = Product::create([
                'id' => (string) Str::uuid(),
                'code' => strtoupper(substr($brand, 0, 3)).'-'.str_pad($i, 4, '0', STR_PAD_LEFT),
                'name' => $name,
                'category_id' => $category->id,
                'brand_id' => null,
                'flavor' => in_array($rule['type'], ['pod', 'mod', 'coil']) ? 'Device Only' : $flavor,
                'base_price' => $basePrice,
                'size_ml' => $size,
                'battery_mah' => in_array($rule['type'], ['pod', 'mod'])
                    ? $faker->randomElement([650, 1000, 1500, 2000, 3000])
                    : null,
                'nicotine_strength' => $nic,
                'is_active' => true,
                'description' => "Produk {$brand} varian {$flavor} generated workflow seed.",
            ]);

            // Buat batch dengan stok yang dihitung di atas
            // Device (pod/mod) idealnya 1 batch saja
            $maxBatchCount = in_array($rule['type'], ['pod', 'mod']) ? 1 : 3;
            $batchCount = rand(1, $maxBatchCount);

            for ($batchIndex = 0; $batchIndex < $batchCount; $batchIndex++) {
                // Stock per batch
                if ($stock === 0) {
                    $batchStock = 0; // Semua batch 0 stock kalau total stock 0
                } elseif ($batchIndex === $batchCount - 1) {
                    // Batch terakhir berisi sisa stock
                    $batchStock = $stock - (($batchCount - 1) * (int) ($stock / $batchCount));
                } else {
                    // Batch-batch awal berisi rata-rata
                    $batchStock = (int) ($stock / $batchCount);
                }

                // Simulasi promo dan expired date
                $isPromo = $faker->boolean(30);
                $expiredDate = $isPromo
                    ? now()->addDays(rand(-60, 45))
                    : now()->addDays(rand(60, 540));

                // Cost price simulasi (70-80% dari base price)
                $costPrice = round($basePrice * $faker->randomFloat(0.7, 0.8, 0.01));

                Batch::create([
                    'id' => (string) Str::uuid(),
                    'product_id' => $product->id,
                    'lot_number' => strtoupper(substr($brand, 0, 3)).'-'.str_pad($i, 4, '0', STR_PAD_LEFT).'-'.chr(65 + $batchIndex),
                    'cost_price' => $costPrice,
                    'stock_quantity' => $batchStock,
                ]);
            }
        }

        $this->command->info("✅ Generated {$productCount} products for workflow testing");
        $this->command->info('📊 Stock distribution:');
        $this->command->info('   - Empty (0 pcs): ~20%');
        $this->command->info('   - Very low (1-3 pcs): ~20%');
        $this->command->info('   - Low (4-10 pcs): ~30%');
        $this->command->info('   - Normal to high (11-50 pcs): ~30%');
    }
}
