<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const STATUSES = ['Processing', 'Ready', 'Completed', 'Cancelled'];

    public function up(): void
    {
        DB::table('orders')->where('status', 'Pending')->update(['status' => 'Processing']);
        DB::table('orders')->where('status', 'Packed')->update(['status' => 'Ready']);

        DB::table('order_status_history')->where('status', 'Pending')->update(['status' => 'Processing']);
        DB::table('order_status_history')->where('status', 'Packed')->update(['status' => 'Ready']);

        $this->changeAllowedStatuses(self::STATUSES, 'Processing');
    }

    public function down(): void
    {
        $this->changeAllowedStatuses(
            ['Pending', 'Processing', 'Packed', 'Ready', 'Completed', 'Cancelled'],
            'Pending'
        );
    }

    private function changeAllowedStatuses(array $statuses, string $default): void
    {
        $statusList = "'".implode("','", $statuses)."'";

        match (DB::getDriverName()) {
            'pgsql' => $this->changePostgresStatuses($statusList, $default),
            'mysql', 'mariadb' => DB::statement(
                "ALTER TABLE orders MODIFY status ENUM({$statusList}) NOT NULL DEFAULT '{$default}'"
            ),
            default => null,
        };
    }

    private function changePostgresStatuses(string $statusList, string $default): void
    {
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check');
        DB::statement("ALTER TABLE orders ALTER COLUMN status SET DEFAULT '{$default}'");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN ({$statusList}))");
    }
};
