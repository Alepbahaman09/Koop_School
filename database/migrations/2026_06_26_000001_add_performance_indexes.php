<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE INDEX IF NOT EXISTS idx_orders_payment_created ON orders (payment_status, created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_orders_status_created ON orders (status, created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_orders_customer_created ON orders (customer_id, created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_products_active_stock ON products (is_active, stock_quantity)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_products_category_active ON products (category_id, is_active)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_customers_active_created ON customers (is_active, created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_payments_status_paid ON payments (status, paid_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_payments_order_status ON payments (order_id, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_order_items_order_product ON order_items (order_id, product_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_order_status_history_order_created ON order_status_history (order_id, created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mobile_documents_collection_updated ON mobile_documents (collection_path, updated_at)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS idx_mobile_documents_collection_updated');
        DB::statement('DROP INDEX IF EXISTS idx_order_status_history_order_created');
        DB::statement('DROP INDEX IF EXISTS idx_order_items_order_product');
        DB::statement('DROP INDEX IF EXISTS idx_payments_order_status');
        DB::statement('DROP INDEX IF EXISTS idx_payments_status_paid');
        DB::statement('DROP INDEX IF EXISTS idx_customers_active_created');
        DB::statement('DROP INDEX IF EXISTS idx_products_category_active');
        DB::statement('DROP INDEX IF EXISTS idx_products_active_stock');
        DB::statement('DROP INDEX IF EXISTS idx_orders_customer_created');
        DB::statement('DROP INDEX IF EXISTS idx_orders_status_created');
        DB::statement('DROP INDEX IF EXISTS idx_orders_payment_created');
    }
};
