<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP POLICY IF EXISTS "Authenticated users can read active products" ON products');
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                CREATE POLICY "Authenticated users can read products"
                ON products FOR SELECT TO authenticated
                USING (true)
                SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP POLICY IF EXISTS "Authenticated users can read products" ON products');
        }

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        DB::statement('CREATE INDEX IF NOT EXISTS idx_products_active_stock ON products (is_active, stock_quantity)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_products_category_active ON products (category_id, is_active)');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                CREATE POLICY "Authenticated users can read active products"
                ON products FOR SELECT TO authenticated
                USING (is_active = true)
                SQL);
        }
    }
};
