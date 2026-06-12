<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique();
            $table->string('phone_number')->nullable()->unique();
            $table->decimal('wallet_balance', 12, 2)->default(0);
            $table->string('api_token_hash', 64)->nullable()->unique();
            $table->jsonb('mobile_profile')->nullable();
        });

        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('card_uid')->unique();
            $table->string('owner');
            $table->decimal('balance', 12, 2)->default(0);
            $table->boolean('is_frozen')->default(false);
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('mobile_documents', function (Blueprint $table) {
            $table->id();
            $table->text('path')->unique();
            $table->text('collection_path')->index();
            $table->string('document_id')->index();
            $table->jsonb('data');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_documents');
        Schema::dropIfExists('cards');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'phone_number', 'wallet_balance', 'api_token_hash', 'mobile_profile']);
        });
    }
};
