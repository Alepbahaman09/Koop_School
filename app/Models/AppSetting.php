<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'store_name',
        'store_email',
        'store_phone',
        'store_address',
        'currency',
        'notification_preferences',
    ];

    protected $casts = [
        'notification_preferences' => 'array',
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'store_name' => config('app.name', 'Koop School'),
            'notification_preferences' => self::defaultNotificationPreferences(),
        ]);
    }

    public static function defaultNotificationPreferences(): array
    {
        return [
            'email_notifications' => false,
            'new_order_alerts' => true,
            'payment_alerts' => true,
            'low_stock_alerts' => true,
        ];
    }

    public function notificationPreference(string $key): bool
    {
        $preferences = array_replace(
            self::defaultNotificationPreferences(),
            $this->notification_preferences ?? [],
        );

        return (bool) ($preferences[$key] ?? false);
    }
}
