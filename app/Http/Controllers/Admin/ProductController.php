<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProductRequest;
use App\Models\Batch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ImageOptimizer;
use App\Services\POS\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ImageOptimizer $imageOptimizer,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'category', 'brand', 'stock_status', 'sort', 'dir']);
        // Admin list harus menampilkan produk nonaktif juga (dengan badge status).
        $filters['include_inactive'] = true;

        $categories = $this->productService->getCategories();
        $brands = Brand::orderBy('name')->get(['id', 'name', 'slug']);
        $products = $this->productService->getFilteredProducts($filters, 15);
        $stats = $this->productService->getStats($filters);

        $selectedCategory = $this->productService->resolveSelectedCategory(
            $categories,
            $filters['category'] ?? null,
            null,
        );

        $selectedBrand = ! empty($filters['brand'])
            ? $brands->firstWhere('slug', $filters['brand'])
            : null;

        return Inertia::render('admin/ManajemenProduct', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'stats' => $stats,
            'searchQuery' => $filters['search'] ?? null,
            'selectedCategory' => $selectedCategory,
            'selectedBrand' => $selectedBrand,
            'selectedStockStatus' => $filters['stock_status'] ?? null,
            'sortKey' => $filters['sort'] ?? null,
            'sortDir' => $filters['dir'] ?? 'asc',
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/product/Create', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $this->imageOptimizer->optimize($request->file('image'), 'products');
        }

        $product = Product::create($data);

        if ($request->filled('batch_stock_quantity') && $request->input('batch_stock_quantity') > 0) {
            $batch = new Batch;
            $batch->id = (string) Str::uuid();
            $batch->product_id = $product->getKey();
            $batch->lot_number = $request->input('batch_lot_number') ?: 'LOT-'.strtoupper(Str::random(6));
            $batch->stock_quantity = (int) $request->input('batch_stock_quantity');
            $batch->cost_price = (float) $request->input('batch_cost_price', 0);
            $batch->cukai_year = $request->input('batch_cukai_year') ?: null;
            $batch->is_promo = $request->boolean('batch_is_promo');
            $batch->promo_price = $batch->is_promo ? (float) $request->input('batch_promo_price', 0) : null;
            $batch->save();
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('admin/product/Edit', [
            'product' => $product->load(['category', 'brand', 'batches']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $this->imageOptimizer->optimize($request->file('image'), 'products');
        } else {
            // Jangan timpa kolom image dengan null saat user tidak upload gambar baru
            unset($data['image']);
        }

        $product->update($data);

        return redirect()->route('admin.products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
