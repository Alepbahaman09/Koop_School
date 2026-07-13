<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->where('stock_quantity', '>', 0)->get();

        return response()->json(['success' => true, 'data' => $products]);
    }

    public function show($id)
    {
        $product = Product::with('category')->find($id);

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $product]);
    }

    public function categories()
    {
        $categories = Category::where('is_active', true)->withCount('products')->get();

        return response()->json(['success' => true, 'data' => $categories]);
    }
}
