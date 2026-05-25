<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Sale;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Idempoten: di lingkungan deploy (Render) container sering restart.
        // Cek Sale (data terakhir di-seed) agar seed partial/gagal bisa diulang,
        // tapi tidak menumpuk transaksi setelah seeding penuh berhasil.
        if (Sale::query()->exists()) {
            $this->command?->info('Database sudah ter-seed sebelumnya, seeding dilewati.');

            return;
        }

        $this->call([
            UserSeeder::class,
            StoreSettingSeeder::class,
            PosDemoSeeder::class,
            TransactionDemoSeeder::class,
        ]);
    }
}
