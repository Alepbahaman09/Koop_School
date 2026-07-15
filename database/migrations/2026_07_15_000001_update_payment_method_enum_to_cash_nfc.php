<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL: drop the existing check constraint on payment_method
        DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_payment_method_check");

        // Rename any existing 'Card' values to 'NFC Card'
        DB::statement("UPDATE payments SET payment_method = 'NFC Card' WHERE payment_method = 'Card'");

        // Also rename other unsupported values to 'Cash' as a fallback
        DB::statement("UPDATE payments SET payment_method = 'Cash' WHERE payment_method NOT IN ('Cash', 'NFC Card')");

        // Add the new restricted check constraint
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_payment_method_check CHECK (payment_method IN ('Cash', 'NFC Card'))");
    }

    public function down(): void
    {
        // Drop the new constraint
        DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_payment_method_check");

        // Rename 'NFC Card' back to 'Card'
        DB::statement("UPDATE payments SET payment_method = 'Card' WHERE payment_method = 'NFC Card'");

        // Restore the original check constraint
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_payment_method_check CHECK (payment_method IN ('Cash', 'Card', 'Online Banking', 'E-Wallet', 'Cheque'))");
    }
};
