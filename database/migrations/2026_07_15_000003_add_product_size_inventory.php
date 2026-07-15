<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('size', 8);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->timestampsTz();
            $table->unique(['product_id', 'size']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('size', 8)->nullable()->after('product_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('size', 8)->nullable()->after('product_id');
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            ALTER TABLE public.product_sizes
            ADD CONSTRAINT product_sizes_supported_size_check
            CHECK (size IN ('XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL'));

            ALTER TABLE public.product_sizes
            ADD CONSTRAINT product_sizes_stock_nonnegative_check
            CHECK (stock_quantity >= 0);

            ALTER TABLE public.cart_items
            ADD CONSTRAINT cart_items_supported_size_check
            CHECK (size IS NULL OR size IN ('XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL'));

            ALTER TABLE public.order_items
            ADD CONSTRAINT order_items_supported_size_check
            CHECK (size IS NULL OR size IN ('XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL'));

            ALTER TABLE public.cart_items
            DROP CONSTRAINT IF EXISTS cart_items_cart_id_product_id_key;

            ALTER TABLE public.cart_items
            DROP CONSTRAINT IF EXISTS cart_items_cart_id_product_id_unique;

            CREATE UNIQUE INDEX cart_items_regular_product_unique
            ON public.cart_items (cart_id, product_id)
            WHERE size IS NULL;

            CREATE UNIQUE INDEX cart_items_sized_product_unique
            ON public.cart_items (cart_id, product_id, size)
            WHERE size IS NOT NULL;

            ALTER TABLE public.product_sizes ENABLE ROW LEVEL SECURITY;

            GRANT SELECT ON public.product_sizes TO anon, authenticated;

            DROP POLICY IF EXISTS "Anyone can read product sizes" ON public.product_sizes;
            CREATE POLICY "Anyone can read product sizes"
            ON public.product_sizes FOR SELECT TO anon, authenticated
            USING (true);

            CREATE OR REPLACE FUNCTION public.create_mobile_purchase_for_student(
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
                v_student_id bigint;
                v_existing_student_id bigint;
                v_result jsonb;
                v_order_id bigint;
                v_item jsonb;
                v_product_id bigint;
                v_product_size_id bigint;
                v_order_item_id bigint;
                v_requested_size text;
                v_quantity integer;
                v_size_count integer;
            BEGIN
                IF v_user_id IS NULL THEN
                    RAISE EXCEPTION 'Authenticated Supabase user is not linked to public.users';
                END IF;

                IF COALESCE(p_data ->> 'studentId', '') !~ '^[0-9]+$' THEN
                    RAISE EXCEPTION 'Select a student before continuing';
                END IF;

                SELECT id
                INTO v_student_id
                FROM public.students
                WHERE id = (p_data ->> 'studentId')::bigint
                  AND user_id = v_user_id
                  AND is_active = true;

                IF v_student_id IS NULL THEN
                    RAISE EXCEPTION 'Selected student does not belong to this account';
                END IF;

                SELECT student_id
                INTO v_existing_student_id
                FROM public.orders
                WHERE user_id = v_user_id
                  AND mobile_reference = p_mobile_reference;

                IF FOUND
                  AND v_existing_student_id IS NOT NULL
                  AND v_existing_student_id <> v_student_id THEN
                    RAISE EXCEPTION 'This purchase is already assigned to another student';
                END IF;

                v_result := public.create_mobile_purchase(p_mobile_reference, p_data);
                v_order_id := (v_result ->> 'id')::bigint;

                FOR v_item IN
                    SELECT value
                    FROM pg_catalog.jsonb_array_elements(COALESCE(p_data -> 'items', '[]'::jsonb))
                LOOP
                    v_product_id := NULL;
                    v_order_item_id := NULL;
                    v_product_size_id := NULL;
                    v_requested_size := pg_catalog.upper(
                        NULLIF(pg_catalog.btrim(v_item ->> 'size'), '')
                    );
                    v_quantity := GREATEST(
                        COALESCE(NULLIF(v_item ->> 'quantity', '')::integer, 1),
                        1
                    );

                    IF COALESCE(v_item ->> 'id', v_item ->> 'productId', '') ~ '^[0-9]+$' THEN
                        v_product_id := COALESCE(v_item ->> 'id', v_item ->> 'productId')::bigint;
                    ELSE
                        SELECT id
                        INTO v_product_id
                        FROM public.products
                        WHERE pg_catalog.lower(name) = pg_catalog.lower(COALESCE(v_item ->> 'name', ''))
                        LIMIT 1;
                    END IF;

                    SELECT COUNT(*)
                    INTO v_size_count
                    FROM public.product_sizes
                    WHERE product_id = v_product_id;

                    IF v_size_count = 0 THEN
                        IF v_requested_size IS NOT NULL THEN
                            RAISE EXCEPTION 'Product % does not use sizes', COALESCE(v_item ->> 'name', v_product_id::text);
                        END IF;

                        CONTINUE;
                    END IF;

                    IF v_requested_size IS NULL THEN
                        RAISE EXCEPTION 'Select a size for %', COALESCE(v_item ->> 'name', v_product_id::text);
                    END IF;

                    SELECT id
                    INTO v_order_item_id
                    FROM public.order_items
                    WHERE order_id = v_order_id
                      AND product_id = v_product_id
                      AND size IS NULL
                    ORDER BY id
                    LIMIT 1
                    FOR UPDATE;

                    IF v_order_item_id IS NULL THEN
                        IF EXISTS (
                            SELECT 1
                            FROM public.order_items
                            WHERE order_id = v_order_id
                              AND product_id = v_product_id
                              AND size = v_requested_size
                        ) THEN
                            CONTINUE;
                        END IF;

                        RAISE EXCEPTION 'Unable to match size % to the purchased item', v_requested_size;
                    END IF;

                    SELECT id
                    INTO v_product_size_id
                    FROM public.product_sizes
                    WHERE product_id = v_product_id
                      AND size = v_requested_size
                    FOR UPDATE;

                    IF v_product_size_id IS NULL THEN
                        RAISE EXCEPTION 'Size % is not available for %', v_requested_size, COALESCE(v_item ->> 'name', v_product_id::text);
                    END IF;

                    UPDATE public.product_sizes
                    SET stock_quantity = stock_quantity - v_quantity,
                        updated_at = NOW()
                    WHERE id = v_product_size_id
                      AND stock_quantity >= v_quantity;

                    IF NOT FOUND THEN
                        RAISE EXCEPTION 'Insufficient stock for % size %', COALESCE(v_item ->> 'name', v_product_id::text), v_requested_size;
                    END IF;

                    UPDATE public.order_items
                    SET size = v_requested_size,
                        updated_at = NOW()
                    WHERE id = v_order_item_id;
                END LOOP;

                UPDATE public.orders
                SET student_id = v_student_id,
                    updated_at = NOW()
                WHERE id = v_order_id
                  AND user_id = v_user_id
                  AND (student_id IS NULL OR student_id = v_student_id);

                IF NOT FOUND THEN
                    RAISE EXCEPTION 'Unable to assign the selected student to this order';
                END IF;

                RETURN v_result || pg_catalog.jsonb_build_object('studentId', v_student_id);
            END;
            $$;

            REVOKE ALL ON FUNCTION public.create_mobile_purchase(text, jsonb) FROM authenticated;
            REVOKE ALL ON FUNCTION public.create_mobile_purchase_for_student(text, jsonb) FROM public;
            GRANT EXECUTE ON FUNCTION public.create_mobile_purchase_for_student(text, jsonb) TO authenticated;

            NOTIFY pgrst, 'reload schema';
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                GRANT EXECUTE ON FUNCTION public.create_mobile_purchase(text, jsonb) TO authenticated;

                CREATE OR REPLACE FUNCTION public.create_mobile_purchase_for_student(
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
                    v_student_id bigint;
                    v_existing_student_id bigint;
                    v_result jsonb;
                    v_order_id bigint;
                BEGIN
                    IF v_user_id IS NULL THEN
                        RAISE EXCEPTION 'Authenticated Supabase user is not linked to public.users';
                    END IF;

                    IF COALESCE(p_data ->> 'studentId', '') !~ '^[0-9]+$' THEN
                        RAISE EXCEPTION 'Select a student before continuing';
                    END IF;

                    SELECT id
                    INTO v_student_id
                    FROM public.students
                    WHERE id = (p_data ->> 'studentId')::bigint
                      AND user_id = v_user_id
                      AND is_active = true;

                    IF v_student_id IS NULL THEN
                        RAISE EXCEPTION 'Selected student does not belong to this account';
                    END IF;

                    SELECT student_id
                    INTO v_existing_student_id
                    FROM public.orders
                    WHERE user_id = v_user_id
                      AND mobile_reference = p_mobile_reference;

                    IF FOUND
                      AND v_existing_student_id IS NOT NULL
                      AND v_existing_student_id <> v_student_id THEN
                        RAISE EXCEPTION 'This purchase is already assigned to another student';
                    END IF;

                    v_result := public.create_mobile_purchase(p_mobile_reference, p_data);
                    v_order_id := (v_result ->> 'id')::bigint;

                    UPDATE public.orders
                    SET student_id = v_student_id,
                        updated_at = NOW()
                    WHERE id = v_order_id
                      AND user_id = v_user_id
                      AND (student_id IS NULL OR student_id = v_student_id);

                    IF NOT FOUND THEN
                        RAISE EXCEPTION 'Unable to assign the selected student to this order';
                    END IF;

                    RETURN v_result || pg_catalog.jsonb_build_object('studentId', v_student_id);
                END;
                $$;

                WITH cart_totals AS (
                    SELECT cart_id, product_id, MIN(id) AS kept_id, SUM(quantity) AS total_quantity
                    FROM public.cart_items
                    GROUP BY cart_id, product_id
                )
                UPDATE public.cart_items AS cart_item
                SET quantity = cart_totals.total_quantity,
                    updated_at = NOW()
                FROM cart_totals
                WHERE cart_item.id = cart_totals.kept_id;

                DELETE FROM public.cart_items AS cart_item
                USING (
                    SELECT cart_id, product_id, MIN(id) AS kept_id
                    FROM public.cart_items
                    GROUP BY cart_id, product_id
                ) AS kept_items
                WHERE cart_item.cart_id = kept_items.cart_id
                  AND cart_item.product_id = kept_items.product_id
                  AND cart_item.id <> kept_items.kept_id;

                DROP INDEX IF EXISTS public.cart_items_sized_product_unique;
                DROP INDEX IF EXISTS public.cart_items_regular_product_unique;

                ALTER TABLE public.cart_items
                ADD CONSTRAINT cart_items_cart_id_product_id_key UNIQUE (cart_id, product_id);
                SQL);
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('size');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('size');
        });

        Schema::dropIfExists('product_sizes');
    }
};
