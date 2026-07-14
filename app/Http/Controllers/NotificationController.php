<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        if ($filter === 'stock') {
            $query->whereIn('type', ['stock_low', 'stock_out']);
        }

        $notifications = $query->simplePaginate(12)->withQueryString();
        $stats = $this->stats();

        return view('notifications', compact('notifications', 'stats', 'filter'));
    }

    public function changes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);
        $afterId = (int) ($validated['after_id'] ?? 0);
        $notifications = AdminNotification::query()
            ->where('id', '>', $afterId)
            ->oldest('id')
            ->limit(20)
            ->get();
        $latestId = (int) ($notifications->last()?->id ?? $afterId);

        return response()->json([
            'latest_id' => $latestId,
            'stats' => $this->stats(),
            'notifications' => $notifications->map(fn (AdminNotification $notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'category' => $notification->isOrderNotification() ? 'orders' : 'stock',
                'severity' => in_array($notification->type, ['stock_low', 'stock_out'], true) ? 'error' : 'success',
                'html' => view('partials.notification-item', compact('notification'))->render(),
            ])->values(),
        ])->header('Cache-Control', 'no-store');
    }

    private function stats(): array
    {
        $stats = (array) AdminNotification::query()
            ->toBase()
            ->selectRaw(
                <<<'SQL'
                COUNT(*) as total,
                COUNT(CASE WHEN read_at IS NULL THEN 1 END) as unread,
                COUNT(CASE WHEN type IN ('order_created', 'order_received') THEN 1 END) as orders,
                COUNT(CASE WHEN type IN ('stock_low', 'stock_out') THEN 1 END) as stock
                SQL
            )
            ->first();

        return [
            'total' => (int) $stats['total'],
            'unread' => (int) $stats['unread'],
            'orders' => (int) $stats['orders'],
            'stock' => (int) $stats['stock'],
        ];
    }

    public function markAllRead()
    {
        AdminNotification::whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function markRead(AdminNotification $notification)
    {
        $destination = $notification->destinationUrl();
        $notification->update(['read_at' => now()]);

        return $destination
            ? redirect($destination)
            : back()->with('success', 'Notification marked as read.');
    }
}
