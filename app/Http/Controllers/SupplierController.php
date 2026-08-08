<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\StockPurchase;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'suppliers');

        if ($tab === 'purchases') {
            $query = StockPurchase::with(['supplier', 'creator']);

            if ($search = $request->input('search')) {
                $query->whereHas('supplier', function ($q) use ($search) {
                    $q->where('company_name', 'like', "%{$search}%");
                });
            }

            $stockPurchases = $query->latest()->paginate(15)->withQueryString();
            $spData = self::spDrawerData();

            return view('suppliers', array_merge(compact('stockPurchases', 'tab'), $spData));
        }

        $query = Supplier::query();

        if ($search = $request->input('search')) {
            $query->where('company_name', 'like', "%{$search}%");
        }

        $suppliers = $query->latest()->paginate(15)->withQueryString();
        $spData = self::spDrawerData();

        return view('suppliers', array_merge(compact('suppliers', 'tab'), $spData));
    }

    /** Data needed by the New Stock Purchase slide-over drawer. */
    private static function spDrawerData(): array
    {
        return [
            'spProducts'      => Product::orderBy('name')->get(),
            'spCategories'    => Category::orderBy('name')->get(),
            'spPurchaseUnits' => StockPurchaseController::PURCHASE_UNITS,
            'spSuppliers'     => Supplier::orderBy('company_name')->get(),
        ];
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
        ]);

        Supplier::create($validated);

        return redirect()->route('suppliers.index', ['tab' => 'suppliers'])
            ->with('success', 'Supplier created successfully.');
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.index', ['tab' => 'suppliers'])
            ->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier)
    {
        // Safe check for relationships to prevent referential integrity errors
        if ($supplier->purchaseOrders()->exists() || $supplier->stockPurchases()->exists()) {
            return redirect()->route('suppliers.index', ['tab' => 'suppliers'])
                ->with('error', 'Cannot delete this supplier because they have associated stock purchases or purchase orders.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index', ['tab' => 'suppliers'])
            ->with('success', 'Supplier deleted successfully.');
    }
}
