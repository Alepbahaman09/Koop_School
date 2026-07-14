<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

class FcmQueue
{
    public function __construct(private readonly FcmService $fcm) {}

    public function processPending(int $limit = 100): void
    {
        if (! $this->fcm->isConfigured()) {
            return;
        }

        DB::table('fcm_notification_queue')
            ->whereNull('sent_at')
            ->where('attempts', '<', 5)
            ->orderBy('id')
            ->limit($limit)
            ->pluck('notification_id')
            ->each(fn ($id) => $this->process((int) $id));
    }

    public function process(int $notificationId): bool
    {
        $queue = DB::table('fcm_notification_queue')
            ->where('notification_id', $notificationId)
            ->whereNull('sent_at')
            ->first();

        if (! $queue) {
            return true;
        }

        DB::table('fcm_notification_queue')
            ->where('id', $queue->id)
            ->increment('attempts');

        try {
            $this->fcm->sendNotification($notificationId);
            DB::table('fcm_notification_queue')
                ->where('id', $queue->id)
                ->update(['sent_at' => now(), 'last_error' => null]);

            return true;
        } catch (Throwable $error) {
            report($error);
            DB::table('fcm_notification_queue')
                ->where('id', $queue->id)
                ->update(['last_error' => $error->getMessage()]);

            return false;
        }
    }
}
