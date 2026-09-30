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

        // 1. Clean up duplicate tokens in fcm_devices
        DB::statement("
            DELETE FROM public.fcm_devices d1
            WHERE d1.id < (
                SELECT MAX(d2.id)
                FROM public.fcm_devices d2
                WHERE d2.user_id = d1.user_id AND d2.platform = d1.platform
            )
        ");

        // 2. Ensure register_fcm_device deletes older tokens for the user/platform
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.register_fcm_device(p_token text, p_platform text)
            RETURNS void
            LANGUAGE plpgsql
            SECURITY DEFINER
            SET search_path TO ''
            AS $$
            declare
                current_user_id bigint := public.current_legacy_user_id();
                clean_platform text := pg_catalog.lower(pg_catalog.btrim(p_platform));
                clean_token text := pg_catalog.btrim(p_token);
            begin
                if current_user_id is null then
                    raise exception 'A signed-in user is required.';
                end if;

                if nullif(clean_token, '') is null then
                    raise exception 'A device token is required.';
                end if;

                delete from public.fcm_devices
                where user_id = current_user_id
                  and platform = clean_platform
                  and token <> clean_token;

                insert into public.fcm_devices (
                    user_id, token, platform, registered_at, last_seen_at
                )
                values (
                    current_user_id,
                    clean_token,
                    clean_platform,
                    now(),
                    now()
                )
                on conflict (token) do update
                set user_id = excluded.user_id,
                    platform = excluded.platform,
                    last_seen_at = now();
            end;
            $$;

            -- 3. Prevent duplicate notifications on terminal_payments status updates
            CREATE OR REPLACE FUNCTION public.notify_admin_terminal_purchase()
            RETURNS trigger
            LANGUAGE plpgsql
            SET search_path TO ''
            AS $$
            begin
                if new.status <> 'Completed' then
                    return new;
                end if;

                if tg_op = 'UPDATE' and old.status is not distinct from new.status then
                    return new;
                end if;

                insert into public.admin_notifications (type, title, message, link, data, created_at, updated_at)
                select
                    'purchase',
                    'Kiosk purchase',
                    'Kiosk purchase · ' || pg_catalog.split_part(orders.order_number, '-', 3) ||
                        ' of RM ' || pg_catalog.to_char(new.amount, 'FM999999990.00') || ' was completed.',
                    '/orders',
                    pg_catalog.jsonb_build_object(
                        'order_id', orders.id,
                        'order_number', orders.order_number,
                        'amount', new.amount,
                        'payment_method', new.payment_method
                    ),
                    now(),
                    now()
                from public.orders as orders
                where orders.id = new.order_id;

                return new;
            end;
            $$;

            -- 4. Drop products_notify_users_out_of_stock trigger to avoid spamming mobile users
            DROP TRIGGER IF EXISTS products_notify_users_out_of_stock ON public.products;
        SQL);
    }

    public function down(): void
    {
        // Reversal is optional since it's deduplication and cleanup.
    }
};
