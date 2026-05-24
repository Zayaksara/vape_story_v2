<?php

namespace App\Services\POS;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * Get filtered & paginated products based on validated filter input.
     */
    public function getFilteredProducts(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->buildFilteredQuery($filters)
            ->with(['category', 'brand', 'batches']);

        $this->applySorting($query, $filters);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Sort seluruh dataset (bukan hanya halaman aktif) sebelum pagination.
     * Kolom turunan (brand/category/stok/cukai) diurutkan via subquery.
     * Hanya dipakai di getFilteredProducts agar tidak mengganggu count() di getStats.
     */
    private function applySorting(Builder $query, array $filters): void
    {
        $sort = $filters['sort'] ?? null;
        if (! $sort) {
            return;
        }

        $dir = strtolower((string) ($filters['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        $stockSql = '(select coalesce(sum(stock_quantity), 0) from batches where batches.product_id = products.id)';
        $brandSql = '(select name from brands where brands.id = products.brand_id)';
        $categorySql = '(select name from categories where categories.id = products.category_id)';
        $cukaiSql = '(select min(cukai_year) from batches where batches.product_id = products.id)';

        $query->reorder();

        match ($sort) {
            'sku'        => $query->orderBy('products.code', $dir),
            'name'       => $query->orderBy('products.name', $dir),
            'flavor'     => $query->orderByRaw("coalesce(products.flavor, '') {$dir}"),
            'nicotine'   => $query->orderByRaw("coalesce(products.nicotine_strength, -1) {$dir}"),
            'size'       => $query->orderByRaw("coalesce(products.size_ml, -1) {$dir}"),
            'base_price' => $query->orderByRaw("coalesce(products.base_price, 0) {$dir}"),
            'brand'      => $query->orderByRaw("{$brandSql} {$dir}"),
            'category'   => $query->orderByRaw("{$categorySql} {$dir}"),
            'stock', 'status' => $query->orderByRaw("{$stockSql} {$dir}"),
            'cukai'      => $query->orderByRaw("{$cukaiSql} {$dir}"),
            default      => $query->orderBy('products.name'),
        };

        // Tie-break stabil agar urutan konsisten antar halaman.
        $query->orderBy('products.name');
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
        $query = Product::query()->orderBy('name');

        // Default: hanya produk aktif (untuk POS). Admin bisa kirim
        // include_inactive=true agar produk nonaktif tetap muncul di list.
        if (empty($filters['include_inactive'])) {
            $query->where('is_active', 1);
        }

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

        return $sizeUnits
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

    /**
     * Semantic-ish search:
     *  - PostgreSQL: pakai pg_trgm `similarity()` + ILIKE substring fallback,
     *    di-OR-kan dengan FTS (websearch_to_tsquery) untuk token matching,
     *    lalu di-ORDER BY skor relevansi (higher = lebih cocok).
     *  - Driver lain: fallback LIKE biasa.
     *
     * Hasil: typo-tolerant ("manggo" ketemu "Mango"), partial match,
     * dan urutan hasil berdasarkan seberapa mirip dengan query.
     */
    private function applySearchFilter(Builder $query, array $filters): void
    {
        if (empty($filters['search'])) {
            return;
        }

        $term   = trim((string) $filters['search']);
        $driver = $query->getModel()->getConnection()->getDriverName();

        if ($driver !== 'pgsql') {
            $this->applyLikeSearch($query, $term);
            return;
        }

        // Threshold similarity 0.18 — cukup permisif untuk salah ketik 1-2 huruf,
        // tapi tetap menyaring kata yang sangat berbeda.
        $threshold = 0.18;
        $tokens = array_filter(preg_split('/\s+/', $term));

        $query->where(function ($outer) use ($term, $tokens, $threshold) {
            // (1) Trigram similarity di field utama produk.
            $outer->whereRaw('similarity(products.name, ?) >= ?',   [$term, $threshold])
                  ->orWhereRaw('similarity(coalesce(products.code, \'\'), ?) >= ?',   [$term, $threshold])
                  ->orWhereRaw('similarity(coalesce(products.flavor, \'\'), ?) >= ?', [$term, $threshold]);

            // (2) Substring ILIKE untuk match parsial (e.g. ketik "vap" → "Vape Story").
            foreach ($tokens as $token) {
                $like = '%'.$token.'%';
                $outer->orWhere('products.name', 'ilike', $like)
                      ->orWhere('products.code', 'ilike', $like)
                      ->orWhere('products.flavor', 'ilike', $like);
            }

            // (3) Match ke nama brand & kategori (subquery EXISTS lebih ringan).
            $outer->orWhereExists(function ($q) use ($term, $threshold) {
                $q->select(DB::raw(1))
                  ->from('brands')
                  ->whereColumn('brands.id', 'products.brand_id')
                  ->whereRaw('similarity(brands.name, ?) >= ?', [$term, $threshold]);
            });
            $outer->orWhereExists(function ($q) use ($term, $threshold) {
                $q->select(DB::raw(1))
                  ->from('categories')
                  ->whereColumn('categories.id', 'products.category_id')
                  ->whereRaw('similarity(categories.name, ?) >= ?', [$term, $threshold]);
            });
        });

        // Ranking: ambil similarity tertinggi dari semua field sebagai relevance score.
        // ORDER BY score DESC, lalu nama alfabetis untuk tie-break.
        $query->reorder()
            ->select('products.*')
            ->selectRaw('
                GREATEST(
                    similarity(products.name, ?),
                    similarity(coalesce(products.code, \'\'), ?),
                    similarity(coalesce(products.flavor, \'\'), ?)
                ) AS _relevance
            ', [$term, $term, $term])
            ->orderByDesc('_relevance')
            ->orderBy('products.name');
    }

    private function applyLikeSearch(Builder $query, string $term): void
    {
        $tokens = array_filter(preg_split('/\s+/', $term));

        $query->where(function ($outer) use ($tokens) {
            foreach ($tokens as $token) {
                $like = '%'.$token.'%';
                $outer->where(function ($q) use ($like) {
                    $q->where('products.name', 'like', $like)
                        ->orWhere('products.code', 'like', $like)
                        ->orWhere('products.flavor', 'like', $like)
                        ->orWhereHas('brand',    fn ($b) => $b->where('name', 'like', $like))
                        ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like));
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
                default => null,
            };
        });
    }
}
