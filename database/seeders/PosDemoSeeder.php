<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Brand;
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

        $multiplier = max(1, (int) env('POS_SEED_MULTIPLIER', 1));
        $productCount = (int) env('POS_SEED_PRODUCTS', 60 * $multiplier);
        $maxBatchesPerProduct = max(2, (int) env('POS_SEED_MAX_BATCH', 4));

        if ($resetBeforeSeed) {
            Schema::disableForeignKeyConstraints();
            DB::table('sale_item_batches')->truncate();
            DB::table('batches')->truncate();
            DB::table('products')->truncate();
            DB::table('brands')->truncate();
            DB::table('categories')->truncate();
            Schema::enableForeignKeyConstraints();
        }

        // ==================== CATEGORIES ====================
        $categoriesData = [
            ['name' => 'Freebase',           'slug' => 'freebase'],
            ['name' => 'Nicotine Salt',      'slug' => 'nicotine-salt'],
            ['name' => 'Pod Device',         'slug' => 'pod-device'],
            ['name' => 'Mod Device',         'slug' => 'mod-device'],
            ['name' => 'Disposable',         'slug' => 'disposable'],
            ['name' => 'Coil & Accessories', 'slug' => 'coil-accessories'],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[] = Category::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $cat['name'],
                    'slug' => $cat['slug'],
                    'is_active' => true,
                ]
            );
        }

        // ==================== BRANDS ====================
        $brandNames = [
            'Elf Bar', 'Geek Bar', 'Lost Vape', 'Vaporesso', 'Voopoo', 'OXVA', 'Uwell',
            'Nasty Juice', 'Just Juice', 'Dinner Lady', 'Vampire Vape', 'Riyal',
            'Relx', 'HQD', 'Vuse', 'Aspire', 'Smok', 'Innokin', 'Caliburn', 'Geekvape',
        ];

        $brands = [];
        foreach ($brandNames as $name) {
            $brands[] = Brand::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'is_active' => true,
                ]
            );
        }

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
            $categories[3]->id => ['type' => 'mod',        'price' => [450000, 1250000],'nic' => [0, 0],   'size' => [0]],
            $categories[4]->id => ['type' => 'disposable', 'price' => [125000, 280000], 'nic' => [35, 50], 'size' => [10, 20]],
            $categories[5]->id => ['type' => 'coil',       'price' => [45000,  120000], 'nic' => [0, 0],   'size' => [0]],
        ];

        $currentYear = (int) now()->format('Y');
        $previousYear = $currentYear - 1;

        $products = [];

        for ($i = 1; $i <= $productCount; $i++) {
            $category = $categories[array_rand($categories)];
            $rule = $categoryRules[$category->id];
            $brand = $brands[array_rand($brands)];
            $flavor = $flavorBases[array_rand($flavorBases)];
            $suffix = strtoupper(Str::random(2));

            if (in_array($rule['type'], ['pod', 'mod'])) {
                $name = "{$brand->name} ".['XROS', 'Drag', 'Argus', 'Centaurus', 'Aegis', 'Luxe', 'Target', 'T200'][rand(0, 7)]." {$suffix}";
            } elseif ($rule['type'] === 'disposable') {
                $name = "{$brand->name} ".['BC5000', 'Meloso', 'Crystal', 'Pulse', '6000', '10000'][rand(0, 5)]." {$suffix}";
            } else {
                $name = "{$brand->name} {$flavor} {$suffix}";
            }

            $basePrice = (int) round(rand($rule['price'][0], $rule['price'][1]) / 5000) * 5000;

            $size = $rule['size'][0] === 0
                ? 0
                : $faker->randomElement([$rule['size'][0], $rule['size'][1], rand($rule['size'][0], $rule['size'][1])]);

            $nic = ($rule['nic'][0] === 0 && $rule['nic'][1] === 0)
                ? 0
                : $faker->randomElement([$rule['nic'][0], $rule['nic'][1], rand($rule['nic'][0], $rule['nic'][1])]);

            $product = Product::create([
                'id'                => (string) Str::uuid(),
                'code'              => strtoupper(Str::slug($brand->name, '')).'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'name'              => $name,
                'category_id'       => $category->id,
                'brand_id'          => $brand->id,
                'flavor'            => in_array($rule['type'], ['pod', 'mod', 'coil']) ? 'Device Only' : $flavor,
                'base_price'        => $basePrice,
                'size_ml'           => $size,
                'nicotine_strength' => $nic,
                'is_active'         => true,
                'min_stock'         => $faker->randomElement([3, 5, 10]),
            ]);

            $products[] = $product;
        }

        // ==================== BATCHES (FIFO + Cukai variations) ====================
        // Setiap produk dapat 2..N batch:
        //  - minimal 1 batch cukai TAHUN INI (normal, non-promo)
        //  - kadang ada batch cukai TAHUN LALU (is_promo=true, ada promo_price)
        //  - distribusi stok: 10% habis, 25% tipis, 65% normal
        foreach ($products as $product) {
            $batchCount = rand(2, $maxBatchesPerProduct);

            // Pastikan minimal 1 batch tahun ini (normal), sisanya bisa campur.
            for ($batchIndex = 1; $batchIndex <= $batchCount; $batchIndex++) {
                $stockMode = rand(1, 100);
                if ($stockMode <= 10) {
                    $stockQty = 0;
                } elseif ($stockMode <= 35) {
                    $stockQty = rand(1, 20);
                } else {
                    $stockQty = rand(21, 180);
                }

                // Batch pertama selalu non-promo + tahun ini agar produk pasti bisa terjual normal.
                $isPromo = $batchIndex === 1 ? false : $faker->boolean(35);

                $costPrice = (int) round($product->base_price * $faker->randomFloat(2, 0.58, 0.78));

                // Promo batch dijual 10-18% lebih murah dari base price.
                $promoPrice = $isPromo
                    ? (int) round($product->base_price * $faker->randomFloat(2, 0.82, 0.90) / 1000) * 1000
                    : null;

                Batch::create([
                    'id'             => (string) Str::uuid(),
                    'product_id'     => $product->id,
                    'lot_number'     => 'LOT-'.strtoupper(Str::random(8)),
                    'stock_quantity' => $stockQty,
                    'cost_price'     => $costPrice,
                    'promo_price'    => $promoPrice,
                    'cukai_year'     => $isPromo ? $previousYear : $currentYear,
                    'is_promo'       => $isPromo,
                ]);
            }
        }

        $this->command->info('✅ Seed POS selesai: '.count($brands).' brand, '.count($categories).' kategori, '.$productCount.' produk');
        $this->command->info('Tip env: POS_SEED_MULTIPLIER=2, POS_SEED_PRODUCTS=120, POS_SEED_MAX_BATCH=5, POS_SEED_RESET=true');
    }
}
