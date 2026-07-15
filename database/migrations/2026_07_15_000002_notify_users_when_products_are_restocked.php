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
            CREATE OR REPLACE FUNCTION public.notify_users_product_restocked()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            SET search_path = ''
            AS $$
            BEGIN
                IF NEW.is_active IS DISTINCT FROM TRUE THEN
                    RETURN NEW;
                END IF;

                INSERT INTO public.notifications (
                    user_id, channel, title, body, type, status, data, created_at, updated_at
                )
                SELECT
                    users.id,
                    'in_app',
                    'Item back in stock',
                    NEW.name || ' is available again.',
                    'stock_available',
                    'unread',
                    pg_catalog.jsonb_build_object(
                        'product_id', NEW.id,
                        'product_name', NEW.name,
                        'stock_quantity', NEW.stock_quantity
                    ),
                    NOW(),
                    NOW()
                FROM public.users AS users;

                RETURN NEW;
            END;
            $$;

            DROP TRIGGER IF EXISTS products_notify_users_restocked ON public.products;

            CREATE TRIGGER products_notify_users_restocked
            AFTER UPDATE OF stock_quantity ON public.products
            FOR EACH ROW
            WHEN (OLD.stock_quantity <= 0 AND NEW.stock_quantity > 0)
            EXECUTE FUNCTION public.notify_users_product_restocked();
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS products_notify_users_restocked ON public.products;
            DROP FUNCTION IF EXISTS public.notify_users_product_restocked();
            SQL);
    }
};
