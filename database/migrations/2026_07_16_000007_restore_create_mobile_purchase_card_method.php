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
            CREATE OR REPLACE FUNCTION public.create_mobile_purchase(
                p_mobile_reference text,
                p_data jsonb
            )
            RETURNS jsonb
            LANGUAGE plpgsql
            SECURITY DEFINER
            SET search_path = ''
            AS $$
            DECLARE
                v_user_id bigint := public.current_legacy_user_id();
                v_user public.users%ROWTYPE;
                v_customer_id bigint;
                v_order_number text := pg_catalog.btrim(p_data ->> 'orderNumber');
                v_items jsonb := COALESCE(p_data -> 'items', '[]'::jsonb);
                v_existing_order public.orders%ROWTYPE;
                v_order_id bigint;
                v_item jsonb;
                v_product public.products%ROWTYPE;
                v_product_id bigint;
                v_quantity integer;
                v_unit_price numeric(10, 2);
                v_line_total numeric(10, 2);
                v_subtotal numeric(10, 2) := 0;
                v_total numeric(10, 2);
                v_paid_with text := lower(coalesce(p_data ->> 'paidWith', 'gateway'));
                v_payment_source text := lower(coalesce(p_data ->> 'paymentSource', 'gateway'));
                v_payment_method text;
                v_card_id bigint;
                v_status text := coalesce(p_data ->> 'orderStatus', 'Processing');
                v_stock_before integer;
                v_stock_after integer;
            BEGIN
                if v_user_id is null then
                    raise exception 'Authenticated Supabase user is not linked to public.users';
                end if;

                if p_mobile_reference is null
                    or p_mobile_reference !~ ('^users/' || v_user_id::text || '/transactions/[^/]+$') then
                    raise exception 'Invalid mobile transaction reference';
                end if;

                select *
                into v_existing_order
                from public.orders
                where user_id = v_user_id
                    and mobile_reference = p_mobile_reference
                limit 1;

                if v_existing_order.id is not null then
                    return jsonb_build_object(
                    'id', v_existing_order.id,
                    'orderNumber', v_existing_order.order_number,
                    'alreadyCreated', true
                    );
                end if;

                if jsonb_typeof(v_items) <> 'array' or jsonb_array_length(v_items) = 0 then
                    raise exception 'Purchase requires at least one item';
                end if;

                select * into v_user
                from public.users
                where id = v_user_id
                for update;

                select id into v_customer_id
                from public.customers
                where lower(email) = lower(v_user.email)
                limit 1;

                if v_customer_id is null then
                    insert into public.customers (
                    student_id, parent_name, student_name, email, phone, class, address,
                    is_active, created_at, updated_at
                    )
                    values (
                    'MOB-' || v_user.id::text,
                    coalesce(v_user.name, v_user.username, 'Mobile User'),
                    coalesce(v_user.username, v_user.name, 'Mobile User'),
                    v_user.email,
                    coalesce(v_user.phone_number, '-'),
                    'Mobile',
                    'Mobile app customer',
                    true,
                    now(),
                    now()
                    )
                    on conflict (email) do update set
                    phone = excluded.phone,
                    updated_at = now()
                    returning id into v_customer_id;
                end if;

                if v_order_number is null
                    or exists (select 1 from public.orders where order_number = v_order_number) then
                    v_order_number := 'MOB-' || to_char(now(), 'YYYYMMDDHH24MISSMS') || '-' ||
                    substr(md5(p_mobile_reference), 1, 6);
                end if;

                if lower(v_status) in ('pending', 'preparing') then
                    v_status := 'Processing';
                elsif lower(v_status) = 'completed' then
                    v_status := 'Completed';
                elsif lower(v_status) = 'cancelled' then
                    v_status := 'Cancelled';
                elsif lower(v_status) = 'packed' then
                    v_status := 'Packed';
                elsif lower(v_status) = 'ready' then
                    v_status := 'Ready';
                else
                    v_status := 'Processing';
                end if;

                for v_item in select value from jsonb_array_elements(v_items) loop
                    v_product_id := nullif(coalesce(v_item ->> 'id', v_item ->> 'productId'), '')::bigint;

                    if v_product_id is not null then
                    select * into v_product
                    from public.products
                    where id = v_product_id
                    for update;
                    else
                    select * into v_product
                    from public.products
                    where lower(name) = lower(coalesce(v_item ->> 'name', ''))
                    limit 1
                    for update;
                    end if;

                    if v_product.id is null then
                    raise exception 'Product not found for item %', coalesce(v_item ->> 'name', v_product_id::text);
                    end if;

                    v_quantity := greatest(coalesce(nullif(v_item ->> 'quantity', '')::integer, 1), 1);

                    if v_product.stock_quantity < v_quantity then
                    raise exception 'Insufficient stock for %', v_product.name;
                    end if;

                    v_unit_price := coalesce(nullif(v_item ->> 'price', '')::numeric, v_product.price);
                    v_line_total := round(v_unit_price * v_quantity, 2);
                    v_subtotal := v_subtotal + v_line_total;
                end loop;

                v_total := round(v_subtotal, 2);

                if v_paid_with = 'card' or v_payment_source = 'card' then
                    update public.cards
                    set balance = balance - v_total,
                        last_used_at = now(),
                        updated_at = now()
                    where user_id = v_user_id
                    and card_uid = coalesce(p_data ->> 'cardId', p_data ->> 'cardUid')
                    and is_frozen = false
                    and balance >= v_total
                    returning id into v_card_id;

                    if v_card_id is null then
                    raise exception 'Selected card is unavailable or has insufficient balance';
                    end if;

                    v_payment_method := 'Card'; -- Reverted back to Card for original app behavior
                elsif v_paid_with = 'wallet' or v_payment_source = 'primary' then
                    update public.users
                    set wallet_balance = wallet_balance - v_total,
                        updated_at = now()
                    where id = v_user_id
                    and wallet_balance >= v_total;

                    if not found then
                    raise exception 'Insufficient primary wallet balance';
                    end if;

                    v_payment_method := 'E-Wallet';
                else
                    v_payment_method := 'Online Banking';
                end if;

                insert into public.orders (
                    order_number, customer_id, user_id, status, subtotal, tax, discount,
                    total_amount, payment_status, notes, mobile_reference, created_at, updated_at
                )
                values (
                    v_order_number,
                    v_customer_id,
                    v_user_id,
                    v_status,
                    v_subtotal,
                    0,
                    0,
                    v_total,
                    'Paid',
                    coalesce(p_data ->> 'note', 'Mobile app order'),
                    p_mobile_reference,
                    now(),
                    now()
                )
                returning id into v_order_id;

                for v_item in select value from jsonb_array_elements(v_items) loop
                    v_product_id := nullif(coalesce(v_item ->> 'id', v_item ->> 'productId'), '')::bigint;

                    if v_product_id is not null then
                    select * into v_product
                    from public.products
                    where id = v_product_id
                    for update;
                    else
                    select * into v_product
                    from public.products
                    where lower(name) = lower(coalesce(v_item ->> 'name', ''))
                    limit 1
                    for update;
                    end if;

                    v_quantity := greatest(coalesce(nullif(v_item ->> 'quantity', '')::integer, 1), 1);
                    v_unit_price := coalesce(nullif(v_item ->> 'price', '')::numeric, v_product.price);
                    v_line_total := round(v_unit_price * v_quantity, 2);

                    insert into public.order_items (
                    order_id, product_id, quantity, unit_price, subtotal, created_at, updated_at
                    )
                    values (
                    v_order_id, v_product.id, v_quantity, v_unit_price, v_line_total, now(), now()
                    );

                    update public.products
                    set stock_quantity = stock_quantity - v_quantity,
                        updated_at = now()
                    where id = v_product.id
                    and stock_quantity >= v_quantity
                    returning stock_quantity + v_quantity, stock_quantity
                    into v_stock_before, v_stock_after;

                    if not found then
                    raise exception 'Insufficient stock for %', v_product.name;
                    end if;

                    insert into public.inventory_transactions (
                    product_id, user_id, type, quantity, stock_before, stock_after,
                    reference_type, reference_id, notes, created_at, updated_at
                    )
                    values (
                    v_product.id,
                    v_user_id,
                    'Out',
                    v_quantity,
                    v_stock_before,
                    v_stock_after,
                    'Order',
                    v_order_id,
                    'Mobile app purchase',
                    now(),
                    now()
                    );
                end loop;

                insert into public.payments (
                    order_id, payment_reference, payment_method, amount, status, paid_at,
                    notes, created_at, updated_at
                )
                values (
                    v_order_id,
                    'PAY-' || substr(md5(p_mobile_reference), 1, 20),
                    v_payment_method,
                    v_total,
                    'Completed',
                    now(),
                    coalesce(p_data ->> 'note', 'Mobile app payment'),
                    now(),
                    now()
                );

                if pg_catalog.to_regclass('public.admin_notifications') is not null then
                    execute '
                    insert into public.admin_notifications (
                        type, title, message, link, data, created_at, updated_at
                    )
                    values ($1, $2, $3, $4, $5, now(), now())
                    '
                    using
                    'order_received',
                    'New mobile order received',
                    'Order ' || v_order_number || ' was created from the mobile app.',
                    '/orders/' || v_order_id::text,
                    jsonb_build_object(
                        'order_id', v_order_id,
                        'order_number', v_order_number,
                        'user_id', v_user_id,
                        'total_amount', v_total
                    );
                end if;

                return jsonb_build_object(
                    'id', v_order_id,
                    'orderNumber', v_order_number,
                    'totalAmount', v_total,
                    'alreadyCreated', false
                );
            END;
            $$;
            SQL);
    }

    public function down(): void
    {
    }
};
