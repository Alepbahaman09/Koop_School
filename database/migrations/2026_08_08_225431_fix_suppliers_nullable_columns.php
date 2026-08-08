<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Make email, phone, contact_person, and address nullable in suppliers table.
     * The PostgreSQL database had NOT NULL constraints on these columns from before
     * the Laravel migrations were synced with the actual remote schema.
     */
    public function up(): void
    {
        // Use raw SQL to reliably drop NOT NULL constraints on PostgreSQL
        DB::statement('ALTER TABLE suppliers ALTER COLUMN email DROP NOT NULL');
        DB::statement('ALTER TABLE suppliers ALTER COLUMN phone DROP NOT NULL');
        DB::statement('ALTER TABLE suppliers ALTER COLUMN contact_person DROP NOT NULL');
        DB::statement('ALTER TABLE suppliers ALTER COLUMN address DROP NOT NULL');
        DB::statement('ALTER TABLE suppliers ALTER COLUMN company_name DROP NOT NULL');
    }

    public function down(): void
    {
        // Re-apply NOT NULL constraints on rollback
        DB::statement('ALTER TABLE suppliers ALTER COLUMN email SET NOT NULL');
        DB::statement('ALTER TABLE suppliers ALTER COLUMN phone SET NOT NULL');
    }
};
