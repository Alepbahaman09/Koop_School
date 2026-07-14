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
            CREATE OR REPLACE FUNCTION public.process_order_after_completed_payment()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            SET search_path = ''
            AS $$
            BEGIN
                IF NEW.status = 'Completed'
                    AND (TG_OP = 'INSERT' OR OLD.status IS DISTINCT FROM NEW.status) THEN
                    UPDATE public.orders
                    SET status = 'Processing', updated_at = NOW()
                    WHERE id = NEW.order_id;
                END IF;

                RETURN NEW;
            END;
            $$;
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.process_order_after_completed_payment()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            AS $$
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
            $$;
            SQL);
    }
};
