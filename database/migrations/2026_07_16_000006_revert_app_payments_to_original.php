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

        // 1. Drop the check constraint first to allow updating the values without constraint checks
        DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_payment_method_check");

        // 2. Revert any 'NFC Card' payment methods back to 'Card' on the original payments table
        DB::statement("UPDATE payments SET payment_method = 'Card' WHERE payment_method = 'NFC Card'");

        // 3. Recreate original check constraint (Cash, Card, Online Banking, E-Wallet, Cheque)
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_payment_method_check CHECK (payment_method IN ('Cash', 'Card', 'Online Banking', 'E-Wallet', 'Cheque'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_payment_method_check");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_payment_method_check CHECK (payment_method IN ('Cash', 'Card', 'Online Banking', 'E-Wallet', 'Cheque', 'NFC Card'))");
    }
};
