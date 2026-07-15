<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Rename name to contact_person
            $table->renameColumn('name', 'contact_person');
            
            // Add notes
            $table->text('notes')->nullable();
            
            // Add status
            $table->string('status')->default('active');
        });

        // Set company_name to a placeholder if any null rows exist before making it NOT NULL
        DB::table('suppliers')->whereNull('company_name')->update(['company_name' => 'Unnamed Supplier']);

        Schema::table('suppliers', function (Blueprint $table) {
            // Make company_name required (NOT NULL)
            $table->string('company_name')->nullable(false)->change();
            $table->string('contact_person')->nullable()->change();
        });

        // Migrate is_active to status
        DB::table('suppliers')->where('is_active', true)->update(['status' => 'active']);
        DB::table('suppliers')->where('is_active', false)->update(['status' => 'inactive']);

        Schema::table('suppliers', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn('is_active');
            if (Schema::hasColumn('suppliers', 'tax_number')) {
                $table->dropColumn('tax_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->renameColumn('contact_person', 'name');
            $table->boolean('is_active')->default(true);
            $table->string('tax_number')->nullable();
        });

        DB::table('suppliers')->where('status', 'active')->update(['is_active' => true]);
        DB::table('suppliers')->where('status', 'inactive')->update(['is_active' => false]);

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('notes');
            $table->dropColumn('status');
            $table->string('company_name')->nullable()->change();
        });
    }
};
