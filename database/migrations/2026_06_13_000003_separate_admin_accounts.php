<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('admin_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreignId('admin_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->change();
        });
        Schema::table('order_status_history', function (Blueprint $table) {
            $table->foreignId('admin_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->change();
        });
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('admin_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->change();
        });

        $adminMap = [];
        foreach (DB::table('users')->where('is_admin', true)->get() as $user) {
            $adminId = DB::table('admins')->insertGetId([
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'password' => $user->password,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
            $adminMap[$user->id] = $adminId;
        }

        foreach ($adminMap as $userId => $adminId) {
            DB::table('inventory_transactions')->where('user_id', $userId)->update([
                'admin_id' => $adminId,
                'user_id' => null,
            ]);
            DB::table('order_status_history')->where('user_id', $userId)->update([
                'admin_id' => $adminId,
                'user_id' => null,
            ]);
            DB::table('purchase_orders')->where('user_id', $userId)->update([
                'admin_id' => $adminId,
                'user_id' => null,
            ]);
        }

        DB::table('users')->where('is_admin', true)->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->index();
        });

        foreach (DB::table('admins')->get() as $admin) {
            DB::table('users')->insert([
                'name' => $admin->name,
                'email' => $admin->email,
                'email_verified_at' => $admin->email_verified_at,
                'password' => $admin->password,
                'remember_token' => $admin->remember_token,
                'is_admin' => true,
                'created_at' => $admin->created_at,
                'updated_at' => $admin->updated_at,
            ]);
        }

        Schema::table('inventory_transactions', fn (Blueprint $table) => $table->dropConstrainedForeignId('admin_id'));
        Schema::table('order_status_history', fn (Blueprint $table) => $table->dropConstrainedForeignId('admin_id'));
        Schema::table('purchase_orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('admin_id'));
        Schema::dropIfExists('admin_password_reset_tokens');
        Schema::dropIfExists('admins');
    }
};
