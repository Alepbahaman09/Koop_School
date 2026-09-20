<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terminal_payments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            $table->foreignId('card_id')->nullable()->after('user_id')->constrained('cards')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('terminal_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('card_id');
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
