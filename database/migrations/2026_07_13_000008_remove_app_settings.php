<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('app_settings');
    }

    public function down(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->default('Koop School');
            $table->string('store_email')->nullable();
            $table->string('store_phone')->nullable();
            $table->text('store_address')->nullable();
            $table->string('currency', 3)->default('MYR');
            $table->jsonb('notification_preferences')->nullable();
            $table->timestamps();
        });
    }
};
