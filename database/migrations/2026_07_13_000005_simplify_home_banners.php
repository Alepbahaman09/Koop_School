<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEFAULT_COLOR = '#4f46e5';

    public function up(): void
    {
        Schema::table('home_banners', function (Blueprint $table) {
            $table->text('image_url')->nullable()->after('tone');
        });

        DB::table('home_banners')->where('tone', 'blue')->update(['tone' => '#4f46e5']);
        DB::table('home_banners')->where('tone', 'green')->update(['tone' => '#059669']);
        DB::table('home_banners')->where('tone', 'orange')->update(['tone' => '#d97706']);
        DB::table('home_banners')->where('tone', 'purple')->update(['tone' => '#7c3aed']);

        Schema::table('home_banners', function (Blueprint $table) {
            $table->string('tone')->default(self::DEFAULT_COLOR)->change();
            $table->dropColumn(['sort_order', 'starts_at', 'ends_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
                VALUES (
                    'home-banner-images',
                    'home-banner-images',
                    true,
                    5242880,
                    ARRAY['image/jpeg', 'image/png', 'image/gif', 'image/webp']
                )
                ON CONFLICT (id) DO UPDATE SET
                    public = EXCLUDED.public,
                    file_size_limit = EXCLUDED.file_size_limit,
                    allowed_mime_types = EXCLUDED.allowed_mime_types
                SQL);
        }
    }

    public function down(): void
    {
        Schema::table('home_banners', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('tone')->default('blue')->change();
            $table->dropColumn('image_url');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                DELETE FROM storage.buckets
                WHERE id = 'home-banner-images'
                  AND NOT EXISTS (
                      SELECT 1 FROM storage.objects WHERE bucket_id = 'home-banner-images'
                  )
                SQL);
        }
    }
};
