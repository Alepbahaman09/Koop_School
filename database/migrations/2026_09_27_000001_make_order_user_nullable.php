<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make user_id and customer_id nullable on orders so kiosk cash sales
        // don't need a fake walk-in user. NFC and online orders still have
        // real users linked.
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreignId('customer_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreignId('customer_id')->nullable(false)->change();
        });
    }
};
