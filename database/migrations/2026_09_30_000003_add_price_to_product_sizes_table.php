<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_sizes') && ! Schema::hasColumn('product_sizes', 'price')) {
            Schema::table('product_sizes', function (Blueprint $table) {
                $table->decimal('price', 10, 2)->nullable()->after('stock_quantity');
            });

            // Backfill existing product sizes with their parent product price
            DB::statement("
                UPDATE product_sizes
                SET price = products.price
                FROM products
                WHERE product_sizes.product_id = products.id
                  AND product_sizes.price IS NULL
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_sizes') && Schema::hasColumn('product_sizes', 'price')) {
            Schema::table('product_sizes', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }
};
