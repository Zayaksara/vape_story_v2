<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportSaleController extends Controller
{
    private const TZ = 'Asia/Jakarta';

    public function index(Request $request)
    {
        [$start, $end] = $this->resolvePeriod($request);

        $byCategory = $this->aggregateByCategory($start, $end);
        $byBrand    = $this->aggregateByBrand($start, $end);
        $byProduct  = $this->aggregateByProduct($start, $end);
        $byPayment  = $this->aggregateByPayment($start, $end);
        $byStock    = $this->aggregateStock($start, $end);

        $summary = [
            'total_revenue'      => (float) $byPayment->sum('revenue'),
            'total_profit'       => (float) $byCategory->sum('profit'),
            'total_items'        => (int)   $byCategory->sum('qty'),
            'total_transactions' => (int)   $byPayment->sum('transactions'),
        ];

        return Inertia::render('admin/ReportSale', [
            'by_category'       => $byCategory->values(),
            'by_product'        => $byProduct->values(),
            'by_brand'          => $byBrand->values(),
            'by_payment_method' => $byPayment->values(),
            'by_stock'          => $byStock,
            'summary'           => $summary,
            'period'            => $request->query('period', 'monthly'),
            'date_range'        => [
                'start' => $start->toDateString(),
                'end'   => $end->toDateString(),
            ],
            'categories'        => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'brands'            => Brand::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$start, $end] = $this->resolvePeriod($request);
        $type = (string) $request->query('type', 'category');

        [$filename, $headers, $rows] = match ($type) {
            'product'        => $this->csvProduct($start, $end),
            'brand'          => $this->csvBrand($start, $end),
            'payment'        => $this->csvPayment($start, $end),
            'stock_top'      => $this->csvStockTop($start, $end),
            'stock_out'      => $this->csvStockOut(),
            default          => $this->csvCategory($start, $end),
        };

        $datePart = $start->format('Ymd').'-'.$end->format('Ymd');

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM for Excel UTF-8
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, "laporan-{$filename}-{$datePart}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // ─── Shopping list (Belanja?) ─────────────────────────────────────────────

    public function shoppingList(Request $request)
    {
        [$start, $end]         = $this->resolvePeriod($request);
        $format                = $request->query('format', 'pdf'); // 'pdf' | 'word'
        $categoryIds           = (array) $request->query('categories', []);
        $includeOutOfStock     = $request->boolean('include_out_of_stock', true);
        $includeTopSelling     = $request->boolean('include_top_selling', true);
        $topLimit              = (int) $request->query('top_limit', 20);

        $stockSub = $this->productStockSubquery();

        // Out of stock
        $outOfStock = collect();
        if ($includeOutOfStock) {
            $q = DB::table('products')
                ->leftJoinSub($stockSub, 'ps', 'ps.product_id', '=', 'products.id')
                ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->where('products.is_active', true)
                ->where(DB::raw('COALESCE(ps.stock, 0)'), '=', 0);

            if (! empty($categoryIds)) {
                $q->whereIn('products.category_id', $categoryIds);
            }

            $outOfStock = $q->select([
                'products.code',
                'products.name',
                DB::raw('categories.name as category'),
                DB::raw('brands.name as brand'),
            ])
                ->orderBy('categories.name')
                ->orderBy('products.name')
                ->get()
                ->map(fn ($r) => [
                    'code'     => $r->code,
                    'name'     => $r->name,
                    'category' => $r->category,
                    'brand'    => $r->brand,
                ])
                ->all();
        }

        // Top selling
        $topSelling = [];
        if ($includeTopSelling) {
            $q = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->leftJoinSub($stockSub, 'ps', 'ps.product_id', '=', 'sale_items.product_id')
                ->whereBetween('sales.created_at', [$start, $end])
                ->where('sales.status', 'completed')
                ->where(DB::raw('COALESCE(ps.stock, 0)'), '>', 0); // exclude stok habis

            if (! empty($categoryIds)) {
                $q->whereIn('products.category_id', $categoryIds);
            }

            $topSelling = $q->groupBy('products.id', 'products.code', 'products.name', 'categories.name', 'brands.name', 'ps.stock')
                ->select([
                    'products.code',
                    'products.name',
                    DB::raw('categories.name as category'),
                    DB::raw('brands.name as brand'),
                    DB::raw('SUM(sale_items.quantity)::int as qty_sold'),
                    DB::raw('SUM(sale_items.total)::numeric as revenue'),
                    DB::raw('COALESCE(ps.stock, 0)::int as stock_remaining'),
                ])
                ->orderByDesc('revenue') // ranking by % kontribusi revenue (kolom tidak ditampilkan di PDF)
                ->limit($topLimit)
                ->get()
                ->map(fn ($r) => [
                    'code'            => $r->code,
                    'name'            => $r->name,
                    'category'        => $r->category,
                    'brand'           => $r->brand,
                    'qty_sold'        => (int) $r->qty_sold,
                    'stock_remaining' => (int) $r->stock_remaining,
                ])
                ->all();
        }

        $filterCategories = [];
        if (! empty($categoryIds)) {
            $filterCategories = Category::whereIn('id', $categoryIds)->orderBy('name')->pluck('name')->all();
        }

        // Semua produk aktif untuk modal "Tambah Produk"
        $allProducts = DB::table('products')
            ->leftJoinSub($stockSub, 'ps', 'ps.product_id', '=', 'products.id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->where('products.is_active', true)
            ->select([
                'products.code',
                'products.name',
                DB::raw('categories.name as category'),
                DB::raw('brands.name as brand'),
                DB::raw('COALESCE(ps.stock, 0)::int as stock'),
            ])
            ->orderBy('products.name')
            ->get()
            ->map(fn ($r) => [
                'code'     => $r->code,
                'name'     => $r->name,
                'category' => $r->category,
                'brand'    => $r->brand,
                'stock'    => (int) $r->stock,
            ])
            ->all();

        $html = view('admin.shopping-list', [
            'out_of_stock'           => $outOfStock,
            'top_selling'            => $topSelling,
            'include_out_of_stock'   => $includeOutOfStock,
            'include_top_selling'    => $includeTopSelling,
            'filter_categories'      => $filterCategories,
            'all_products'           => $allProducts,
            'period_label'           => $start->isoFormat('D MMM YYYY').' — '.$end->isoFormat('D MMM YYYY'),
            'generated_at'           => Carbon::now(self::TZ)->isoFormat('dddd, D MMMM YYYY · HH:mm'),
        ])->render();

        $stamp = Carbon::now(self::TZ)->format('Ymd-His');

        if ($format === 'word') {
            return response($html, 200, [
                'Content-Type'        => 'application/vnd.ms-word; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"daftar-belanja-{$stamp}.doc\"",
            ]);
        }

        // PDF / interactive: return interactive HTML page (user customizes & prints sendiri)
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    // ─── Period ───────────────────────────────────────────────────────────────

    private function resolvePeriod(Request $request): array
    {
        $period      = $request->query('period', 'monthly');
        $singleDate  = $request->query('date');
        $customStart = $request->query('start_date');
        $customEnd   = $request->query('end_date');

        $tz   = self::TZ;
        $base = $singleDate ? Carbon::parse($singleDate, $tz) : Carbon::now($tz);

        switch ($period) {
            case 'daily':
                $start = $base->copy()->startOfDay();
                $end   = $base->copy()->endOfDay();
                break;
            case 'weekly':
                $start = $base->copy()->startOfWeek();
                $end   = $base->copy()->endOfWeek();
                break;
            case 'quarterly':
                $start = $base->copy()->startOfQuarter();
                $end   = $base->copy()->endOfQuarter();
                break;
            case 'yearly':
                $start = $base->copy()->startOfYear();
                $end   = $base->copy()->endOfYear();
                break;
            case 'custom':
                $start = Carbon::parse($customStart ?? $base->toDateString(), $tz)->startOfDay();
                $end   = Carbon::parse($customEnd ?? $base->toDateString(), $tz)->endOfDay();
                break;
            default: // monthly
                $start = $base->copy()->startOfMonth();
                $end   = $base->copy()->endOfMonth();
        }

        return [$start, $end];
    }

    // ─── Aggregations ─────────────────────────────────────────────────────────

    private function aggregateByCategory(Carbon $start, Carbon $end): Collection
    {
        $avgCostSub = $this->avgCostSubquery();
        $stockSub   = $this->productStockSubquery();

        $rows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoinSub($avgCostSub, 'ac', 'ac.product_id', '=', 'sale_items.product_id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.status', 'completed')
            ->groupBy('categories.id', 'categories.name')
            ->select([
                'categories.id',
                'categories.name',
                DB::raw('SUM(sale_items.quantity)::int as qty'),
                DB::raw('SUM(sale_items.total)::numeric as revenue'),
                DB::raw('SUM(sale_items.total - (sale_items.quantity * COALESCE(ac.avg_cost, 0)))::numeric as profit'),
            ])
            ->orderByDesc('revenue')
            ->get();

        // Stock per category from active products
        $stockMap = DB::table('products')
            ->leftJoinSub($stockSub, 'ps', 'ps.product_id', '=', 'products.id')
            ->where('products.is_active', true)
            ->groupBy('products.category_id')
            ->select('products.category_id', DB::raw('COALESCE(SUM(ps.stock), 0)::int as stock'))
            ->pluck('stock', 'category_id');

        return collect($rows)->map(fn ($r) => [
            'id'      => $r->id,
            'name'    => $r->name,
            'qty'     => (int) $r->qty,
            'revenue' => (float) $r->revenue,
            'profit'  => (float) $r->profit,
            'stock'   => (int) ($stockMap[$r->id] ?? 0),
        ]);
    }

    private function aggregateByBrand(Carbon $start, Carbon $end): Collection
    {
        $avgCostSub = $this->avgCostSubquery();
        $stockSub   = $this->productStockSubquery();

        $rows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoinSub($avgCostSub, 'ac', 'ac.product_id', '=', 'sale_items.product_id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.status', 'completed')
            ->groupBy('brands.id', 'brands.name')
            ->select([
                'brands.id',
                'brands.name',
                DB::raw('SUM(sale_items.quantity)::int as qty'),
                DB::raw('SUM(sale_items.total)::numeric as revenue'),
                DB::raw('SUM(sale_items.total - (sale_items.quantity * COALESCE(ac.avg_cost, 0)))::numeric as profit'),
            ])
            ->orderByDesc('revenue')
            ->get();

        $stockMap = DB::table('products')
            ->leftJoinSub($stockSub, 'ps', 'ps.product_id', '=', 'products.id')
            ->where('products.is_active', true)
            ->whereNotNull('products.brand_id')
            ->groupBy('products.brand_id')
            ->select('products.brand_id', DB::raw('COALESCE(SUM(ps.stock), 0)::int as stock'))
            ->pluck('stock', 'brand_id');

        return collect($rows)->map(fn ($r) => [
            'id'      => $r->id,
            'name'    => $r->name,
            'qty'     => (int) $r->qty,
            'revenue' => (float) $r->revenue,
            'profit'  => (float) $r->profit,
            'stock'   => (int) ($stockMap[$r->id] ?? 0),
        ]);
    }

    private function aggregateByProduct(Carbon $start, Carbon $end): Collection
    {
        $avgCostSub = $this->avgCostSubquery();
        $stockSub   = $this->productStockSubquery();

        $rows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoinSub($avgCostSub, 'ac', 'ac.product_id', '=', 'sale_items.product_id')
            ->leftJoinSub($stockSub, 'ps', 'ps.product_id', '=', 'sale_items.product_id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.status', 'completed')
            ->groupBy('products.id', 'products.code', 'products.name', 'categories.name', 'brands.name', 'ps.stock')
            ->select([
                'products.id',
                'products.code',
                'products.name',
                DB::raw('categories.name as category_name'),
                DB::raw('brands.name as brand_name'),
                DB::raw('SUM(sale_items.quantity)::int as qty'),
                DB::raw('SUM(sale_items.total)::numeric as revenue'),
                DB::raw('SUM(sale_items.total - (sale_items.quantity * COALESCE(ac.avg_cost, 0)))::numeric as profit'),
                DB::raw('COALESCE(ps.stock, 0)::int as stock'),
            ])
            ->orderByDesc('revenue')
            ->get();

        return collect($rows)->map(fn ($r) => [
            'id'       => $r->id,
            'code'     => $r->code,
            'name'     => $r->name,
            'category' => $r->category_name,
            'brand'    => $r->brand_name,
            'qty'      => (int) $r->qty,
            'revenue'  => (float) $r->revenue,
            'profit'   => (float) $r->profit,
            'stock'    => (int) $r->stock,
        ]);
    }

    private function aggregateByPayment(Carbon $start, Carbon $end): Collection
    {
        $rows = DB::table('sales')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->select([
                'payment_method',
                DB::raw('COUNT(*)::int as transactions'),
                DB::raw('COALESCE(SUM(total_amount), 0)::numeric as revenue'),
            ])
            ->orderByDesc('revenue')
            ->get();

        $totalRevenue = (float) $rows->sum('revenue') ?: 1.0;

        $labels = [
            'cash'          => 'Tunai',
            'bank_transfer' => 'Transfer',
            'qris'          => 'QRIS',
            'e_wallet'      => 'E-Wallet',
        ];

        return collect($rows)->map(fn ($r) => [
            'method'       => (string) $r->payment_method,
            'label'        => $labels[$r->payment_method] ?? ucfirst(str_replace('_', ' ', (string) $r->payment_method)),
            'transactions' => (int) $r->transactions,
            'revenue'      => (float) $r->revenue,
            'percentage'   => round(((float) $r->revenue / $totalRevenue) * 100, 2),
        ]);
    }

    private function aggregateStock(Carbon $start, Carbon $end): array
    {
        $stockSub = $this->productStockSubquery();

        // Total revenue periode (untuk hitung persentase kontribusi tiap produk)
        $totalRevenue = (float) DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.status', 'completed')
            ->sum('sale_items.total') ?: 1.0;

        $topSelling = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoinSub($stockSub, 'ps', 'ps.product_id', '=', 'sale_items.product_id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.status', 'completed')
            ->groupBy('products.id', 'products.code', 'products.name', 'categories.name', 'brands.name', 'ps.stock')
            ->select([
                'products.id',
                'products.code',
                'products.name',
                DB::raw('categories.name as category_name'),
                DB::raw('brands.name as brand_name'),
                DB::raw('SUM(sale_items.quantity)::int as qty_sold'),
                DB::raw('SUM(sale_items.total)::numeric as revenue'),
                DB::raw('COALESCE(ps.stock, 0)::int as stock_remaining'),
            ])
            ->orderByDesc('revenue')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'id'               => $r->id,
                'code'             => $r->code,
                'name'             => $r->name,
                'category'         => $r->category_name,
                'brand'            => $r->brand_name,
                'qty_sold'         => (int) $r->qty_sold,
                'revenue'          => (float) $r->revenue,
                'sales_percentage' => round(((float) $r->revenue / $totalRevenue) * 100, 2),
                'stock_remaining'  => (int) $r->stock_remaining,
            ])->values();

        // Last sold timestamp per product (subquery)
        $lastSoldSub = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->groupBy('sale_items.product_id')
            ->select('sale_items.product_id', DB::raw('MAX(sales.created_at) as last_sold_at'));

        $outOfStock = DB::table('products')
            ->leftJoinSub($stockSub, 'ps', 'ps.product_id', '=', 'products.id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoinSub($lastSoldSub, 'ls', 'ls.product_id', '=', 'products.id')
            ->where('products.is_active', true)
            ->where(DB::raw('COALESCE(ps.stock, 0)'), '=', 0)
            ->select([
                'products.id',
                'products.code',
                'products.name',
                DB::raw('categories.name as category_name'),
                DB::raw('brands.name as brand_name'),
                DB::raw('ls.last_sold_at'),
            ])
            ->orderBy('products.name')
            ->get()
            ->map(fn ($r) => [
                'id'           => $r->id,
                'code'         => $r->code,
                'name'         => $r->name,
                'category'     => $r->category_name,
                'brand'        => $r->brand_name,
                'last_sold_at' => $r->last_sold_at ? Carbon::parse($r->last_sold_at)->toIso8601String() : null,
            ])->values();

        return [
            'top_selling'  => $topSelling,
            'out_of_stock' => $outOfStock,
        ];
    }

    // ─── Subqueries ───────────────────────────────────────────────────────────

    private function avgCostSubquery()
    {
        return DB::table('batches')
            ->groupBy('product_id')
            ->select('product_id', DB::raw('AVG(cost_price)::numeric as avg_cost'));
    }

    private function productStockSubquery()
    {
        return DB::table('batches')
            ->groupBy('product_id')
            ->select('product_id', DB::raw('COALESCE(SUM(stock_quantity), 0)::int as stock'));
    }

    // ─── CSV generators ───────────────────────────────────────────────────────

    private function csvCategory(Carbon $start, Carbon $end): array
    {
        $rows = $this->aggregateByCategory($start, $end);
        $headers = ['Kategori', 'Qty Terjual', 'Revenue', 'Profit', 'Stok Saat Ini'];
        $data = $rows->map(fn ($r) => [$r['name'], $r['qty'], $r['revenue'], $r['profit'], $r['stock']])->all();

        return ['kategori', $headers, $data];
    }

    private function csvProduct(Carbon $start, Carbon $end): array
    {
        $rows = $this->aggregateByProduct($start, $end);
        $headers = ['Kode', 'Nama Produk', 'Kategori', 'Merek', 'Qty', 'Revenue', 'Profit', 'Stok'];
        $data = $rows->map(fn ($r) => [
            $r['code'], $r['name'], $r['category'], $r['brand'],
            $r['qty'], $r['revenue'], $r['profit'], $r['stock'],
        ])->all();

        return ['produk', $headers, $data];
    }

    private function csvBrand(Carbon $start, Carbon $end): array
    {
        $rows = $this->aggregateByBrand($start, $end);
        $headers = ['Merek', 'Qty Terjual', 'Revenue', 'Profit', 'Stok Saat Ini'];
        $data = $rows->map(fn ($r) => [$r['name'], $r['qty'], $r['revenue'], $r['profit'], $r['stock']])->all();

        return ['merek', $headers, $data];
    }

    private function csvPayment(Carbon $start, Carbon $end): array
    {
        $rows = $this->aggregateByPayment($start, $end);
        $headers = ['Metode Pembayaran', 'Jumlah Transaksi', 'Revenue', 'Persentase (%)'];
        $data = $rows->map(fn ($r) => [$r['label'], $r['transactions'], $r['revenue'], $r['percentage']])->all();

        return ['metode-bayar', $headers, $data];
    }

    private function csvStockTop(Carbon $start, Carbon $end): array
    {
        $rows = $this->aggregateStock($start, $end)['top_selling'];
        $headers = ['Kode', 'Nama Produk', 'Kategori', 'Merek', 'Qty Terjual', 'Revenue', 'Stok Sisa'];
        $data = $rows->map(fn ($r) => [
            $r['code'], $r['name'], $r['category'], $r['brand'],
            $r['qty_sold'], $r['revenue'], $r['stock_remaining'],
        ])->all();

        return ['stok-terlaris', $headers, $data];
    }

    private function csvStockOut(): array
    {
        $stockSub = $this->productStockSubquery();
        $rows = DB::table('products')
            ->leftJoinSub($stockSub, 'ps', 'ps.product_id', '=', 'products.id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->where('products.is_active', true)
            ->where(DB::raw('COALESCE(ps.stock, 0)'), '=', 0)
            ->select([
                'products.code',
                'products.name',
                DB::raw('categories.name as category_name'),
                DB::raw('brands.name as brand_name'),
            ])
            ->orderBy('products.name')
            ->get();

        $headers = ['Kode', 'Nama Produk', 'Kategori', 'Merek'];
        $data = $rows->map(fn ($r) => [$r->code, $r->name, $r->category_name, $r->brand_name])->all();

        return ['stok-habis', $headers, $data];
    }
}
