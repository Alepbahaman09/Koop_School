<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Card;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\MobileUserProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MobileRelationalController extends Controller
{
    public function query(Request $request)
    {
        $data = $request->validate([
            'collection' => 'required|string',
            'field' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_]+$/'],
            'equals' => 'nullable',
            'order_by' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_]+$/'],
            'descending' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:1000',
            'collection_group' => 'nullable|boolean',
        ]);

        $collection = trim($data['collection'], '/');
        $limit = $data['limit'] ?? 1000;

        if ($collection === 'items') {
            $products = Product::with('category');
            if (! empty($data['field']) && array_key_exists('equals', $data) && in_array($data['field'], ['name', 'sku'], true)) {
                $products->where($data['field'], $data['equals']);
            }
            if (! empty($data['order_by']) && in_array($data['order_by'], ['name', 'sku', 'price', 'stock'], true)) {
                $column = $data['order_by'] === 'stock' ? 'stock_quantity' : $data['order_by'];
                $products->orderBy($column, $request->boolean('descending') ? 'desc' : 'asc');
            }

            return response()->json(['documents' => $products->limit($limit)->get()->map(fn ($product) => $this->productDocument($product))]);
        }

        if ($collection === 'users') {
            abort(403, 'User directory queries are not allowed.');
        }

        $this->authorizeCollectionRead($request, $collection, $request->boolean('collection_group'));

        if ($this->isCardsCollection($request, $collection)) {
            $cards = Card::where('user_id', $request->user()->id);
            if (! empty($data['field']) && array_key_exists('equals', $data)) {
                $this->applyCardFilter($cards, $data['field'], $data['equals']);
            }
            $this->applyCardOrder($cards, $data['order_by'] ?? null, $request->boolean('descending'));

            return response()->json(['documents' => $cards->limit($limit)->get()->map(fn ($card) => $this->cardDocument($card))]);
        }

        if ($this->isTransactionsCollection($request, $collection)) {
            $orders = Order::with(['orderItems.product', 'payments'])
                ->where('user_id', $request->user()->id);
            if (! empty($data['field']) && array_key_exists('equals', $data)) {
                $this->applyOrderFilter($orders, $data['field'], $data['equals']);
            }
            $this->applyOrderSort($orders, $data['order_by'] ?? null, $request->boolean('descending'));

            return response()->json(['documents' => $orders->limit($limit)->get()->map(fn ($order) => $this->transactionDocument($order))]);
        }

        return response()->json(['documents' => []]);
    }

    public function show(Request $request, string $path)
    {
        $path = trim($path, '/');
        $this->authorizeReadPath($request, $path);

        if ($path === 'users/'.$request->user()->id) {
            return response()->json($this->userDocumentFor($request->user()));
        }

        if (preg_match('#^items/(\d+)$#', $path, $matches)) {
            $product = Product::with('category')->find($matches[1]);

            return $product ? response()->json($this->productDocument($product)) : response()->json(['message' => 'Not found'], 404);
        }

        if (preg_match('#^users/(\d+)/cards/([^/]+)$#', $path, $matches)) {
            $card = Card::where('user_id', $matches[1])->where('card_uid', $matches[2])->first();

            return $card ? response()->json($this->cardDocument($card)) : response()->json(['message' => 'Not found'], 404);
        }

        if (preg_match('#^users/(\d+)/transactions/([^/]+)$#', $path, $matches)) {
            $order = $this->findUserOrderByDocumentPath((int) $matches[1], $path, $matches[2]);

            return $order ? response()->json($this->transactionDocument($order)) : response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(['message' => 'Not found'], 404);
    }

    public function cardExists(string $cardUid)
    {
        return response()->json(['exists' => Card::where('card_uid', $cardUid)->exists()]);
    }

    public function upsert(Request $request, string $path)
    {
        $payload = $request->validate(['data' => 'required|array', 'merge' => 'nullable|boolean']);
        $path = trim($path, '/');
        $this->authorizeUserPath($request, $path);

        $document = DB::transaction(function () use ($request, $payload, $path) {
            if ($path === 'users/'.$request->user()->id) {
                return $this->updateUserProfile($request->user(), $payload['data'], $request->boolean('merge'));
            }

            if (preg_match('#^users/(\d+)/cards/([^/]+)$#', $path, $matches)) {
                return $this->upsertCard((int) $matches[1], $matches[2], $payload['data'], $request->boolean('merge'));
            }

            if (preg_match('#^users/(\d+)/transactions/([^/]+)$#', $path, $matches)) {
                return $this->createPurchaseOrder((int) $matches[1], $path, $payload['data']);
            }

            return ['id' => basename($path), 'path' => $path, 'data' => $payload['data']];
        });

        return response()->json($document);
    }

    public function destroy(Request $request, string $path)
    {
        $path = trim($path, '/');
        $this->authorizeUserPath($request, $path);
        DB::transaction(fn () => $this->deleteRelationalPath($path));

        return response()->json(['success' => true]);
    }

    public function batch(Request $request)
    {
        $operations = $request->validate([
            'operations' => 'required|array|min:1|max:500',
            'operations.*.type' => 'required|in:set,update,delete',
            'operations.*.path' => 'required|string',
            'operations.*.data' => 'nullable|array',
            'operations.*.merge' => 'nullable|boolean',
        ])['operations'];

        DB::transaction(function () use ($operations, $request) {
            foreach ($operations as $operation) {
                $path = trim($operation['path'], '/');
                $this->authorizeUserPath($request, $path);
                if ($operation['type'] === 'delete') {
                    $this->deleteRelationalPath($path);

                    continue;
                }

                if ($path === 'users/'.$request->user()->id) {
                    $this->updateUserProfile($request->user(), $operation['data'] ?? [], $operation['type'] === 'update' || ($operation['merge'] ?? false));
                } elseif (preg_match('#^users/(\d+)/cards/([^/]+)$#', $path, $matches)) {
                    $this->upsertCard((int) $matches[1], $matches[2], $operation['data'] ?? [], $operation['type'] === 'update' || ($operation['merge'] ?? false));
                } elseif (preg_match('#^users/(\d+)/transactions/([^/]+)$#', $path, $matches)) {
                    $this->createPurchaseOrder((int) $matches[1], $path, $operation['data'] ?? []);
                }
            }
        });

        return response()->json(['success' => true]);
    }

    private function updateUserProfile(User $user, array $incoming, bool $merge): array
    {
        $lockedUser = User::lockForUpdate()->findOrFail($user->id);
        $profileRow = MobileUserProfile::where('user_id', $lockedUser->id)->lockForUpdate()->first();
        $current = array_merge($profileRow?->profile ?? [], ['balance' => (float) $lockedUser->wallet_balance]);
        $profile = $merge
            ? array_replace_recursive($current, $this->resolve($incoming, $current))
            : $this->resolve($incoming, []);

        abort_if((float) ($profile['balance'] ?? $lockedUser->wallet_balance) < 0, 422, 'Insufficient wallet balance.');

        $lockedUser->update([
            'wallet_balance' => $profile['balance'] ?? $lockedUser->wallet_balance,
            'username' => $profile['username'] ?? $lockedUser->username,
            'phone_number' => $profile['phoneNumber'] ?? $profile['phone_number'] ?? $lockedUser->phone_number,
        ]);
        MobileUserProfile::updateOrCreate(['user_id' => $lockedUser->id], ['profile' => $profile]);
        $this->syncCustomerProfile($lockedUser);

        return $this->userDocumentFor($lockedUser->refresh());
    }

    private function upsertCard(int $userId, string $cardUid, array $incoming, bool $merge): array
    {
        $existing = Card::where('card_uid', $cardUid)->lockForUpdate()->first();
        abort_if($existing && $existing->user_id !== $userId, 422, 'This NFC card is already registered.');

        $current = $existing ? $this->cardData($existing) : ['cardUid' => $cardUid, 'balance' => 0, 'isFrozen' => false];
        $data = $merge ? array_replace_recursive($current, $this->resolve($incoming, $current)) : $this->resolve(array_merge(['cardUid' => $cardUid], $incoming), $current);

        abort_if((float) ($data['balance'] ?? 0) < 0, 422, 'Insufficient card balance.');

        $card = Card::updateOrCreate(['card_uid' => $cardUid], [
            'user_id' => $userId,
            'owner' => $data['owner'] ?? $data['cardName'] ?? 'Card',
            'balance' => $data['balance'] ?? 0,
            'is_frozen' => $data['isFrozen'] ?? $data['is_frozen'] ?? false,
            'last_used_at' => $data['lastUsed'] ?? $data['last_used_at'] ?? null,
        ]);

        return $this->cardDocument($card->refresh());
    }

    private function createPurchaseOrder(int $userId, string $path, array $data): array
    {
        $existing = Order::with(['orderItems.product', 'payments'])
            ->where('user_id', $userId)
            ->where('mobile_reference', $path)
            ->first();
        if ($existing) {
            return $this->transactionDocument($existing);
        }

        abort_unless(($data['type'] ?? 'Purchase') === 'Purchase', 422, 'Only purchase transactions are supported.');

        $user = User::lockForUpdate()->findOrFail($userId);
        $customer = Customer::firstOrCreate(['email' => $user->email], [
            'student_id' => 'APP-'.$user->id,
            'parent_name' => $user->username ?: $user->name,
            'student_name' => $user->username ?: $user->name,
            'phone' => $user->phone_number ?: '-',
            'class' => '-',
            'address' => '-',
        ]);

        $requestedItems = collect($data['items'] ?? []);
        $items = $requestedItems->map(function ($item) {
            $product = isset($item['product_id']) || isset($item['id'])
                ? Product::lockForUpdate()->find($item['product_id'] ?? $item['id'])
                : Product::where('name', $item['name'] ?? '')->lockForUpdate()->first();

            return $product ? ['product' => $product, 'quantity' => max(1, (int) ($item['quantity'] ?? 1))] : null;
        })->filter()->values();

        abort_if($items->count() !== $requestedItems->count(), 422, 'One or more products are unavailable.');

        foreach ($items as $item) {
            abort_if($item['product']->stock_quantity < $item['quantity'], 422, "{$item['product']->name} does not have enough stock.");
        }

        $subtotal = $items->isEmpty()
            ? (float) ($data['amount'] ?? 0)
            : $items->sum(fn ($item) => (float) $item['product']->price * $item['quantity']);
        $totalAmount = $items->isEmpty() ? (float) ($data['amount'] ?? 0) : $subtotal;

        abort_if($totalAmount <= 0, 422, 'Purchase amount must be greater than zero.');

        $this->deductPurchaseBalance($user, $data, $totalAmount);

        $orderNumber = $this->uniqueOrderNumber($path, $data['orderNumber'] ?? null);
        $order = Order::create([
            'order_number' => $orderNumber,
            'mobile_reference' => $path,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => Order::DEFAULT_STATUS,
            'subtotal' => $subtotal,
            'tax' => 0,
            'discount' => 0,
            'total_amount' => $totalAmount,
            'payment_status' => 'Paid',
            'notes' => $data['note'] ?? null,
        ]);

        foreach ($items as $item) {
            $stockBefore = $item['product']->stock_quantity;
            $stockAfter = $stockBefore - $item['quantity'];

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product']->id,
                'quantity' => $item['quantity'],
                'unit_price' => $item['product']->price,
                'subtotal' => (float) $item['product']->price * $item['quantity'],
            ]);

            $item['product']->update(['stock_quantity' => $stockAfter]);

            InventoryTransaction::create([
                'product_id' => $item['product']->id,
                'user_id' => $user->id,
                'type' => 'Out',
                'quantity' => $item['quantity'],
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'notes' => 'Stock reduced by mobile purchase',
            ]);
        }

        Payment::create([
            'order_id' => $order->id,
            'payment_reference' => 'APP-PAY-'.$order->id.'-'.now()->format('YmdHis'),
            'payment_method' => $this->paymentMethod($data),
            'amount' => $totalAmount,
            'status' => 'Completed',
            'paid_at' => now(),
            'notes' => $data['note'] ?? null,
        ]);

        $this->bustSalesCaches();

        AdminNotification::forOrder($order->load('customer'), 'mobile_sql');

        return $this->transactionDocument($order->load(['orderItems.product', 'payments']));
    }

    private function deleteRelationalPath(string $path): void
    {
        if (preg_match('#^users/(\d+)/cards/([^/]+)$#', $path, $matches)) {
            Card::where('user_id', $matches[1])->where('card_uid', $matches[2])->delete();
        }
    }

    private function resolve(array $data, array $current): array
    {
        foreach ($data as $key => $value) {
            if (! is_array($value) || ! isset($value['__op'])) {
                continue;
            }

            if ($value['__op'] === 'timestamp') {
                $data[$key] = now()->toIso8601String();
            }
            if ($value['__op'] === 'increment') {
                $data[$key] = (float) ($current[$key] ?? 0) + (float) ($value['value'] ?? 0);
            }
            if ($value['__op'] === 'delete') {
                unset($data[$key]);
            }
        }

        return $data;
    }

    private function userDocumentFor(User $user): array
    {
        $user->loadMissing('mobileProfile');

        return ['id' => (string) $user->id, 'path' => "users/{$user->id}", 'data' => array_merge($user->mobileProfile?->profile ?? [], [
            'email' => $user->email,
            'username' => $user->username,
            'phoneNumber' => $user->phone_number,
            'balance' => (float) $user->wallet_balance,
        ])];
    }

    private function productDocument(Product $product): array
    {
        return ['id' => (string) $product->id, 'path' => "items/{$product->id}", 'data' => [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $product->price,
            'stock' => $product->stock_quantity,
            'image_url' => $product->image_url,
            'category' => $product->category?->name,
        ]];
    }

    private function cardDocument(Card $card): array
    {
        return ['id' => $card->card_uid, 'path' => "users/{$card->user_id}/cards/{$card->card_uid}", 'data' => $this->cardData($card)];
    }

    private function cardData(Card $card): array
    {
        return [
            'cardUid' => $card->card_uid,
            'cardName' => $card->owner,
            'owner' => $card->owner,
            'balance' => (float) $card->balance,
            'isFrozen' => (bool) $card->is_frozen,
            'lastUsed' => $card->last_used_at?->toIso8601String(),
            'updatedAt' => $card->updated_at?->toIso8601String(),
        ];
    }

    private function transactionDocument(Order $order): array
    {
        $order->loadMissing(['orderItems.product', 'payments']);
        $documentId = $this->transactionDocumentId($order);

        return ['id' => $documentId, 'path' => "users/{$order->user_id}/transactions/{$documentId}", 'data' => [
            'type' => 'Purchase',
            'amount' => (float) $order->total_amount,
            'orderNumber' => $order->order_number,
            'orderStatus' => $order->status,
            'paymentStatus' => $order->payment_status,
            'paidWith' => $order->payments->first()?->payment_method,
            'note' => $order->notes,
            'createdAt' => $order->created_at?->toIso8601String(),
            'items' => $order->orderItems->map(fn ($item) => [
                'id' => $item->product_id,
                'name' => $item->product?->name,
                'quantity' => $item->quantity,
                'price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
            ])->values(),
        ]];
    }

    private function transactionDocumentId(Order $order): string
    {
        if ($order->mobile_reference && preg_match('#/transactions/([^/]+)$#', $order->mobile_reference, $matches)) {
            return $matches[1];
        }

        return 'order-'.$order->id;
    }

    private function findUserOrderByDocumentPath(int $userId, string $path, string $documentId): ?Order
    {
        return Order::with(['orderItems.product', 'payments'])
            ->where('user_id', $userId)
            ->where(function ($query) use ($path, $documentId) {
                $query->where('mobile_reference', $path)
                    ->orWhere('order_number', $documentId);
                if (str_starts_with($documentId, 'order-') && ctype_digit(substr($documentId, 6))) {
                    $query->orWhere('id', (int) substr($documentId, 6));
                }
            })
            ->first();
    }

    private function productUpdates(Product $product, array $incoming): array
    {
        $resolved = $this->resolve($incoming, [
            'stock' => $product->stock_quantity,
            'price' => (float) $product->price,
            'name' => $product->name,
        ]);
        $mapped = [];
        if (array_key_exists('stock', $resolved)) {
            $mapped['stock_quantity'] = $resolved['stock'];
        }
        if (array_key_exists('price', $resolved)) {
            $mapped['price'] = $resolved['price'];
        }
        if (array_key_exists('name', $resolved)) {
            $mapped['name'] = $resolved['name'];
        }
        abort_if((int) ($mapped['stock_quantity'] ?? $product->stock_quantity) < 0, 422, 'Insufficient product stock.');

        return $mapped;
    }

    private function authorizeUserPath(Request $request, string $path): void
    {
        if (preg_match('#^users/(\d+)(?:/|$)#', $path, $matches)) {
            abort_unless((string) $request->user()->id === $matches[1], 403);

            return;
        }

        abort_unless(preg_match('#^(email_notifications|notifications)/[^/]+$#', $path), 403);
    }

    private function authorizeReadPath(Request $request, string $path): void
    {
        if (preg_match('#^items/\d+$#', $path)) {
            return;
        }

        abort_unless(str_starts_with($path, 'users/'.$request->user()->id.'/') || $path === 'users/'.$request->user()->id, 403);
    }

    private function authorizeCollectionRead(Request $request, string $collection, bool $collectionGroup): void
    {
        if ($collectionGroup) {
            abort_unless(in_array($collection, ['cards', 'transactions'], true), 403);

            return;
        }

        abort_unless(str_starts_with($collection, 'users/'.$request->user()->id.'/'), 403);
    }

    private function isCardsCollection(Request $request, string $collection): bool
    {
        return $collection === 'cards' || $collection === 'users/'.$request->user()->id.'/cards';
    }

    private function isTransactionsCollection(Request $request, string $collection): bool
    {
        return $collection === 'transactions' || $collection === 'users/'.$request->user()->id.'/transactions';
    }

    private function applyCardFilter($query, string $field, mixed $equals): void
    {
        match ($field) {
            'cardUid' => $query->where('card_uid', $equals),
            'owner', 'cardName' => $query->where('owner', $equals),
            'isFrozen' => $query->where('is_frozen', filter_var($equals, FILTER_VALIDATE_BOOLEAN)),
            default => null,
        };
    }

    private function applyCardOrder($query, ?string $field, bool $descending): void
    {
        $column = match ($field) {
            'cardUid' => 'card_uid',
            'owner', 'cardName' => 'owner',
            'balance' => 'balance',
            'lastUsed' => 'last_used_at',
            default => 'updated_at',
        };

        $query->orderBy($column, $descending ? 'desc' : 'asc');
    }

    private function applyOrderFilter($query, string $field, mixed $equals): void
    {
        match ($field) {
            'orderNumber' => $query->where('order_number', $equals),
            'orderStatus' => $query->where('status', $equals),
            'paymentStatus' => $query->where('payment_status', $equals),
            default => null,
        };
    }

    private function applyOrderSort($query, ?string $field, bool $descending): void
    {
        $column = match ($field) {
            'orderNumber' => 'order_number',
            'orderStatus' => 'status',
            'amount' => 'total_amount',
            default => 'created_at',
        };

        $query->orderBy($column, $descending ? 'desc' : 'asc');
    }

    private function deductPurchaseBalance(User $user, array $data, float $amount): void
    {
        $paidWith = $data['paidWith'] ?? $data['paymentSource'] ?? null;
        if (in_array($paidWith, ['gateway', 'online'], true)) {
            return;
        }
        if ($paidWith === 'wallet' || $paidWith === 'primary') {
            abort_if((float) $user->wallet_balance < $amount, 422, 'Insufficient wallet balance.');
            $user->decrement('wallet_balance', $amount);

            return;
        }

        abort_unless(in_array($paidWith, ['card', 'nfc_card'], true), 422, 'Unsupported payment method.');

        $cardUid = $data['cardId'] ?? $data['cardUid'] ?? null;
        abort_if(! $cardUid, 422, 'Card identifier is required.');

        $card = Card::where('user_id', $user->id)->where('card_uid', $cardUid)->lockForUpdate()->firstOrFail();
        abort_if($card->is_frozen, 422, 'Card is frozen.');
        abort_if((float) $card->balance < $amount, 422, 'Insufficient card balance.');

        $card->decrement('balance', $amount);
        $card->update(['last_used_at' => now()]);
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
            $rangeStart = now()->subDays($i + 9)->toDateString();
            Cache::forget("dashboard.sales_by_date.{$rangeStart}.{$today}");
        }
    }

    private function paymentMethod(array $data): string
    {
        return match ($data['paidWith'] ?? $data['paymentSource'] ?? '') {
            'card', 'nfc_card' => 'NFC Card',
            'gateway', 'online' => 'Online Banking',
            default => 'E-Wallet',
        };
    }

    private function uniqueOrderNumber(string $path, ?string $requested): string
    {
        $orderNumber = $requested ?: 'APP-'.now()->format('YmdHis').'-'.substr(md5($path), 0, 6);

        if (Order::where('order_number', $orderNumber)->exists()) {
            return $orderNumber.'-'.substr(md5($path), 0, 6);
        }

        return $orderNumber;
    }

    private function syncCustomerProfile(User $user): void
    {
        Customer::where('email', $user->email)->update([
            'parent_name' => $user->username ?: $user->name,
            'student_name' => $user->username ?: $user->name,
            'phone' => $user->phone_number ?: '-',
        ]);
    }
}
