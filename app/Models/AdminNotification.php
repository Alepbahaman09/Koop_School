<?php

namespace App\Models;

use App\Models\Concerns\UsesUtcDatabaseTimestamps;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    use UsesUtcDatabaseTimestamps;

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

    public static function forOrder(Order $order, string $source = 'mobile'): self
    {
        $order->loadMissing('customer');
        $customer = $order->customer?->parent_name ?? $order->customer?->student_name ?? 'Unknown customer';

        return self::create([
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

    }

    public static function forStock(Product $product, string $level): self
    {
        $outOfStock = $level === 'out';

        return self::create([
            'type' => $outOfStock ? 'stock_out' : 'stock_low',
            'title' => $outOfStock ? 'Item out of stock' : 'Low stock alert',
            'message' => $outOfStock
                ? "{$product->name} is out of stock."
                : "{$product->name} has {$product->stock_quantity} item(s) remaining. Minimum stock level: {$product->min_stock_level}.",
            'link' => route('products.index', ['stock' => $outOfStock ? 'out' : 'low'], false),
            'data' => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'stock_quantity' => $product->stock_quantity,
                'min_stock_level' => $product->min_stock_level,
                'level' => $level,
            ],
        ]);
    }

    public function isOrderNotification(): bool
    {
        return in_array($this->type, ['order_created', 'order_received'], true)
            || (bool) preg_match('#^/orders/\d+$#', (string) $this->link);
    }

    public function destinationUrl(): ?string
    {
        if (! $this->isOrderNotification()) {
            return $this->link;
        }

        $data = is_array($this->data) ? $this->data : [];
        $orderNumber = $data['order_number'] ?? null;
        $orderId = $data['order_id'] ?? null;

        if (! $orderId && preg_match('#^/orders/(\d+)$#', (string) $this->link, $matches)) {
            $orderId = $matches[1];
        }

        if (! $orderNumber && $orderId) {
            $orderNumber = Order::query()->whereKey($orderId)->value('order_number');
        }

        return route(
            'orders.index',
            $orderNumber ? ['search' => $orderNumber] : [],
            false,
        );
    }
}
