<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Drop old constraint
            DB::statement("ALTER TABLE purchase_orders DROP CONSTRAINT IF EXISTS purchase_orders_status_check");

            // Recreate constraint to include pending, partially_received, and received
            DB::statement("ALTER TABLE purchase_orders ADD CONSTRAINT purchase_orders_status_check 
                CHECK (status IN ('Draft', 'Sent', 'Confirmed', 'Received', 'Completed', 'Cancelled', 'pending', 'partially_received', 'received'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE purchase_orders DROP CONSTRAINT IF EXISTS purchase_orders_status_check");

            DB::statement("ALTER TABLE purchase_orders ADD CONSTRAINT purchase_orders_status_check 
                CHECK (status IN ('Draft', 'Sent', 'Confirmed', 'Received', 'Completed', 'Cancelled'))");
        }
    }
};
