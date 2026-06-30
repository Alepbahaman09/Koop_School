<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->string('filter')->toString();
        $query = AdminNotification::query()->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        if ($filter === 'orders') {
            $query->whereIn('type', ['order_created', 'order_received']);
        }

        $notifications = $query->simplePaginate(12)->withQueryString();
        $stats = Cache::remember('admin_notifications.stats', 15, fn () => (array) AdminNotification::query()
            ->toBase()
            ->selectRaw(
                <<<'SQL'
                COUNT(*) as total,
                COUNT(CASE WHEN read_at IS NULL THEN 1 END) as unread,
                COUNT(CASE WHEN type IN ('order_created', 'order_received') THEN 1 END) as orders
                SQL
            )
            ->first());
        $stats = [
            'total' => (int) $stats['total'],
            'unread' => (int) $stats['unread'],
            'orders' => (int) $stats['orders'],
        ];
        Cache::put('admin_notifications.unread_count', $stats['unread'], 15);

        return view('notifications', compact('notifications', 'stats', 'filter'));
    }

    public function markAllRead()
    {
        AdminNotification::whereNull('read_at')->update(['read_at' => now()]);
        Cache::forget('admin_notifications.unread_count');
        Cache::forget('admin_notifications.stats');

        return back()->with('success', 'All notifications marked as read.');
    }

    public function markRead(AdminNotification $notification)
    {
        $destination = $notification->destinationUrl();
        $notification->update(['read_at' => now()]);
        Cache::forget('admin_notifications.unread_count');
        Cache::forget('admin_notifications.stats');

        return $destination
            ? redirect($destination)
            : back()->with('success', 'Notification marked as read.');
    }
}
