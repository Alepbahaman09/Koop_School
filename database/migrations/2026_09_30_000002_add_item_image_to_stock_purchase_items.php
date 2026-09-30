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
        if (! Schema::hasColumn('stock_purchase_items', 'item_image')) {
            Schema::table('stock_purchase_items', function (Blueprint $table) {
                $table->string('item_image')->nullable()->after('item_sku');
            });
        }

        // Backfill existing purchase items image from their linked product if available
        try {
            DB::statement("
                UPDATE stock_purchase_items spi
                SET item_image = p.image
                FROM products p
                WHERE spi.product_id = p.id
                  AND p.image IS NOT NULL
                  AND (spi.item_image IS NULL OR spi.item_image = '')
            ");
        } catch (\Throwable $e) {
            // fallback in case of different driver
            $items = DB::table('stock_purchase_items')
                ->whereNotNull('product_id')
                ->whereNull('item_image')
                ->get();

            foreach ($items as $item) {
                $product = DB::table('products')->where('id', $item->product_id)->first();
                if ($product && ! empty($product->image)) {
                    DB::table('stock_purchase_items')
                        ->where('id', $item->id)
                        ->update(['item_image' => $product->image]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('stock_purchase_items', 'item_image')) {
            Schema::table('stock_purchase_items', function (Blueprint $table) {
                $table->dropColumn('item_image');
            });
        }
    }
};
