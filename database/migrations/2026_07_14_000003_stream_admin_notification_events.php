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
            CREATE OR REPLACE FUNCTION public.sync_product_stock_alert()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            SET search_path = ''
            AS $$
            DECLARE
                alert_level TEXT;
            BEGIN
                IF NEW.stock_quantity <= 0 THEN
                    alert_level := 'out';
                ELSIF NEW.stock_quantity <= NEW.min_stock_level THEN
                    alert_level := 'low';
                ELSE
                    alert_level := NULL;
                END IF;

                IF NEW.stock_alert_level IS DISTINCT FROM alert_level THEN
                    NEW.stock_alert_level := alert_level;

                    IF alert_level IS NOT NULL THEN
                        INSERT INTO public.admin_notifications (
                            type, title, message, link, data, created_at, updated_at
                        )
                        VALUES (
                            CASE WHEN alert_level = 'out' THEN 'stock_out' ELSE 'stock_low' END,
                            CASE WHEN alert_level = 'out' THEN 'Item out of stock' ELSE 'Low stock alert' END,
                            CASE
                                WHEN alert_level = 'out' THEN NEW.name || ' is out of stock.'
                                ELSE NEW.name || ' has ' || NEW.stock_quantity ||
                                    ' item(s) remaining. Minimum stock level: ' || NEW.min_stock_level || '.'
                            END,
                            '/products?stock=' || CASE WHEN alert_level = 'out' THEN 'out' ELSE 'low' END,
                            pg_catalog.jsonb_build_object(
                                'product_id', NEW.id,
                                'product_name', NEW.name,
                                'stock_quantity', NEW.stock_quantity,
                                'min_stock_level', NEW.min_stock_level,
                                'level', alert_level
                            ),
                            NOW(),
                            NOW()
                        );
                    END IF;
                END IF;

                RETURN NEW;
            END;
            $$;

            DROP TRIGGER IF EXISTS products_sync_stock_alert ON public.products;

            CREATE TRIGGER products_sync_stock_alert
            BEFORE INSERT OR UPDATE OF stock_quantity, min_stock_level
            ON public.products
            FOR EACH ROW
            EXECUTE FUNCTION public.sync_product_stock_alert();

            CREATE TABLE IF NOT EXISTS public.admin_notification_signals (
                id SMALLINT PRIMARY KEY,
                revision BIGINT NOT NULL DEFAULT 0,
                updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                CONSTRAINT admin_notification_signals_single_row CHECK (id = 1)
            );

            INSERT INTO public.admin_notification_signals (id)
            VALUES (1)
            ON CONFLICT (id) DO NOTHING;

            ALTER TABLE public.admin_notification_signals ENABLE ROW LEVEL SECURITY;
            REVOKE ALL ON public.admin_notification_signals FROM anon, authenticated;
            GRANT SELECT ON public.admin_notification_signals TO anon, authenticated;

            DROP POLICY IF EXISTS "Notification signals are readable" ON public.admin_notification_signals;
            CREATE POLICY "Notification signals are readable"
            ON public.admin_notification_signals
            FOR SELECT
            TO anon, authenticated
            USING (TRUE);

            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM pg_catalog.pg_publication_tables
                    WHERE pubname = 'supabase_realtime'
                      AND schemaname = 'public'
                      AND tablename = 'admin_notification_signals'
                ) THEN
                    ALTER PUBLICATION supabase_realtime
                    ADD TABLE public.admin_notification_signals;
                END IF;
            END;
            $$;

            CREATE OR REPLACE FUNCTION public.publish_admin_notification_event()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            SET search_path = ''
            AS $$
            BEGIN
                UPDATE public.admin_notification_signals
                SET revision = revision + 1,
                    updated_at = NOW()
                WHERE id = 1;

                RETURN NULL;
            END;
            $$;

            DROP TRIGGER IF EXISTS admin_notifications_publish_event ON public.admin_notifications;

            CREATE TRIGGER admin_notifications_publish_event
            AFTER INSERT OR UPDATE OR DELETE
            ON public.admin_notifications
            FOR EACH STATEMENT
            EXECUTE FUNCTION public.publish_admin_notification_event();

            UPDATE public.products
            SET stock_quantity = stock_quantity
            WHERE stock_alert_level IS DISTINCT FROM CASE
                WHEN stock_quantity <= 0 THEN 'out'
                WHEN stock_quantity <= min_stock_level THEN 'low'
                ELSE NULL
            END;
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS admin_notifications_publish_event ON public.admin_notifications;
            DROP FUNCTION IF EXISTS public.publish_admin_notification_event();
            DO $$
            BEGIN
                IF EXISTS (
                    SELECT 1
                    FROM pg_catalog.pg_publication_tables
                    WHERE pubname = 'supabase_realtime'
                      AND schemaname = 'public'
                      AND tablename = 'admin_notification_signals'
                ) THEN
                    ALTER PUBLICATION supabase_realtime
                    DROP TABLE public.admin_notification_signals;
                END IF;
            END;
            $$;
            DROP TABLE IF EXISTS public.admin_notification_signals;
            DROP TRIGGER IF EXISTS products_sync_stock_alert ON public.products;
            DROP FUNCTION IF EXISTS public.sync_product_stock_alert();
            SQL);
    }
};
