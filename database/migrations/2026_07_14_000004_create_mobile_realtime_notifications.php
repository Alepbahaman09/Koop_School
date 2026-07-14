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
            CREATE OR REPLACE FUNCTION public.notify_user_transaction()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            SET search_path = ''
            AS $$
            DECLARE
                transaction_name TEXT := pg_catalog.initcap(
                    pg_catalog.replace(COALESCE(NEW.type, 'transaction'), '_', ' ')
                );
            BEGIN
                IF NEW.order_id IS NOT NULL AND pg_catalog.lower(NEW.type) = 'purchase' THEN
                    RETURN NEW;
                END IF;

                INSERT INTO public.notifications (
                    user_id, channel, title, body, type, status, data, created_at, updated_at
                )
                VALUES (
                    NEW.user_id,
                    'in_app',
                    transaction_name || ' recorded',
                    COALESCE(
                        NULLIF(NEW.note, ''),
                        transaction_name || ' transaction of RM ' ||
                            pg_catalog.to_char(NEW.amount, 'FM999999990.00') || ' was recorded.'
                    ),
                    'transaction',
                    'unread',
                    pg_catalog.jsonb_build_object(
                        'transaction_id', NEW.id,
                        'amount', NEW.amount,
                        'transaction_type', NEW.type,
                        'order_number', NEW.order_number,
                        'receipt_id', CASE
                            WHEN NULLIF(NEW.order_number, '') IS NULL THEN 'transaction-' || NEW.id
                            ELSE NEW.order_number
                        END
                    ),
                    NOW(),
                    NOW()
                );

                RETURN NEW;
            END;
            $$;

            DROP TRIGGER IF EXISTS transactions_notify_user ON public.transactions;

            CREATE TRIGGER transactions_notify_user
            AFTER INSERT ON public.transactions
            FOR EACH ROW
            EXECUTE FUNCTION public.notify_user_transaction();

            CREATE OR REPLACE FUNCTION public.notify_user_completed_payment()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            SET search_path = ''
            AS $$
            BEGIN
                IF NEW.status <> 'Completed' THEN
                    RETURN NEW;
                END IF;

                IF TG_OP = 'UPDATE' AND OLD.status IS NOT DISTINCT FROM NEW.status THEN
                    RETURN NEW;
                END IF;

                INSERT INTO public.notifications (
                    user_id, channel, title, body, type, status, data, created_at, updated_at
                )
                SELECT
                    orders.user_id,
                    'in_app',
                    'Payment successful',
                    'Payment of RM ' || pg_catalog.to_char(NEW.amount, 'FM999999990.00') ||
                        ' for order ' || orders.order_number || ' was completed.',
                    'purchase',
                    'unread',
                    pg_catalog.jsonb_build_object(
                        'payment_id', NEW.id,
                        'order_id', orders.id,
                        'order_number', orders.order_number,
                        'amount', NEW.amount,
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

            DROP TRIGGER IF EXISTS payments_notify_user ON public.payments;

            CREATE TRIGGER payments_notify_user
            AFTER INSERT OR UPDATE OF status ON public.payments
            FOR EACH ROW
            EXECUTE FUNCTION public.notify_user_completed_payment();

            CREATE OR REPLACE FUNCTION public.notify_users_product_out_of_stock()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            SET search_path = ''
            AS $$
            BEGIN
                IF NEW.stock_quantity > 0 THEN
                    RETURN NEW;
                END IF;

                IF TG_OP = 'UPDATE' AND OLD.stock_quantity <= 0 THEN
                    RETURN NEW;
                END IF;

                INSERT INTO public.notifications (
                    user_id, channel, title, body, type, status, data, created_at, updated_at
                )
                SELECT
                    users.id,
                    'in_app',
                    'Item out of stock',
                    NEW.name || ' is currently out of stock.',
                    'stock_out',
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

            DROP TRIGGER IF EXISTS products_notify_users_out_of_stock ON public.products;

            CREATE TRIGGER products_notify_users_out_of_stock
            AFTER INSERT OR UPDATE OF stock_quantity ON public.products
            FOR EACH ROW
            EXECUTE FUNCTION public.notify_users_product_out_of_stock();

            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM pg_catalog.pg_publication_tables
                    WHERE pubname = 'supabase_realtime'
                      AND schemaname = 'public'
                      AND tablename = 'notifications'
                ) THEN
                    ALTER PUBLICATION supabase_realtime ADD TABLE public.notifications;
                END IF;
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
            DROP TRIGGER IF EXISTS products_notify_users_out_of_stock ON public.products;
            DROP FUNCTION IF EXISTS public.notify_users_product_out_of_stock();
            DROP TRIGGER IF EXISTS payments_notify_user ON public.payments;
            DROP FUNCTION IF EXISTS public.notify_user_completed_payment();
            DROP TRIGGER IF EXISTS transactions_notify_user ON public.transactions;
            DROP FUNCTION IF EXISTS public.notify_user_transaction();

            DO $$
            BEGIN
                IF EXISTS (
                    SELECT 1
                    FROM pg_catalog.pg_publication_tables
                    WHERE pubname = 'supabase_realtime'
                      AND schemaname = 'public'
                      AND tablename = 'notifications'
                ) THEN
                    ALTER PUBLICATION supabase_realtime DROP TABLE public.notifications;
                END IF;
            END;
            $$;
            SQL);
    }
};
