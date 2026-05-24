<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TruncateSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        // Hapus data dari tabel yang berelasi dulu
        DB::table('batches')->truncate();
        DB::table('products')->truncate();
        DB::table('categories')->truncate();

        // Tambahkan tabel lain kalau ada (contoh: brands, users, dll)
        // DB::table('brands')->truncate();

        Schema::enableForeignKeyConstraints();

        $this->command->info('✅ Semua data lama berhasil dihapus!');
    }
}