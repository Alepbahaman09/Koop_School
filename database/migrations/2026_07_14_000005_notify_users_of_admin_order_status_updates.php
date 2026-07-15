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
            CREATE OR REPLACE FUNCTION public.notify_user_order_status_update()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            SET search_path = ''
            AS $$
            BEGIN
                IF NEW.admin_id IS NULL THEN
                    RETURN NEW;
                END IF;

                INSERT INTO public.notifications (
                    user_id, channel, title, body, type, status, data, created_at, updated_at
                )
                SELECT
                    orders.user_id,
                    'in_app',
                    'Order status updated',
                    'Order ' || orders.order_number || ' is now ' || NEW.status || '.',
                    'order_status',
                    'unread',
                    pg_catalog.jsonb_build_object(
                        'order_id', orders.id,
                        'order_number', orders.order_number,
                        'order_status', NEW.status,
                        'receipt_id', COALESCE(
                            NULLIF(
                                pg_catalog.regexp_replace(orders.mobile_reference, '^.*/', ''),
                                ''
                            ),
                            'order-' || orders.id
                        )
                    ),
                    NOW(),
                    NOW()
                FROM public.orders AS orders
                WHERE orders.id = NEW.order_id
                  AND orders.user_id IS NOT NULL;

                RETURN NEW;
            END;
            $$;

            DROP TRIGGER IF EXISTS order_status_history_notify_user
            ON public.order_status_history;

            CREATE TRIGGER order_status_history_notify_user
            AFTER INSERT ON public.order_status_history
            FOR EACH ROW
            EXECUTE FUNCTION public.notify_user_order_status_update();
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS order_status_history_notify_user
            ON public.order_status_history;

            DROP FUNCTION IF EXISTS public.notify_user_order_status_update();
            SQL);
    }
};
