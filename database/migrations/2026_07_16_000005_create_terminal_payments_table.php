<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terminal_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('payment_reference')->unique();
            $table->string('payment_method');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('Completed');
            $table->timestamp('paid_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE terminal_payments ADD CONSTRAINT terminal_payments_payment_method_check CHECK (payment_method IN ('Cash', 'NFC Card'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('terminal_payments');
    }
};
