<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Aktifkan ekstensi trigram bawaan PostgreSQL untuk fuzzy similarity().
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // Index GIN trigram di kolom yang dipakai untuk pencarian produk —
        // similarity() dan ILIKE jadi cepat walau jutaan baris.
        DB::statement('CREATE INDEX IF NOT EXISTS products_name_trgm_idx
                       ON products USING gin (name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_code_trgm_idx
                       ON products USING gin (code gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_flavor_trgm_idx
                       ON products USING gin (flavor gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS brands_name_trgm_idx
                       ON brands USING gin (name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS categories_name_trgm_idx
                       ON categories USING gin (name gin_trgm_ops)');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS products_name_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS products_code_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS products_flavor_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS brands_name_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS categories_name_trgm_idx');
        // Ekstensi pg_trgm sengaja TIDAK didrop — bisa dipakai modul lain.
    }
};
