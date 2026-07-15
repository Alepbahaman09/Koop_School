<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Default purchase unit used when buying this product (e.g. "Carton")
            $table->string('purchase_unit')->default('Unit')->after('unit');
            // How many selling units per purchase unit — auto-fills on next purchase
            $table->unsignedInteger('units_per_carton')->default(1)->after('purchase_unit');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['purchase_unit', 'units_per_carton']);
        });
    }
};
