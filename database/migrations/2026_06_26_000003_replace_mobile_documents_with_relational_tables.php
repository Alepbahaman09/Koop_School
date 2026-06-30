<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'mobile_reference')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('mobile_reference')->nullable()->unique();
            });
        }

        if (Schema::hasColumn('orders', 'source_document_path')) {
            DB::statement('UPDATE orders SET mobile_reference = source_document_path WHERE mobile_reference IS NULL AND source_document_path IS NOT NULL');

            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('source_document_path');
            });
        }

        Schema::dropIfExists('mobile_documents');
    }

    public function down(): void
    {
        if (! Schema::hasTable('mobile_documents')) {
            Schema::create('mobile_documents', function (Blueprint $table) {
                $table->id();
                $table->text('path')->unique();
                $table->text('collection_path')->index();
                $table->string('document_id')->index();
                $table->jsonb('data');
                $table->timestampsTz();
            });
        }

        if (! Schema::hasColumn('orders', 'source_document_path')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->text('source_document_path')->nullable()->unique();
            });
        }

        if (Schema::hasColumn('orders', 'mobile_reference')) {
            DB::statement('UPDATE orders SET source_document_path = mobile_reference WHERE source_document_path IS NULL AND mobile_reference IS NOT NULL');

            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('mobile_reference');
            });
        }
    }
};
