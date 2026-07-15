<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_purchase_items', function (Blueprint $table) {
            // e.g. "Carton", "Pack", "Box" — the bulk unit used when buying
            $table->string('purchase_unit')->default('Unit')->after('selling_price');
            // How many selling units fit in one purchase unit (e.g. 24 pcs per carton)
            $table->unsignedInteger('units_per_purchase')->default(1)->after('purchase_unit');
        });
    }

    public function down(): void
    {
        Schema::table('stock_purchase_items', function (Blueprint $table) {
            $table->dropColumn(['purchase_unit', 'units_per_purchase']);
        });
    }
};
