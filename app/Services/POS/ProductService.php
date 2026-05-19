<?php

namespace App\Services\POS;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductService
{
    /**
     * Get filtered & paginated products based on validated filter input.
     */
    public function getFilteredProducts(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->buildFilteredQuery($filters)
            ->with(['category', 'brand', 'batches'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Aggregated stats (total + stok habis) untuk filter yang sama dengan getFilteredProducts,
     * tanpa pagination. Dipakai untuk summary card di Manajemen Produk / POS.
     */
    public function getStats(array $filters): array
    {
        $base = $this->buildFilteredQuery($filters);

        $total = (clone $base)->count();

        $outOfStock = (clone $base)
            ->whereRaw('(select coalesce(sum(stock_quantity), 0) from batches where batches.product_id = products.id) = 0')
            ->count();

        return [
            'total' => $total,
            'out_of_stock' => $outOfStock,
        ];
    }

    private function buildFilteredQuery(array $filters): Builder
    {
        $query = Product::query()
            ->where('is_active', 1)
            ->orderBy('name');

        $this->applyCategoryFilter($query, $filters);
        $this->applyBrandFilter($query, $filters);
        $this->applySearchFilter($query, $filters);
        $this->applyStockStatusFilter($query, $filters);
        $this->applyUnitFilter($query, $filters);

        return $query;
    }

    /**
     * Get all categories ordered by name.
     */
    public function getCategories(): Collection
    {
        return Category::orderBy('name', 'asc')->get();
    }

    /**
     * Get all active products for summary/count cards.
     */
    public function getAllProductsForCounts(): Collection
    {
        return Product::with(['category', 'brand', 'batches'])
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();
    }

    /**
     * Resolve the selected category from slug or id.
     */
    public function resolveSelectedCategory(Collection $categories, ?string $slug, ?int $id): mixed
    {
        if ($slug) {
            return $categories->firstWhere('slug', $slug);
        }

        if ($id) {
            return $categories->firstWhere('id', $id);
        }

        return null;
    }

    /**
     * Get all unique unit strings (ml + mAh) from active products.
     * Volume formatting is delegated to the Product model accessor.
     */
    public function getAvailableUnits(): array
    {
        $sizeUnits = Product::where('is_active', 1)
            ->whereNotNull('size_ml')
            ->where('size_ml', '>', 0)
            ->distinct()
            ->pluck('size_ml')
            ->map(fn ($v) => ($v == (int) $v ? (int) $v : (float) $v).'ml');

        $batteryUnits = Product::where('is_active', 1)
            ->whereNotNull('battery_mah')
            ->where('battery_mah', '>', 0)
            ->distinct()
            ->pluck('battery_mah')
            ->map(fn ($v) => (int) $v.'mAh');

        return $sizeUnits
            ->merge($batteryUnits)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Parse a unit string (e.g. "60ml", "3000mAh") into its numeric value and type.
     *
     * @return array{value: float, type: string}
     */
    public function parseUnit(string $unit): array
    {
        $numericValue = (float) $unit;
        $unitType = str_replace((string) $numericValue, '', $unit);

        // Handle case where numeric loses the decimal (e.g. "60" from "60.0ml")
        if ($unitType === '') {
            $unitType = str_replace(strval((int) $numericValue), '', $unit);
        }

        return ['value' => $numericValue, 'type' => trim($unitType)];
    }

    // -------------------------------------------------------------------------
    // Private filter appliers
    // -------------------------------------------------------------------------

    private function applyCategoryFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['category'])) {
            $slug = $filters['category'];
            $query->whereHas('category', fn ($q) => $q->where('slug', $slug));
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
    }

    private function applyBrandFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['brand'])) {
            $slug = $filters['brand'];
            $query->whereHas('brand', fn ($q) => $q->where('slug', $slug));
        }
    }

    private function applySearchFilter(Builder $query, array $filters): void
    {
        if (empty($filters['search'])) {
            return;
        }

        $driver  = $query->getModel()->getConnection()->getDriverName();
        $likeOp  = $driver === 'pgsql' ? 'ilike' : 'like';
        $tokens  = array_filter(preg_split('/\s+/', trim((string) $filters['search'])));

        $query->where(function ($outer) use ($tokens, $likeOp) {
            foreach ($tokens as $token) {
                $like = '%'.$token.'%';
                $outer->where(function ($q) use ($like, $likeOp) {
                    $q->where('products.name', $likeOp, $like)
                        ->orWhere('products.code', $likeOp, $like)
                        ->orWhere('products.flavor', $likeOp, $like)
                        ->orWhereHas('brand',    fn ($b) => $b->where('name', $likeOp, $like))
                        ->orWhereHas('category', fn ($c) => $c->where('name', $likeOp, $like));
                });
            }
        });
    }

    private function applyStockStatusFilter(Builder $query, array $filters): void
    {
        $stockStatus = $filters['stock_status'] ?? null;
        $totalStockSql = '(select coalesce(sum(stock_quantity), 0) from batches where batches.product_id = products.id)';

        match ($stockStatus) {
            'tersedia' => $query->whereRaw("{$totalStockSql} > 0"),
            'habis'    => $query->whereRaw("{$totalStockSql} = 0"),
            default    => null,
        };
    }

    private function applyUnitFilter(Builder $query, array $filters): void
    {
        if (empty($filters['unit'])) {
            return;
        }

        ['value' => $numericValue, 'type' => $unitType] = $this->parseUnit($filters['unit']);

        $query->where(function ($q) use ($numericValue, $unitType) {
            match ($unitType) {
                'ml' => $q->where('size_ml', $numericValue),
                'mAh' => $q->where('battery_mah', $numericValue),
                default => null,
            };
        });
    }
}
