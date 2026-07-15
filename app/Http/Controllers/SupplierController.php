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
            'name'         => 'required|filled|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email'        => 'required|email|unique:suppliers,email',
            'phone'        => 'required|filled|string|max:50',
            'address'      => 'nullable|string|max:1000',
            'tax_number'   => 'nullable|string|max:100',
            'is_active'    => 'nullable|boolean',
        ]);

        // Guard: ConvertEmptyStringsToNull middleware can nullify fields after
        // validation; this ensures name never reaches the DB as null.
        if (empty($validated['name'] ?? '')) {
            return redirect()->back()->withInput()
                ->withErrors(['name' => 'The contact name field is required.']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        Supplier::create($validated);

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name'         => 'required|filled|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email'        => 'required|email|unique:suppliers,email,'.$supplier->id,
            'phone'        => 'required|filled|string|max:50',
            'address'      => 'nullable|string|max:1000',
            'tax_number'   => 'nullable|string|max:100',
            'is_active'    => 'nullable|boolean',
        ]);

        if (empty($validated['name'] ?? '')) {
            return redirect()->back()->withInput()
                ->withErrors(['name' => 'The contact name field is required.']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        $supplier->update($validated);

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
