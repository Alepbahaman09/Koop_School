<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return redirect()->route('products.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')],
            'description' => ['nullable', 'string'],
        ]);
        $validated['is_active'] = $request->boolean('is_active') ? 1 : 0;
        Category::create($validated);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string'],
        ]);
        $validated['is_active'] = $request->boolean('is_active') ? 1 : 0;
        $category->update($validated);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            $category->update(['is_active' => false]);

            return back()->with('success', 'Category has products and was deactivated.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
