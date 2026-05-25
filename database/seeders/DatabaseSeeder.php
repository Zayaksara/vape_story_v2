<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Idempoten: di lingkungan deploy (Render) container sering restart.
        // Lewati seed bila DB sudah terisi agar data tidak menumpuk/rusak.
        if (User::query()->exists()) {
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
