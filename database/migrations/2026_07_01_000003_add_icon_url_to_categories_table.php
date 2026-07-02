<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('categories', 'icon_url')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->text('icon_url')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('categories', 'icon_url')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('icon_url');
            });
        }
    }
};
