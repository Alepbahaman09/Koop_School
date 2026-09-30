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

        $notificationIds = DB::transaction(function () use ($limit) {
            return DB::table('fcm_notification_queue')
                ->whereNull('sent_at')
                ->where('attempts', '<', 5)
                ->orderBy('id')
                ->limit($limit)
                ->lock('FOR UPDATE SKIP LOCKED')
                ->pluck('notification_id')
                ->all();
        });

        foreach ($notificationIds as $id) {
            $this->process((int) $id);
        }
    }

    public function process(int $notificationId): bool
    {
        $claimed = DB::transaction(function () use ($notificationId) {
            $queue = DB::table('fcm_notification_queue')
                ->where('notification_id', $notificationId)
                ->whereNull('sent_at')
                ->where('attempts', '<', 5)
                ->lockForUpdate()
                ->first();

            if (! $queue) {
                return null;
            }

            DB::table('fcm_notification_queue')
                ->where('id', $queue->id)
                ->update([
                    'attempts' => DB::raw('attempts + 1'),
                    'sent_at' => now(),
                ]);

            return $queue;
        });

        if (! $claimed) {
            return true;
        }

        try {
            $this->fcm->sendNotification($notificationId);

            return true;
        } catch (Throwable $error) {
            report($error);
            DB::table('fcm_notification_queue')
                ->where('id', $claimed->id)
                ->update([
                    'sent_at' => null,
                    'last_error' => $error->getMessage(),
                ]);

            return false;
        }
    }
}
