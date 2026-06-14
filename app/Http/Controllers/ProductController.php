<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->filled('search')) {
            $search = (string) $request->string('search')->trim();
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }

        if ($request->filled('stock')) {
            match ($request->stock) {
                'low' => $query->where('stock_quantity', '>', 0)
                    ->whereColumn('stock_quantity', '<=', 'min_stock_level'),
                'out' => $query->where('stock_quantity', 0),
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                default => null,
            };
        }

        $products = $query->latest()->paginate(20)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $stats = [
            'total' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'low' => Product::where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'min_stock_level')->count(),
            'out' => Product::where('stock_quantity', 0)->count(),
            'inventory_value' => Product::selectRaw('COALESCE(SUM(price * stock_quantity), 0) as value')->value('value'),
        ];

        return view('products', compact('products', 'categories', 'stats'));
    }

    public function create()
    {
        return redirect()->route('products.index', ['create' => 1]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'sku' => 'required|unique:products,sku',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_level' => 'required|integer|min:0',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($validated) {
            $product = Product::create($validated);

            if ($product->stock_quantity > 0) {
                InventoryTransaction::create([
                    'product_id' => $product->id,
                    'admin_id' => auth()->id(),
                    'type' => 'In',
                    'quantity' => $product->stock_quantity,
                    'stock_before' => 0,
                    'stock_after' => $product->stock_quantity,
                    'notes' => 'Opening stock',
                ]);
            }
        });

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        return redirect()->route('products.index', ['edit' => $product->id]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'sku' => 'required|unique:products,sku,'.$product->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_level' => 'required|integer|min:0',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $oldImage = $product->image;
        $oldStock = $product->stock_quantity;

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        DB::transaction(function () use ($product, $validated, $oldStock) {
            $product->update($validated);

            if ($oldStock !== $product->stock_quantity) {
                InventoryTransaction::create([
                    'product_id' => $product->id,
                    'admin_id' => auth()->id(),
                    'type' => 'Adjustment',
                    'quantity' => abs($product->stock_quantity - $oldStock),
                    'stock_before' => $oldStock,
                    'stock_after' => $product->stock_quantity,
                    'notes' => 'Stock adjusted from product page',
                ]);
            }
        });

        if ($request->hasFile('image') && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        if ($product->orderItems()->exists()) {
            $product->update(['is_active' => false]);

            return back()->with('success', 'Product has order history, so it was deactivated instead of deleted.');
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }
}
