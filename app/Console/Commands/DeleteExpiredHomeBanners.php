<?php

namespace App\Console\Commands;

use App\Services\ExpiredHomeBannerCleanup;
use Illuminate\Console\Command;

class DeleteExpiredHomeBanners extends Command
{
    protected $signature = 'banners:delete-expired';

    protected $description = 'Delete expired home banners and their stored images';

    public function handle(ExpiredHomeBannerCleanup $cleanup): int
    {
        $deleted = count($cleanup->run());

        $this->info("Deleted {$deleted} expired banner(s).");

        return self::SUCCESS;
    }
}
