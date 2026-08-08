<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add supplier_id to products
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
        });

        // 2. Safely add supplier_id to suppliers if it doesn't exist
        if (!Schema::hasColumn('suppliers', 'supplier_id')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->string('supplier_id')->nullable()->after('id');
            });

            // Populate existing rows
            $suppliers = DB::table('suppliers')->orderBy('id')->get();
            foreach ($suppliers as $index => $supplier) {
                $supplierId = 'SUP' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);
                DB::table('suppliers')->where('id', $supplier->id)->update(['supplier_id' => $supplierId]);
            }

            // Set unique and not null
            Schema::table('suppliers', function (Blueprint $table) {
                $table->string('supplier_id')->nullable(false)->unique()->change();
            });
        }

        // 3. Drop notes and status columns from suppliers
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('suppliers', 'status')) {
                $table->dropColumn('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('suppliers', 'status')) {
                $table->string('status')->default('active');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
    }
};
