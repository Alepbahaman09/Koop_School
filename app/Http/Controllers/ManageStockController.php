<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManageStockController extends Controller
{
    /**
     * Show manual stock adjustment form and history.
     */
    public function index(Request $request)
    {
        $products = Product::orderBy('name')->get();

        $history = InventoryTransaction::with(['product', 'admin'])
            ->whereIn('reference_type', ['ManualAdjustment'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('manage_stock.index', compact('products', 'history'));
    }

    /**
     * Apply a manual stock adjustment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type'       => 'required|in:add,deduct',
            'quantity'   => 'required|integer|min:1',
            'reason'     => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated) {
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);
            $stockBefore = $product->stock_quantity;

            if ($validated['type'] === 'add') {
                $product->increment('stock_quantity', $validated['quantity']);
                $stockAfter = $product->fresh()->stock_quantity;
                $txType = 'In';
            } else {
                if ($product->stock_quantity < $validated['quantity']) {
                    abort(422, 'Insufficient stock to deduct.');
                }
                $product->decrement('stock_quantity', $validated['quantity']);
                $stockAfter = $product->fresh()->stock_quantity;
                $txType = 'Out';
            }

            InventoryTransaction::create([
                'product_id'     => $product->id,
                'admin_id'       => auth()->id(),
                'type'           => $txType,
                'quantity'       => $validated['quantity'],
                'stock_before'   => $stockBefore,
                'stock_after'    => $stockAfter,
                'reference_type' => 'ManualAdjustment',
                'reference_id'   => null,
                'notes'          => $validated['reason'],
            ]);
        });

        return redirect()->route('manage-stock.index')
            ->with('success', 'Stock adjusted successfully.');
    }
}
