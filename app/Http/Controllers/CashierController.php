<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\TerminalPayment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CashierController extends Controller
{
    // ─────────────────────────────────────────────────────────
    // POST /cashier/sale
    // Dedicated endpoint for the cashier terminal POS.
    // Completely separate from PaymentController (used by app).
    // ─────────────────────────────────────────────────────────
    public function sale(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|distinct|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'payment_method' => 'required|in:Cash,NFC Card',
            'cash_received' => 'required_if:payment_method,Cash|nullable|numeric|min:0',
            'card_uid' => 'required_if:payment_method,NFC Card|nullable|string|max:128',
        ]);

        $items = $request->items;
        $paymentMethod = $request->payment_method;
        $cashReceived = (float) ($request->cash_received ?? 0);
        $cardUid = $request->card_uid;

        // ── Main transaction ────────────────────────────────────
        try {
            DB::beginTransaction();

            // Lock products before checking stock so two cashiers cannot sell
            // the same final item.
            $productIds = array_column($items, 'product_id');
            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $total = 0;
            foreach ($items as $item) {
                $product = $products->get($item['product_id']);

                if (! $product) {
                    DB::rollBack();

                    return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
                }

                if ($product->stock_quantity < $item['qty']) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for \"{$product->name}\" (available: {$product->stock_quantity}).",
                    ], 422);
                }

                $total += (float) $product->price * (int) $item['qty'];
            }
            $total = round($total, 2);

            if ($paymentMethod === 'Cash' && $cashReceived < $total) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Cash received is less than total amount.',
                ], 422);
            }

            // The row lock keeps the balance stable until this sale commits.
            $card = null;
            if ($paymentMethod === 'NFC Card') {
                $card = Card::where('card_uid', $cardUid)->lockForUpdate()->first();

                if (! $card) {
                    DB::rollBack();

                    return response()->json(['success' => false, 'message' => 'NFC Card not found.'], 404);
                }

                if ($card->is_frozen) {
                    DB::rollBack();

                    return response()->json(['success' => false, 'message' => 'NFC Card is frozen.'], 422);
                }

                if ((float) $card->balance < $total) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient card balance. Available: RM '.number_format($card->balance, 2),
                    ], 422);
                }
            }

            // Find or create default walk-in user & customer for database integrity
            $user = User::where('email', 'pos@koop.school')->first();
            if (! $user) {
                $user = User::create([
                    'name' => 'POS Walk-in',
                    'email' => 'pos@koop.school',
                    'password' => Hash::make(Str::random(16)),
                    'wallet_balance' => 0,
                    'email_verified_at' => now(),
                ]);
            }

            $customer = Customer::where('student_id', 'POS-WALKIN')->first();
            if (! $customer) {
                $customer = Customer::create([
                    'student_id' => 'POS-WALKIN',
                    'student_name' => 'Walk-in Customer',
                    'parent_name' => 'Walk-in Parent',
                    'email' => 'pos@koop.school',
                    'phone' => '-',
                    'class' => '-',
                    'address' => '-',
                ]);
            }

            // 1. Create order
            $orderNumber = $this->generateOrderNumber();
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'status' => Order::STATUS_COMPLETED,
                'payment_status' => 'Paid',
                'subtotal' => $total,
                'tax' => 0,
                'discount' => 0,
                'total_amount' => $total,
                'notes' => 'POS cashier terminal sale',
            ]);

            // 2. Create order items + deduct stock
            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $qty = (int) $item['qty'];
                $lineTotal = round($product->price * $qty, 2);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'subtotal' => $lineTotal,
                ]);

                $product->decrement('stock_quantity', $qty);
            }

            // 3. Process payment
            $paymentReference = 'POS-'.($paymentMethod === 'NFC Card' ? 'NFC' : 'CASH')
                .'-'.date('YmdHis').'-'.str_pad($order->id, 5, '0', STR_PAD_LEFT);

            $paymentNotes = $paymentMethod === 'Cash'
                ? 'Cash payment. Received: RM '.number_format($cashReceived, 2).', Change: RM '.number_format($cashReceived - $total, 2)
                : 'NFC Card payment. Card: '.$cardUid.' ('.$card->owner.')';

            TerminalPayment::create([
                'order_id' => $order->id,
                'payment_reference' => $paymentReference,
                'payment_method' => $paymentMethod,
                'amount' => $total,
                'status' => 'Completed',
                'paid_at' => now(),
                'notes' => $paymentNotes,
            ]);

            // 4. Deduct NFC card balance
            if ($paymentMethod === 'NFC Card') {
                $card->decrement('balance', $total);
                $card->update(['last_used_at' => now()]);
            }

            // 5. Status history
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'admin_id' => auth()->id(),
                'status' => Order::STATUS_COMPLETED,
                'notes' => 'Completed via POS cashier terminal ('.$paymentMethod.')',
            ]);

            DB::commit();

            // 6. Bust caches so Analytics/Finance/Dashboard refresh
            $this->bustSalesCaches();

            // 7. Build response
            $response = [
                'success' => true,
                'order_number' => $orderNumber,
                'payment_reference' => $paymentReference,
                'total' => $total,
                'payment_method' => $paymentMethod,
            ];

            if ($paymentMethod === 'Cash') {
                $response['cash_received'] = $cashReceived;
                $response['change'] = round($cashReceived - $total, 2);
            } else {
                $response['card_owner'] = $card->owner;
                $response['remaining_balance'] = number_format($card->fresh()->balance, 2);
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS Sale Failed: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sale could not be completed. Please retry.',
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────
    // POST /cashier/card-lookup
    // Quick NFC card info lookup (name + balance) before sale.
    // ─────────────────────────────────────────────────────────
    public function cardLookup(Request $request): JsonResponse
    {
        $request->validate(['card_uid' => 'required|string|max:128']);

        $card = Card::where('card_uid', $request->card_uid)->first();

        if (! $card) {
            return response()->json(['success' => false, 'message' => 'Card not found.'], 404);
        }

        if ($card->is_frozen) {
            return response()->json(['success' => false, 'message' => 'Card is frozen.'], 422);
        }

        return response()->json([
            'success' => true,
            'owner' => $card->owner,
            'balance' => number_format($card->balance, 2),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // GET /cashier/history
    // Returns recent POS orders for the Order History modal.
    // ─────────────────────────────────────────────────────────
    public function history(): JsonResponse
    {
        $orders = Order::where('notes', 'like', '%POS cashier%')
            ->with(['terminalPayments' => fn ($q) => $q->where('status', 'Completed')->latest()->limit(1)])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->order_number,
                'date' => $o->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i'),
                'total' => (float) $o->total_amount,
                'method' => $o->terminalPayments->first()?->payment_method ?? '—',
                'status' => $o->payment_status === 'Paid' ? 'Paid' : 'Pending',
            ]);

        return response()->json(['success' => true, 'data' => $orders]);
    }

    // ─────────────────────────────────────────────────────────
    // GET /cashier/order/{orderNumber}
    // Returns full detail (items, payment) for a single POS order.
    // ─────────────────────────────────────────────────────────
    public function orderDetail(string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->with([
                'orderItems.product',
                'terminalPayments' => fn ($q) => $q->where('status', 'Completed')->latest()->limit(1),
            ])
            ->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        $payment = $order->terminalPayments->first();

        $items = $order->orderItems->map(fn ($item) => [
            'name' => $item->product->name ?? '—',
            'qty' => $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'subtotal' => (float) $item->subtotal,
        ]);

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'date' => $order->created_at->timezone(config('app.timezone'))->format('d M Y, h:i A'),
            'total' => (float) $order->total_amount,
            'payment_method' => $payment?->payment_method ?? '—',
            'status' => $order->payment_status === 'Paid' ? 'Paid' : 'Pending',
            'notes' => $payment?->notes ?? '',
            'items' => $items,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────
    private function generateOrderNumber(): string
    {
        $prefix = 'POS-'.date('Ymd').'-';
        $last = Order::where('order_number', 'like', $prefix.'%')
            ->orderByDesc('order_number')
            ->value('order_number');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function bustSalesCaches(): void
    {
        Cache::forget('dashboard.order_totals.current_statuses');
        Cache::forget('dashboard.recent_payments');
        Cache::forget('dashboard.customer_totals');
        Cache::forget('dashboard.product_totals');

        foreach ([7, 30, 90, 365] as $days) {
            Cache::forget("analytics.index.top_items.{$days}");
        }

        Cache::forget('finance.index.'.now()->format('Y-m'));
        Cache::forget('finance.index.'.now()->subMonthNoOverflow()->format('Y-m'));

        $today = now()->toDateString();
        for ($i = 0; $i <= 30; $i++) {
            Cache::forget('dashboard.sales_by_date.'.now()->subDays($i + 9)->toDateString().'.'.$today);
        }
    }
}
