<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->string('label')->nullable();
            $table->string('tone')->default('blue');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::statement('grant select on public.home_banners to authenticated');
        DB::statement('grant select on public.home_banners to anon');
    }

    public function down(): void
    {
        Schema::dropIfExists('home_banners');
    }
};
