<?php

namespace App\Console\Commands;

use App\Services\FcmQueue;
use Illuminate\Console\Command;
use Throwable;

class ListenForFcmNotifications extends Command
{
    protected $signature = 'fcm:listen {--interval=1 : Seconds between queue checks}';

    protected $description = 'Continuously deliver pending mobile FCM notifications';

    public function handle(FcmQueue $queue): int
    {
        $lockHandle = fopen(storage_path('fcm-listener.lock'), 'c');
        if ($lockHandle === false || ! flock($lockHandle, LOCK_EX | LOCK_NB)) {
            $this->warn('Another FCM listener is already running.');

            return self::SUCCESS;
        }

        $interval = max(1, (int) $this->option('interval'));
        $this->info("Listening for FCM notifications every {$interval} second(s). Press Ctrl+C to stop.");

        while (true) {
            try {
                $queue->processPending();
            } catch (Throwable $error) {
                report($error);
                $this->error($error->getMessage());
            }

            sleep($interval);
        }
    }
}
