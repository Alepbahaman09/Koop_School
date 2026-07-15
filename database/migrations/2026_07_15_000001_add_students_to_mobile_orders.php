<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('class', 100);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->index(['user_id', 'created_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('student_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->nullOnDelete();
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE UNIQUE INDEX students_user_name_class_unique
                ON public.students (user_id, lower(name), lower(class))
                WHERE is_active = true;

            INSERT INTO public.students (user_id, name, class, created_at, updated_at)
            SELECT DISTINCT
                orders.user_id,
                customers.student_name,
                customers.class,
                NOW(),
                NOW()
            FROM public.orders AS orders
            JOIN public.customers AS customers ON customers.id = orders.customer_id
            WHERE NULLIF(BTRIM(customers.student_name), '') IS NOT NULL
              AND NULLIF(BTRIM(customers.class), '') IS NOT NULL
              AND customers.student_name <> '-'
              AND customers.class NOT IN ('-', 'Mobile')
            ON CONFLICT DO NOTHING;

            UPDATE public.orders AS orders
            SET student_id = students.id
            FROM public.customers AS customers
            JOIN public.students AS students
              ON students.name = customers.student_name
             AND students.class = customers.class
            WHERE customers.id = orders.customer_id
              AND students.user_id = orders.user_id
              AND orders.student_id IS NULL;

            ALTER TABLE public.students ENABLE ROW LEVEL SECURITY;

            GRANT SELECT, INSERT, UPDATE, DELETE ON public.students TO authenticated;
            GRANT USAGE, SELECT ON SEQUENCE public.students_id_seq TO authenticated;

            DROP POLICY IF EXISTS "Users manage their students" ON public.students;
            CREATE POLICY "Users manage their students"
                ON public.students
                FOR ALL
                TO authenticated
                USING (user_id = public.current_legacy_user_id())
                WITH CHECK (user_id = public.current_legacy_user_id());

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

                RETURN v_result || jsonb_build_object('studentId', v_student_id);
            END;
            $$;

            REVOKE ALL ON FUNCTION public.create_mobile_purchase_for_student(text, jsonb) FROM public;
            GRANT EXECUTE ON FUNCTION public.create_mobile_purchase_for_student(text, jsonb) TO authenticated;

            NOTIFY pgrst, 'reload schema';
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                DROP FUNCTION IF EXISTS public.create_mobile_purchase_for_student(text, jsonb);
                DROP POLICY IF EXISTS "Users manage their students" ON public.students;
                SQL);
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_id');
        });

        Schema::dropIfExists('students');
    }
};
