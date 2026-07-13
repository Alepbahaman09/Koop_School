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

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION process_order_after_completed_payment()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.status = 'Completed' AND TG_OP = 'INSERT' THEN
                    UPDATE orders
                    SET status = 'Processing', updated_at = NOW()
                    WHERE id = NEW.order_id;
                ELSIF NEW.status = 'Completed' AND OLD.status IS DISTINCT FROM NEW.status THEN
                    UPDATE orders
                    SET status = 'Processing', updated_at = NOW()
                    WHERE id = NEW.order_id;
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS payments_process_order ON payments;

            CREATE TRIGGER payments_process_order
            AFTER INSERT OR UPDATE OF status ON payments
            FOR EACH ROW
            EXECUTE FUNCTION process_order_after_completed_payment();
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS payments_process_order ON payments;
            DROP FUNCTION IF EXISTS process_order_after_completed_payment();
            SQL);
    }
};
