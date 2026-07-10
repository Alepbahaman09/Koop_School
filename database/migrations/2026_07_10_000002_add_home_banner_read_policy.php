<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('alter table public.home_banners enable row level security');
        DB::statement('drop policy if exists "Anyone can read active home banners" on public.home_banners');
        DB::statement(
            'create policy "Anyone can read active home banners"
             on public.home_banners
             for select
             using (is_active = true)'
        );
        DB::statement('grant select on public.home_banners to anon, authenticated');
        DB::statement("notify pgrst, 'reload schema'");
    }

    public function down(): void
    {
        DB::statement('drop policy if exists "Anyone can read active home banners" on public.home_banners');
    }
};
