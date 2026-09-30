<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Stock Purchase Items: preserve historical intake data even if a product is deleted
        if (Schema::hasTable('stock_purchase_items')) {
            Schema::table('stock_purchase_items', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_purchase_items', 'item_name')) {
                    $table->string('item_name')->nullable()->after('product_id');
                }
                if (!Schema::hasColumn('stock_purchase_items', 'item_sku')) {
                    $table->string('item_sku')->nullable()->after('item_name');
                }
            });

            // Backfill existing rows with current product names and skus
            DB::statement("
                UPDATE stock_purchase_items
                SET item_name = products.name,
                    item_sku = products.sku
                FROM products
                WHERE stock_purchase_items.product_id = products.id
                  AND stock_purchase_items.item_name IS NULL
            ");

            // Change product_id foreign key from CASCADE to NULL ON DELETE
            Schema::table('stock_purchase_items', function (Blueprint $table) {
                // Drop existing foreign key safely
                $table->dropForeign(['product_id']);
                // Make column nullable
                $table->unsignedBigInteger('product_id')->nullable()->change();
                // Re-add foreign key with nullOnDelete
                $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            });
        }

        // 2. Purchase Order Items: also ensure nullOnDelete so purchase order history is preserved
        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_order_items', 'item_name')) {
                    $table->string('item_name')->nullable()->after('product_id');
                }
            });

            DB::statement("
                UPDATE purchase_order_items
                SET item_name = products.name
                FROM products
                WHERE purchase_order_items.product_id = products.id
                  AND purchase_order_items.item_name IS NULL
            ");

            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
                $table->unsignedBigInteger('product_id')->nullable()->change();
                $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_purchase_items')) {
            Schema::table('stock_purchase_items', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
                $table->unsignedBigInteger('product_id')->nullable(false)->change();
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
                $table->dropColumn(['item_name', 'item_sku']);
            });
        }

        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
                $table->unsignedBigInteger('product_id')->nullable(false)->change();
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
                $table->dropColumn(['item_name']);
            });
        }
    }
};
