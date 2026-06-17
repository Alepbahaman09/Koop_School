<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->filled('search')) {
            $search = (string) $request->string('search');
            $query->where(fn ($q) => $q
                ->where('name', 'ilike', "%{$search}%")
                ->orWhere('sku', 'ilike', "%{$search}%"));
        }
        if ($request->filled('category')) $query->where('category_id', $request->integer('category'));
        if ($request->filled('stock')) {
            $query->where(function ($q) use ($request) {
                match ($request->stock) {
                    'low' => $q->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'min_stock_level'),
                    'out' => $q->where('stock_quantity', 0),
                    'active' => $q->where('is_active', true),
                    'inactive' => $q->where('is_active', false),
                    default => null,
                };
            });
        }

        $all = Product::all();
        $stats = [
            'total' => $all->count(),
            'active' => $all->where('is_active', true)->count(),
            'low' => $all->filter(fn ($p) => $p->stock_quantity > 0 && $p->stock_quantity <= $p->min_stock_level)->count(),
            'out' => $all->where('stock_quantity', 0)->count(),
            'inventory_value' => $all->sum(fn ($p) => $p->price * $p->stock_quantity),
        ];

        $products = $query->latest()->paginate(20)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('products', compact('products', 'categories', 'stats'));
    }

    public function create()
    {
        return redirect()->route('products.index', ['create' => 1]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($request->hasFile('image')) $data['image'] = $request->file('image')->store('products', 'public');
        DB::transaction(function () use ($data) {
            $product = Product::create($data);
            if ((int) $product->stock_quantity > 0) {
                InventoryTransaction::create([
                    'product_id' => $product->id,
                    'admin_id' => Auth::id(),
                    'type' => 'In',
                    'quantity' => $product->stock_quantity,
                    'stock_before' => 0,
                    'stock_after' => $product->stock_quantity,
                    'notes' => 'Opening stock',
                ]);
            }
        });

        return redirect()->route('products.index')->with('success', 'Product created.');
    }

    public function edit(int $product)
    {
        return redirect()->route('products.index', ['edit' => $product]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product->id);
        $oldImage = $product->image;
        if ($request->hasFile('image')) $data['image'] = $request->file('image')->store('products', 'public');
        DB::transaction(function () use ($product, $data) {
            $before = (int) $product->stock_quantity;
            $product->update($data);
            $after = (int) $product->stock_quantity;
            if ($before !== $after) {
                InventoryTransaction::create([
                    'product_id' => $product->id,
                    'admin_id' => Auth::id(),
                    'type' => 'Adjustment',
                    'quantity' => abs($after - $before),
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'notes' => 'Stock adjusted from admin panel',
                ]);
            }
        });
        if ($request->hasFile('image') && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        if (OrderItem::where('product_id', $product->id)->exists()) {
            $product->update(['is_active' => false]);

            return back()->with('success', 'Product has order history and was deactivated.');
        }

        $product->delete();
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        return back()->with('success', 'Product deleted.');
    }

    private function validated(Request $request, ?int $exceptId = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($exceptId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'min_stock_level' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);
        $data['is_active'] = $request->boolean('is_active') ? 1 : 0;

        return $data;
    }
}
