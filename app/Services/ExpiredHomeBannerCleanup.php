<?php

namespace App\Services;

use App\Models\HomeBanner;
use Throwable;

class ExpiredHomeBannerCleanup
{
    public function __construct(private readonly SupabaseStorage $storage) {}

    /** @return array<int, int> */
    public function run(): array
    {
        $deletedIds = [];

        HomeBanner::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->eachById(function (HomeBanner $banner) use (&$deletedIds): void {
                try {
                    $this->storage->deletePublicFile(
                        $banner->image_url,
                        HomeBanner::IMAGE_BUCKET,
                    );
                    $banner->delete();
                    $deletedIds[] = $banner->id;
                } catch (Throwable $exception) {
                    report($exception);
                }
            });

        return $deletedIds;
    }
}
