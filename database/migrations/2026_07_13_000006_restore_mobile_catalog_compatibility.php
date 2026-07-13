<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->text('description')->nullable();
        });

        Schema::table('home_banners', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            DROP POLICY IF EXISTS "Authenticated users can read products" ON products;
            DROP POLICY IF EXISTS "Anyone can read products" ON products;
            CREATE POLICY "Anyone can read products"
                ON products FOR SELECT TO anon, authenticated
                USING (is_active = true);

            DROP POLICY IF EXISTS "Authenticated users can read active categories" ON categories;
            DROP POLICY IF EXISTS "Anyone can read active categories" ON categories;
            CREATE POLICY "Anyone can read active categories"
                ON categories FOR SELECT TO anon, authenticated
                USING (is_active = true);

            GRANT SELECT ON products, categories, home_banners TO anon, authenticated;
            NOTIFY pgrst, 'reload schema';
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                DROP POLICY IF EXISTS "Anyone can read products" ON products;
                CREATE POLICY "Authenticated users can read products"
                    ON products FOR SELECT TO authenticated
                    USING (true);

                DROP POLICY IF EXISTS "Anyone can read active categories" ON categories;
                CREATE POLICY "Authenticated users can read active categories"
                    ON categories FOR SELECT TO authenticated
                    USING (is_active = true);

                NOTIFY pgrst, 'reload schema';
                SQL);
        }

        Schema::table('home_banners', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'starts_at', 'ends_at']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
