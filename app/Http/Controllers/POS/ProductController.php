<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\ProductFilterRequest;
use App\Models\Brand;
use App\Services\POS\ProductService;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function index(ProductFilterRequest $request): Response
    {
        $filters = $request->validated();

        $categories = $this->productService->getCategories();
        $brands = Brand::orderBy('name')->get(['id', 'name', 'slug']);
        $products = $this->productService->getFilteredProducts($filters);
        $stats = $this->productService->getStats($filters);
        $allProducts = $this->productService->getAllProductsForCounts();
        $units = $this->productService->getAvailableUnits();
        $selectedCategory = $this->productService->resolveSelectedCategory(
            $categories,
            $filters['category'] ?? null,
            $filters['category_id'] ?? null,
        );
        $selectedBrand = ! empty($filters['brand'])
            ? $brands->firstWhere('slug', $filters['brand'])
            : null;

        $user = $request->user();

        return Inertia::render('POS/ProductPos', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'stats' => $stats,
            'all_products' => $allProducts,
            'units' => $units,
            'selectedCategory' => $selectedCategory,
            'selectedBrand' => $selectedBrand,
            'selectedStockStatus' => $filters['stock_status'] ?? null,
            'selectedUnit' => $filters['unit'] ?? null,
            'searchQuery' => $filters['search'] ?? null,
            'cashier' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
