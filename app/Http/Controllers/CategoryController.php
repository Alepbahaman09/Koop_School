<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\SupabaseStorage;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    private const ICON_BUCKET = 'category-icons';

    public function __construct(private readonly SupabaseStorage $storage) {}

    public function index()
    {
        return redirect()->route('products.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'icon' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('icon')) {
            $validated['icon_url'] = $this->storage->uploadImage(
                $request->file('icon'),
                self::ICON_BUCKET,
                'categories',
            );
        }

        unset($validated['icon']);
        $validated['is_active'] = $request->boolean('is_active');
        Category::create($validated);

        return back()->with('success', 'Category created successfully');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,'.$category->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        $oldIcon = $category->icon_url;
        if ($request->hasFile('icon')) {
            $validated['icon_url'] = $this->storage->uploadImage(
                $request->file('icon'),
                self::ICON_BUCKET,
                'categories',
            );
        }

        unset($validated['icon']);
        $validated['is_active'] = $request->boolean('is_active');
        $category->update($validated);

        if ($request->hasFile('icon')) {
            $this->storage->deletePublicFile($oldIcon, self::ICON_BUCKET);
        }

        return back()->with('success', 'Category updated successfully');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete category with products');
        }

        $this->storage->deletePublicFile($category->icon_url, self::ICON_BUCKET);
        $category->delete();

        return back()->with('success', 'Category deleted successfully');
    }
}
