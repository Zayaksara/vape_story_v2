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
    // ==================== SMART THRESHOLD LOGIC ====================
    // Produk vape banyak yang stoknya kecil dari awal (device 1-2 pcs).
    // Threshold adaptif berdasarkan initial_stock agar alert tidak noise.
    private function getMinThreshold(int $initialStock): int
    {
        if ($initialStock <= 2) return 0; // alert hanya saat habis
        if ($initialStock <= 5) return 1;
        return 2;
    }

    // ==================== STOCK DISTRIBUTION PER TYPE ====================
    // Device (pod/mod): stok kecil, realistis 1-3 pcs per item
    // Disposable: sedang, 1-10 pcs
    // Liquid/Salt: bisa banyak, 5-50 pcs
    // Coil/Accessories: sedang, 3-20 pcs
    private function getInitialStock(string $type, \Faker\Generator $faker): int
    {
        return match($type) {
            'pod', 'mod'  => $faker->randomElement([1, 1, 1, 2, 2, 3]),   // mayoritas 1
            'disposable'  => rand(1, 10),
            'liquid','salt'=> rand(5, 50),
            'coil'        => rand(3, 20),
            default       => rand(1, 10),
        };
    }

    // ==================== CURRENT STOCK SIMULATION ====================
    // Simulasi kondisi toko: ada yang habis, hampir habis, dan normal
    // Disesuaikan per type agar realistis
    private function getCurrentStock(int $initialStock, string $type, \Faker\Generator $faker): int
    {
        $roll = rand(1, 100);

        // Device: 20% habis, 40% tinggal 1, sisanya masih ada
        if (in_array($type, ['pod', 'mod'])) {
            if ($roll <= 20) return 0;
            if ($roll <= 60) return 1;
            return $initialStock;
        }

        // Semua type lain: 10% habis, 25% stok tipis, sisanya normal
        if ($roll <= 10) return 0;
        if ($roll <= 35) return (int) max(1, round($initialStock * $faker->randomFloat(2, 0.1, 0.3)));
        return (int) round($initialStock * $faker->randomFloat(2, 0.5, 1.0));
    }

    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $resetBeforeSeed = (bool) env('POS_SEED_RESET', false);

        $multiplier   = max(1, (int) env('POS_SEED_MULTIPLIER', 1));
        $productCount = (int) env('POS_SEED_PRODUCTS', 60 * $multiplier);
        // Untuk device, batch idealnya 1. Untuk liquid/coil bisa lebih.
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
            ['name' => 'Freebase',           'slug' => 'freebase'],
            ['name' => 'Nicotine Salt',       'slug' => 'nicotine-salt'],
            ['name' => 'Pod Device',          'slug' => 'pod-device'],
            ['name' => 'Mod Device',          'slug' => 'mod-device'],
            ['name' => 'Disposable',          'slug' => 'disposable'],
            ['name' => 'Coil & Accessories',  'slug' => 'coil-accessories'],
        ];
        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[] = Category::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'id'        => (string) Str::uuid(),
                    'name'      => $cat['name'],
                    'slug'      => $cat['slug'],
                    'is_active' => true,
                ]
            );
        }

        // ==================== DATA POOL ====================
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

        $categoryRules = [
            $categories[0]->id => ['type' => 'liquid',     'price' => [120000, 195000], 'nic' => [3,6],   'size' => [60,100]],
            $categories[1]->id => ['type' => 'salt',       'price' => [95000,  165000], 'nic' => [20,50], 'size' => [30,50]],
            $categories[2]->id => ['type' => 'pod',        'price' => [220000, 450000], 'nic' => [0,0],   'size' => [0]],
            $categories[3]->id => ['type' => 'mod',        'price' => [450000, 1250000],'nic' => [0,0],   'size' => [0]],
            $categories[4]->id => ['type' => 'disposable', 'price' => [125000, 280000], 'nic' => [35,50], 'size' => [10,20]],
            $categories[5]->id => ['type' => 'coil',       'price' => [45000,  120000], 'nic' => [0,0],   'size' => [0]],
        ];

        $products = [];

        // ==================== GENERATE PRODUCTS ====================
        for ($i = 1; $i <= $productCount; $i++) {
            $category = $categories[array_rand($categories)];
            $rule     = $categoryRules[$category->id];
            $brand    = $brands[array_rand($brands)];
            $flavor   = $flavorBases[array_rand($flavorBases)];
            $suffix   = strtoupper(Str::random(2));

            if (in_array($rule['type'], ['pod', 'mod'])) {
                $name = "{$brand} " . ['XROS','Drag','Argus','Centaurus','Aegis','Luxe','Target','T200'][rand(0,7)] . " {$suffix}";
            } elseif ($rule['type'] === 'disposable') {
                $name = "{$brand} " . ['BC5000','Meloso','Crystal','Pulse','6000','10000'][rand(0,5)] . " {$suffix}";
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

            // ── Smart Threshold ──────────────────────────────────────────
            // Stock langsung di batch tanpa initial_stock & minimum_stock
            // Sesuaikan dengan migration schema yang ada
            $initialStock = $this->getInitialStock($rule['type'], $faker);
            $minimumStock = $this->getMinThreshold($initialStock);
            // ─────────────────────────────────────────────────────────────

            $product = Product::create([
                'id'                => (string) Str::uuid(),
                'code'              => strtoupper(substr($brand, 0, 3)) . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'name'              => $name,
                'category_id'       => $category->id,
                'brand_id'          => null,
                'flavor'            => in_array($rule['type'], ['pod','mod','coil']) ? 'Device Only' : $flavor,
                'base_price'        => $basePrice,
                'size_ml'           => $size,
                'battery_mah'       => in_array($rule['type'], ['pod', 'mod'])
                    ? $faker->randomElement([650, 1000, 1500, 2000, 3000])
                    : null,
                'nicotine_strength' => $nic,
                'initial_stock'     => $initialStock,   // basis smart threshold
                'minimum_stock'     => $minimumStock,   // dihitung dari initial_stock
                'is_active'         => true,
                'description'       => "Produk {$brand} varian {$flavor} generated seed.",
            ]);

            $products[] = ['model' => $product, 'type' => $rule['type'], 'initialStock' => $initialStock];
        }

        // ==================== GENERATE BATCHES ====================
        foreach ($products as $item) {
            $product      = $item['model'];
            $type         = $item['type'];
            $initialStock = $item['initialStock'];

            // Device idealnya 1 batch saja, produk lain bisa lebih
            $maxBatch   = in_array($type, ['pod', 'mod']) ? 1 : $maxBatchesPerProduct;
            $batchCount = rand(1, $maxBatch);

            for ($batchIndex = 1; $batchIndex <= $batchCount; $batchIndex++) {
                // Stock per batch disesuaikan type & initial_stock produk
                $stockQty = $this->getCurrentStock($initialStock, $type, $faker);

                $isPromo     = $faker->boolean(30);
                $expiredDate = $isPromo
                    ? now()->addDays(rand(-60, 45))
                    : now()->addDays(rand(60, 540));

                Batch::create([
                    'id'             => (string) Str::uuid(),
                    'product_id'     => $product->id,
                    'lot_number'     => 'LOT-' . strtoupper(Str::random(8)),
                    'stock_quantity' => $stockQty,
                    'expired_date'   => $expiredDate,
                    'cost_price'     => (int) round($product->base_price * $faker->randomFloat(2, 0.58, 0.78)),
                    'cukai_year'     => (int) ($isPromo ? now()->subYear()->format('Y') : now()->format('Y')),
                    'is_promo'       => $isPromo,
                ]);
            }
        }

        $this->command->info('✅ Seed POS selesai: ' . $productCount . ' produk, multiplier ' . $multiplier . 'x');
        $this->command->info('Tip env: POS_SEED_MULTIPLIER=2 | POS_SEED_PRODUCTS=120 | POS_SEED_MAX_BATCH=5 | POS_SEED_RESET=true');
    }
}