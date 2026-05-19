<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the foreign key constraint first (handle both possible names)
        try {
            DB::statement('ALTER TABLE sessions DROP CONSTRAINT IF EXISTS sessions_user_id_foreign');
        } catch (Exception $e) {
            // Try alternative naming
            try {
                DB::statement('ALTER TABLE sessions DROP CONSTRAINT IF EXISTS sessions_user_id_foreign');
            } catch (Exception $e2) {
                // Continue anyway
            }
        }

        // For PostgreSQL, we need to change the column type from bigint to uuid
        // Drop the existing user_id column and recreate as uuid
        DB::statement('ALTER TABLE sessions DROP COLUMN IF EXISTS user_id');

        Schema::table('sessions', function (Blueprint $table) {
            // Add uuid column to match users.id
            $table->uuid('user_id')->nullable()->index()->after('id');

            // Add foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE sessions DROP COLUMN IF EXISTS user_id');

        Schema::table('sessions', function (Blueprint $table) {
            // Re-add as foreignId (unsignedBigInteger)
            $table->foreignId('user_id')->nullable()->index()->after('id');
        });
    }
};
