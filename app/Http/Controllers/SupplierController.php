<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $suppliers = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total'    => Supplier::count(),
            'active'   => Supplier::where('is_active', true)->count(),
            'inactive' => Supplier::where('is_active', false)->count(),
        ];

        return view('suppliers', compact('suppliers', 'stats'));
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_name' => 'required|filled|string|max:255',
            'company_name'  => 'nullable|string|max:255',
            'email'         => 'required|email|unique:suppliers,email',
            'phone'         => 'required|filled|string|max:50',
            'address'       => 'nullable|string|max:1000',
            'tax_number'    => 'nullable|string|max:100',
            'is_active'     => 'nullable|boolean',
        ]);

        // Use explicit attribute assignment to guarantee 'name' reaches the DB.
        // Avoid mass-assignment via create($validated) because the 'supplier_name'
        // input key must be remapped to the 'name' DB column.
        $supplier = new Supplier();
        $supplier->name         = $validated['supplier_name'];
        $supplier->company_name = $validated['company_name'] ?? null;
        $supplier->email        = $validated['email'];
        $supplier->phone        = $validated['phone'];
        $supplier->address      = $validated['address'] ?? null;
        $supplier->tax_number   = $validated['tax_number'] ?? null;
        $supplier->is_active    = $request->boolean('is_active', true);
        $supplier->save();

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'supplier_name' => 'required|filled|string|max:255',
            'company_name'  => 'nullable|string|max:255',
            'email'         => 'required|email|unique:suppliers,email,'.$supplier->id,
            'phone'         => 'required|filled|string|max:50',
            'address'       => 'nullable|string|max:1000',
            'tax_number'    => 'nullable|string|max:100',
            'is_active'     => 'nullable|boolean',
        ]);

        $supplier->name         = $validated['supplier_name'];
        $supplier->company_name = $validated['company_name'] ?? null;
        $supplier->email        = $validated['email'];
        $supplier->phone        = $validated['phone'];
        $supplier->address      = $validated['address'] ?? null;
        $supplier->tax_number   = $validated['tax_number'] ?? null;
        $supplier->is_active    = $request->boolean('is_active');
        $supplier->save();

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier removed.');
    }
}
