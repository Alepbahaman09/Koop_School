<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Make products.category_id nullable and set ON DELETE SET NULL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE products ALTER COLUMN category_id DROP NOT NULL');
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_category_id_foreign');
            DB::statement('ALTER TABLE products ADD CONSTRAINT products_category_id_foreign FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL');
        } else {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedBigInteger('category_id')->nullable()->change();
            });
        }

        // 2. Create product activity logs table for tracking all additions, edits, and deletions
        Schema::create('product_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('admin_name')->nullable();
            $table->string('entity_type'); // 'product', 'category', 'supplier', 'stock_purchase'
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_name');
            $table->string('action'); // 'created', 'updated', 'deleted'
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_activity_logs');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_category_id_foreign');
            DB::statement('ALTER TABLE products ADD CONSTRAINT products_category_id_foreign FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE');
        }
    }
};
