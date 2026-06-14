<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\MobileDocument;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileDocumentController extends Controller
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
        if ($data['collection'] === 'items') {
            $products = Product::with('category')->where('is_active', true);
            if (! empty($data['field']) && array_key_exists('equals', $data) && in_array($data['field'], ['name', 'sku'], true)) {
                $products->where($data['field'], $data['equals']);
            }

            return response()->json(['documents' => $products->get()->map(fn ($product) => $this->productDocument($product))]);
        }
        if ($data['collection'] === 'users') {
            abort(403, 'User directory queries are not allowed.');
        }
        $this->authorizeCollectionRead($request, $data['collection'], $request->boolean('collection_group'));
        $query = MobileDocument::query();
        $request->boolean('collection_group')
            ? $query->where('collection_path', "users/{$request->user()->id}/{$data['collection']}")
            : $query->where('collection_path', trim($data['collection'], '/'));
        if (! empty($data['field']) && array_key_exists('equals', $data)) {
            $query->where("data->{$data['field']}", $data['equals']);
        }
        if (! empty($data['order_by'])) {
            $query->orderBy("data->{$data['order_by']}", $request->boolean('descending') ? 'desc' : 'asc');
        }

        return response()->json(['documents' => $query->limit($data['limit'] ?? 1000)->get()
            ->map(fn ($document) => $this->serialize($document))]);
    }

    public function show(Request $request, string $path)
    {
        $this->authorizeReadPath($request, $path);
        if (str_starts_with($path, 'users/'.$request->user()->id) && substr_count($path, '/') === 1) {
            return response()->json($this->userDocument($request));
        }
        if (preg_match('#^items/(\d+)$#', $path, $matches)) {
            $product = Product::with('category')->find($matches[1]);

            return $product ? response()->json($this->productDocument($product)) : response()->json(['message' => 'Not found'], 404);
        }
        $document = MobileDocument::where('path', trim($path, '/'))->first();

        return $document ? response()->json($this->serialize($document)) : response()->json(['message' => 'Not found'], 404);
    }

    public function cardExists(string $cardUid)
    {
        return response()->json(['exists' => Card::where('card_uid', $cardUid)->exists()]);
    }

    public function upsert(Request $request, string $path)
    {
        $payload = $request->validate(['data' => 'required|array', 'merge' => 'nullable|boolean']);
        $this->authorizeUserPath($request, $path);
        if (str_starts_with($path, 'users/'.$request->user()->id) && substr_count($path, '/') === 1) {
            $document = DB::transaction(function () use ($request, $payload) {
                $user = User::lockForUpdate()->findOrFail($request->user()->id);
                $current = array_merge($user->mobile_profile ?? [], ['balance' => (float) $user->wallet_balance]);
                $profile = $request->boolean('merge')
                    ? array_replace_recursive($current, $this->resolve($payload['data'], $current))
                    : $this->resolve($payload['data'], []);
                abort_if((float) ($profile['balance'] ?? 0) < 0, 422, 'Insufficient wallet balance.');
                $user->update([
                    'wallet_balance' => $profile['balance'] ?? $user->wallet_balance,
                    'username' => $profile['username'] ?? $user->username,
                    'phone_number' => $profile['phoneNumber'] ?? $user->phone_number,
                    'mobile_profile' => $profile,
                ]);
                $this->syncCustomerProfile($user);

                return $this->userDocumentFor($user);
            });

            return response()->json($document);
        }
        if (preg_match('#^items/(\d+)$#', $path, $matches)) {
            $product = DB::transaction(function () use ($matches, $payload) {
                $product = Product::lockForUpdate()->findOrFail($matches[1]);
                $product->update($this->productUpdates($product, $payload['data']));

                return $product;
            });

            return response()->json($this->productDocument($product->load('category')));
        }
        $document = DB::transaction(fn () => $this->write($path, $payload['data'], $request->boolean('merge')));

        return response()->json($this->serialize($document));
    }

    public function destroy(Request $request, string $path)
    {
        $this->authorizeUserPath($request, $path);
        DB::transaction(fn () => $this->deleteDocument($path));

        return response()->json(['success' => true]);
    }

    public function batch(Request $request)
    {
        $operations = $request->validate([
            'operations' => 'required|array|min:1|max:500',
            'operations.*.type' => 'required|in:set,update,delete',
            'operations.*.path' => 'required|string',
            'operations.*.data' => 'nullable|array',
        ])['operations'];
        DB::transaction(function () use ($operations, $request) {
            foreach ($operations as $operation) {
                $this->authorizeUserPath($request, $operation['path']);
                $operation['type'] === 'delete'
                    ? $this->deleteDocument($operation['path'])
                    : $this->write($operation['path'], $operation['data'] ?? [], $operation['type'] === 'update' || ($operation['merge'] ?? false));
            }
        });

        return response()->json(['success' => true]);
    }

    private function write(string $path, array $incoming, bool $merge): MobileDocument
    {
        $path = trim($path, '/');
        $segments = explode('/', $path);
        abort_if(count($segments) < 2 || count($segments) % 2 !== 0, 422, 'Invalid document path.');
        if (preg_match('#^users/(\d+)/cards/([^/]+)$#', $path, $cardMatches)) {
            $incoming['cardUid'] ??= $cardMatches[2];
        }
        $document = MobileDocument::where('path', $path)->lockForUpdate()->first();
        $current = $document?->data ?? [];
        if (preg_match('#^users/(\d+)$#', $path, $matches)) {
            $user = User::lockForUpdate()->find($matches[1]);
            if ($user) {
                $resolved = $this->resolve($incoming, array_merge($user->mobile_profile ?? [], ['balance' => (float) $user->wallet_balance]));
                abort_if((float) ($resolved['balance'] ?? $user->wallet_balance) < 0, 422, 'Insufficient wallet balance.');
                $user->update([
                    'wallet_balance' => $resolved['balance'] ?? $user->wallet_balance,
                    'username' => $resolved['username'] ?? $user->username,
                    'phone_number' => $resolved['phoneNumber'] ?? $user->phone_number,
                    'mobile_profile' => array_replace_recursive($user->mobile_profile ?? [], $resolved),
                ]);
                $this->syncCustomerProfile($user);
            }
        }
        if (preg_match('#^items/(\d+)$#', $path, $matches)) {
            $product = Product::lockForUpdate()->find($matches[1]);
            if ($product) {
                $product->update($this->productUpdates($product, $incoming));
            }
        }
        $resolvedData = $this->resolve($merge ? array_replace_recursive($current, $incoming) : $incoming, $current);
        if (preg_match('#^users/\d+/cards/[^/]+$#', $path)) {
            abort_if((float) ($resolvedData['balance'] ?? 0) < 0, 422, 'Insufficient card balance.');
        }
        $document = MobileDocument::updateOrCreate(['path' => $path], [
            'collection_path' => implode('/', array_slice($segments, 0, -1)),
            'document_id' => end($segments),
            'data' => $resolvedData,
        ]);
        $this->syncCardToDashboard($path, $document->data);
        $this->syncPurchaseToDashboard($path, $document->data);

        return $document;
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

    private function userDocument(Request $request): array
    {
        $user = $request->user();

        return ['id' => (string) $user->id, 'path' => "users/{$user->id}", 'data' => array_merge($user->mobile_profile ?? [], [
            'email' => $user->email, 'username' => $user->username, 'phoneNumber' => $user->phone_number, 'balance' => (float) $user->wallet_balance,
        ])];
    }

    private function userDocumentFor(User $user): array
    {
        return ['id' => (string) $user->id, 'path' => "users/{$user->id}", 'data' => array_merge($user->mobile_profile ?? [], [
            'email' => $user->email, 'username' => $user->username, 'phoneNumber' => $user->phone_number, 'balance' => (float) $user->wallet_balance,
        ])];
    }

    private function syncCardToDashboard(string $path, array $data): void
    {
        if (! preg_match('#^users/(\d+)/cards/([^/]+)$#', $path, $matches)) {
            return;
        }
        $card = Card::where('card_uid', $matches[2])->lockForUpdate()->first();
        abort_if($card && (string) $card->user_id !== $matches[1], 422, 'This NFC card is already registered.');
        Card::updateOrCreate(['card_uid' => $matches[2]], [
            'user_id' => $matches[1],
            'owner' => $data['owner'] ?? $data['cardName'] ?? 'Card',
            'balance' => $data['balance'] ?? 0,
            'is_frozen' => $data['isFrozen'] ?? false,
            'last_used_at' => $data['lastUsed'] ?? null,
        ]);
    }

    private function deleteDocument(string $path): void
    {
        $path = trim($path, '/');
        if (preg_match('#^users/(\d+)/cards/([^/]+)$#', $path, $matches)) {
            Card::where('user_id', $matches[1])->where('card_uid', $matches[2])->delete();
        }
        MobileDocument::where('path', $path)->delete();
    }

    private function syncPurchaseToDashboard(string $path, array $data): void
    {
        if (($data['type'] ?? null) !== 'Purchase' || ! preg_match('#^users/(\d+)/transactions/[^/]+$#', $path, $matches)) {
            return;
        }
        $user = User::find($matches[1]);
        $customer = $user ? Customer::where('email', $user->email)->first() : null;
        if (! $user || ! $customer) {
            return;
        }
        if (Order::where('source_document_path', $path)->exists()) {
            return;
        }
        $orderNumber = $data['orderNumber'] ?? 'APP-'.now()->format('YmdHis').'-'.substr(md5($path), 0, 6);
        if (Order::where('order_number', $orderNumber)->exists()) {
            $orderNumber .= '-'.substr(md5($path), 0, 6);
        }
        $requestedItems = collect($data['items'] ?? []);
        $items = $requestedItems->map(function ($item) {
            $product = isset($item['id'])
                ? Product::lockForUpdate()->find($item['id'])
                : Product::where('name', $item['name'] ?? '')->lockForUpdate()->first();

            return $product ? ['product' => $product, 'quantity' => (int) ($item['quantity'] ?? 1)] : null;
        })->filter();
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
        $transactionDocument = MobileDocument::where('path', $path)->lockForUpdate()->firstOrFail();
        $transactionData = $transactionDocument->data;
        $transactionData['amount'] = $totalAmount;
        $transactionData['orderNumber'] = $orderNumber;
        $transactionData['orderStatus'] = 'Pending';
        $transactionDocument->update(['data' => $transactionData]);
        $order = Order::create([
            'order_number' => $orderNumber, 'source_document_path' => $path,
            'customer_id' => $customer->id, 'user_id' => $user->id, 'status' => 'Pending',
            'subtotal' => $subtotal, 'tax' => 0, 'discount' => 0, 'total_amount' => $totalAmount,
            'payment_status' => 'Paid', 'notes' => $data['note'] ?? null,
        ]);
        foreach ($items as $item) {
            $stockBefore = $item['product']->stock_quantity;
            $stockAfter = $stockBefore - $item['quantity'];
            OrderItem::create([
                'order_id' => $order->id, 'product_id' => $item['product']->id, 'quantity' => $item['quantity'],
                'unit_price' => $item['product']->price, 'subtotal' => (float) $item['product']->price * $item['quantity'],
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
                'notes' => 'Stock reduced by Flutter purchase',
            ]);
        }
        $paymentMethod = match ($data['paidWith'] ?? '') {
            'card', 'nfc_card' => 'Card',
            'gateway', 'online' => 'Online Banking',
            default => 'E-Wallet',
        };
        Payment::create([
            'order_id' => $order->id, 'payment_reference' => 'APP-PAY-'.$order->id.'-'.now()->format('YmdHis'),
            'payment_method' => $paymentMethod,
            'amount' => $totalAmount, 'status' => 'Completed', 'paid_at' => now(), 'notes' => $data['note'] ?? null,
        ]);
    }

    private function productDocument(Product $product): array
    {
        return ['id' => (string) $product->id, 'path' => "items/{$product->id}", 'data' => [
            'id' => $product->id, 'name' => $product->name, 'description' => $product->description,
            'price' => (float) $product->price, 'stock' => $product->stock_quantity, 'image_url' => $product->image_url,
            'category' => $product->category?->name,
        ]];
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

    private function serialize(MobileDocument $document): array
    {
        return ['id' => $document->document_id, 'path' => $document->path, 'data' => $document->data];
    }

    private function authorizeUserPath(Request $request, string $path): void
    {
        $path = trim($path, '/');
        if (preg_match('#^users/(\d+)(?:/|$)#', $path, $matches)) {
            abort_unless((string) $request->user()->id === $matches[1], 403);

            return;
        }
        abort_unless(preg_match('#^(email_notifications|notifications)/[^/]+$#', $path), 403);
    }

    private function authorizeReadPath(Request $request, string $path): void
    {
        $path = trim($path, '/');
        if (preg_match('#^items/\d+$#', $path)) {
            return;
        }
        abort_unless(str_starts_with($path, 'users/'.$request->user()->id.'/') || $path === 'users/'.$request->user()->id, 403);
    }

    private function authorizeCollectionRead(Request $request, string $collection, bool $collectionGroup): void
    {
        $collection = trim($collection, '/');
        if ($collectionGroup) {
            abort_unless($collection === 'cards', 403);

            return;
        }
        abort_unless(str_starts_with($collection, 'users/'.$request->user()->id.'/'), 403);
    }

    private function deductPurchaseBalance(User $user, array $data, float $amount): void
    {
        $paidWith = $data['paidWith'] ?? $data['paymentSource'] ?? null;
        if (in_array($paidWith, ['gateway', 'online'], true)) {
            return;
        }
        if ($paidWith === 'wallet' || $paidWith === 'primary') {
            $lockedUser = User::lockForUpdate()->findOrFail($user->id);
            abort_if((float) $lockedUser->wallet_balance < $amount, 422, 'Insufficient wallet balance.');
            $lockedUser->decrement('wallet_balance', $amount);

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

        $cardDocument = MobileDocument::where('path', "users/{$user->id}/cards/{$cardUid}")->lockForUpdate()->first();
        if ($cardDocument) {
            $cardData = $cardDocument->data;
            $cardData['balance'] = (float) $card->fresh()->balance;
            $cardData['lastUsed'] = now()->toIso8601String();
            $cardDocument->update(['data' => $cardData]);
        }
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
