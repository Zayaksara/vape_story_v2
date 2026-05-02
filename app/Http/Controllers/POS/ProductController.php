<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categorySlug = $request->query('category');
        $search = $request->query('search');

        $query = Product::with(['category', 'brand', 'batches'])
            ->active()
            ->orderBy('name');

        if ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $products = $query->get();
        $categories = Category::orderBy('name')->get();
        $selectedCategory = $categorySlug ? Category::where('slug', $categorySlug)->first() : null;

        return Inertia::render('POS/ProductPos', [
            'products' => $products,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'searchQuery' => $search,
        ]);
    }
}
