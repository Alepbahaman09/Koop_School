<?php

namespace App\Models\Concerns;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;

trait UsesUtcDatabaseTimestamps
{
    public function freshTimestamp()
    {
        return Carbon::now('UTC');
    }

    public function fromDateTime($value)
    {
        return Carbon::parse($value)->timezone('UTC')->format($this->getDateFormat());
    }

    protected function asDateTime($value)
    {
        $timezone = config('app.timezone', 'Asia/Kuala_Lumpur');

        if ($value instanceof CarbonInterface) {
            return $value->copy()->timezone($timezone);
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->timezone($timezone);
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value, 'UTC')->timezone($timezone);
        }

        if (is_string($value)) {
            return Carbon::parse($value, 'UTC')->timezone($timezone);
        }

        return parent::asDateTime($value);
    }
}
