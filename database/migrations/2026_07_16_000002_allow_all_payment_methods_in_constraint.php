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

        // Drop the constraint so we can allow all payment methods (original app methods + new cashier NFC Card)
        DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_payment_method_check");

        // Add the unified constraint to support both app payments and cashier terminal payments
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_payment_method_check CHECK (payment_method IN ('Cash', 'Card', 'Online Banking', 'E-Wallet', 'Cheque', 'NFC Card'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_payment_method_check");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_payment_method_check CHECK (payment_method IN ('Cash', 'NFC Card'))");
    }
};
