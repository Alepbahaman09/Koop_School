<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AdminNotification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'message',
        'link',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public static function forOrder(Order $order, string $source = 'mobile'): ?self
    {
        if (! AppSetting::current()->notificationPreference('new_order_alerts')) {
            return null;
        }

        $order->loadMissing('customer');
        $customer = $order->customer?->parent_name ?? $order->customer?->student_name ?? 'Unknown customer';

        $notification = self::create([
            'type' => 'order_created',
            'title' => 'New order received',
            'message' => "{$customer} placed order {$order->order_number} for RM ".number_format((float) $order->total_amount, 2).'.',
            'link' => route('orders.index', ['search' => $order->order_number], false),
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer' => $customer,
                'amount' => (float) $order->total_amount,
                'source' => $source,
            ],
        ]);

        Cache::forget('admin_notifications.unread_count');
        Cache::forget('admin_notifications.stats');

        return $notification;
    }
}
