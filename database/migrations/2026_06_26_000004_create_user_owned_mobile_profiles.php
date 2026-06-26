<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mobile_user_profiles')) {
            Schema::create('mobile_user_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->jsonb('profile')->nullable();
                $table->timestampsTz();
            });
        }

        if (Schema::hasColumn('users', 'mobile_profile')) {
            DB::table('users')
                ->whereNotNull('mobile_profile')
                ->orderBy('id')
                ->get(['id', 'mobile_profile'])
                ->each(function ($user) {
                    DB::table('mobile_user_profiles')->updateOrInsert(
                        ['user_id' => $user->id],
                        [
                            'profile' => $user->mobile_profile,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    );
                });

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('mobile_profile');
            });
        }

        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_mobile_reference_unique');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS orders_user_mobile_reference_unique ON orders (user_id, mobile_reference) WHERE mobile_reference IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS orders_user_mobile_reference_unique');

        if (! Schema::hasColumn('users', 'mobile_profile')) {
            Schema::table('users', function (Blueprint $table) {
                $table->jsonb('mobile_profile')->nullable();
            });
        }

        if (Schema::hasTable('mobile_user_profiles')) {
            DB::table('mobile_user_profiles')
                ->orderBy('id')
                ->get(['user_id', 'profile'])
                ->each(function ($profile) {
                    DB::table('users')
                        ->where('id', $profile->user_id)
                        ->update(['mobile_profile' => $profile->profile]);
                });

            Schema::dropIfExists('mobile_user_profiles');
        }

        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_mobile_reference_unique UNIQUE (mobile_reference)');
    }
};
