<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\ProductFilterRequest;
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
        $products = $this->productService->getFilteredProducts($filters);
        $allProducts = $this->productService->getAllProductsForCounts();
        $units = $this->productService->getAvailableUnits();
        $selectedCategory = $this->productService->resolveSelectedCategory(
            $categories,
            $filters['category'] ?? null,
            $filters['category_id'] ?? null,
        );

        $user = $request->user();

        return Inertia::render('POS/ProductPos', [
            'products' => $products,
            'categories' => $categories,
            'all_products' => $allProducts,
            'units' => $units,
            'selectedCategory' => $selectedCategory,
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
