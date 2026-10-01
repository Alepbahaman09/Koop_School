<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductActivityLog;
use App\Services\SupabaseStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    private const ICON_BUCKET = 'category-icons';

    public function __construct(private readonly SupabaseStorage $storage) {}

    public function index()
    {
        return to_route('products.index', ['categories' => 1]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')],
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
        $category = Category::create($validated);

        ProductActivityLog::log(
            'created',
            'category',
            $category->id,
            $category->name,
            'Created new category: ' . $category->name
        );

        $this->clearCategoryCaches();

        return to_route('products.index', ['categories' => 1])->with('category_success', 'Category created successfully.');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category)],
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

        if ($request->hasFile('icon') && $oldIcon) {
            $this->storage->deletePublicFile($oldIcon, self::ICON_BUCKET);
        }

        ProductActivityLog::log(
            'updated',
            'category',
            $category->id,
            $category->name,
            'Updated category: ' . $category->name . ' (Visible: ' . ($category->is_active ? 'Yes' : 'No') . ')'
        );

        $this->clearCategoryCaches();

        return to_route('products.index', ['categories' => 1])->with('category_success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        DB::transaction(function () use ($category) {
            // Instantly move all items in this category to uncategorised
            $affectedCount = Product::where('category_id', $category->id)->count();
            Product::where('category_id', $category->id)->update(['category_id' => null]);

            if ($category->icon_url) {
                $this->storage->deletePublicFile($category->icon_url, self::ICON_BUCKET);
            }

            $categoryName = $category->name;
            $categoryId = $category->id;
            $category->delete();

            ProductActivityLog::log(
                'deleted',
                'category',
                $categoryId,
                $categoryName,
                "Deleted category '{$categoryName}'. {$affectedCount} product(s) moved to Uncategorised."
            );
        });

        $this->clearCategoryCaches();

        return to_route('products.index', ['categories' => 1]);
    }

    private function clearCategoryCaches(): void
    {
        Cache::forget('products.stats_summary_v2');
        Cache::forget('products.active_categories_v2');
        Cache::forget('products.all_categories_v2');
        Cache::forget('products.uncategorized_count_v2');
        Cache::forget('products.report_modal_html_v2');
        Cache::forget('suppliers.stats_summary_v2');
        Cache::forget('suppliers.drawer_categories_v1');
    }
            ->with('category_success', 'Category deleted instantly. Associated items have been moved to Uncategorised.');
    }
}
