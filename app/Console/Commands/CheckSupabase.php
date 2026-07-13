<?php

namespace App\Console\Commands;

use App\Services\SupabaseClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckSupabase extends Command
{
    protected $signature = 'supabase:check {--anon : Use the anon key for the REST API check}';

    protected $description = 'Check the Supabase PostgreSQL connection and REST API configuration.';

    public function handle(SupabaseClient $supabase): int
    {
        $failed = false;

        try {
            DB::connection('pgsql')->select('select 1');
            $this->info('Database connection: OK');
        } catch (Throwable $exception) {
            $failed = true;
            $this->error('Database connection: FAILED');
            $this->line($exception->getMessage());
        }

        try {
            $response = $supabase
                ->rest(! (bool) $this->option('anon'))
                ->get('/products', ['select' => 'id', 'limit' => 1]);

            if ($response->successful()) {
                $this->info('Supabase REST API: OK');
            } else {
                $failed = true;
                $this->error('Supabase REST API: FAILED');
                $this->line('HTTP '.$response->status().': '.$response->body());
            }
        } catch (Throwable $exception) {
            $failed = true;
            $this->error('Supabase REST API: FAILED');
            $this->line($exception->getMessage());
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
