<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period      = $request->query('period', 'daily');
        $customStart = $request->query('start_date');
        $customEnd   = $request->query('end_date');

        [$start, $end, $prevStart, $prevEnd] = $this->resolvePeriod($period, $customStart, $customEnd);

        return Inertia::render('admin/Dashboard', [
            'stats'           => $this->buildStats($start, $end, $prevStart, $prevEnd),
            'period'          => $period,
            'date_range'      => [
                'start'      => $start->toDateString(),
                'end'        => $end->toDateString(),
                'prev_start' => $prevStart->toDateString(),
                'prev_end'   => $prevEnd->toDateString(),
            ],
            'revenue_trend'   => $this->queryRevenueTrend($start, $end, $period),
            'top_products'    => $this->queryTopProducts($start, $end),
            'top_categories'  => $this->queryTopCategories($start, $end),
            'top_brands'      => $this->queryTopBrands($start, $end),
            'payment_methods' => $this->queryPaymentMethods($start, $end),
        ]);
    }

    // ─── Period resolution ────────────────────────────────────────────────────

    private function resolvePeriod(string $period, ?string $customStart, ?string $customEnd): array
    {
        $tz  = 'Asia/Jakarta';
        $now = Carbon::now($tz);

        switch ($period) {
            case 'weekly':
                $start = $now->copy()->startOfWeek();
                $end   = $now->copy()->endOfWeek();
                break;
            case 'monthly':
                $start = $now->copy()->startOfMonth();
                $end   = $now->copy()->endOfMonth();
                break;
            case 'quarterly':
                $start = $now->copy()->startOfQuarter();
                $end   = $now->copy()->endOfQuarter();
                break;
            case 'yearly':
                $start = $now->copy()->startOfYear();
                $end   = $now->copy()->endOfYear();
                break;
            case 'custom':
                $start = Carbon::parse($customStart ?? $now->toDateString(), $tz)->startOfDay();
                $end   = Carbon::parse($customEnd   ?? $now->toDateString(), $tz)->endOfDay();
                break;
            default: // daily
                $start = $now->copy()->startOfDay();
                $end   = $now->copy()->endOfDay();
        }

        $durationDays = max(1, (int) $start->diffInDays($end));
        $prevEnd      = $start->copy()->subSecond();
        $prevStart    = $prevEnd->copy()->subDays($durationDays)->startOfDay();

        return [$start, $end, $prevStart, $prevEnd];
    }

    // ─── Stats assembly ───────────────────────────────────────────────────────

    private function buildStats(Carbon $start, Carbon $end, Carbon $prevStart, Carbon $prevEnd): array
    {
        $cur  = $this->querySaleStats($start, $end);
        $prev = $this->querySaleStats($prevStart, $prevEnd);

        return [
            'revenue'       => $this->metric($cur['revenue'],       $prev['revenue']),
            'transactions'  => $this->metric($cur['transactions'],  $prev['transactions']),
            'profit'        => $this->metric($cur['profit'],        $prev['profit']),
            'products_sold' => $this->metric($cur['products_sold'], $prev['products_sold']),
        ];
    }

    // ─── Base sale stats ──────────────────────────────────────────────────────

    private function querySaleStats(Carbon $start, Carbon $end): array
    {
        $saleRow = DB::table('sales')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as transactions, COALESCE(SUM(total_amount), 0) as revenue')
            ->first();

        $itemRow = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->leftJoin(
                DB::raw('(SELECT product_id, AVG(cost_price)::numeric AS avg_cost FROM batches GROUP BY product_id) AS b'),
                'b.product_id', '=', 'si.product_id'
            )
            ->whereBetween('s.created_at', [$start, $end])
            ->where('s.status', 'completed')
            ->selectRaw('
                COALESCE(SUM(si.quantity), 0) AS products_sold,
                COALESCE(SUM(si.total - COALESCE(b.avg_cost, 0) * si.quantity), 0) AS profit
            ')
            ->first();

        return [
            'revenue'       => (float) ($saleRow->revenue       ?? 0),
            'transactions'  => (int)   ($saleRow->transactions  ?? 0),
            'products_sold' => (int)   ($itemRow->products_sold ?? 0),
            'profit'        => (float) ($itemRow->profit        ?? 0),
        ];
    }

    private function metric(float|int $current, float|int $previous): array
    {
        if ($previous != 0) {
            $pct = round((($current - $previous) / abs($previous)) * 100, 1);
        } else {
            $pct = $current > 0 ? 100.0 : 0.0;
        }

        return [
            'current'    => $current,
            'previous'   => $previous,
            'change_pct' => $pct,
        ];
    }

    // ─── Revenue trend (time-series) ──────────────────────────────────────────

    private function queryRevenueTrend(Carbon $start, Carbon $end, string $period): array
    {
        if ($period === 'daily') {
            // Group by hour
            $truncExpr = "DATE_TRUNC('hour', created_at AT TIME ZONE 'Asia/Jakarta')";
            $labelExpr = "TO_CHAR(DATE_TRUNC('hour', created_at AT TIME ZONE 'Asia/Jakarta'), 'YYYY-MM-DD HH24:00')";
        } elseif ($period === 'yearly') {
            // Group by month
            $truncExpr = "DATE_TRUNC('month', created_at AT TIME ZONE 'Asia/Jakarta')";
            $labelExpr = "TO_CHAR(DATE_TRUNC('month', created_at AT TIME ZONE 'Asia/Jakarta'), 'YYYY-MM-DD')";
        } elseif ($period === 'quarterly') {
            // Group by week
            $truncExpr = "DATE_TRUNC('week', created_at AT TIME ZONE 'Asia/Jakarta')";
            $labelExpr = "TO_CHAR(DATE_TRUNC('week', created_at AT TIME ZONE 'Asia/Jakarta'), 'YYYY-MM-DD')";
        } else {
            // weekly, monthly, custom → group by day
            $truncExpr = "DATE_TRUNC('day', created_at AT TIME ZONE 'Asia/Jakarta')";
            $labelExpr = "TO_CHAR(DATE_TRUNC('day', created_at AT TIME ZONE 'Asia/Jakarta'), 'YYYY-MM-DD')";
        }

        $rows = DB::table('sales')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->selectRaw("
                {$labelExpr} AS period,
                COALESCE(SUM(total_amount), 0) AS revenue,
                COUNT(*) AS transactions
            ")
            ->groupByRaw($truncExpr)
            ->orderByRaw($truncExpr)
            ->get();

        return $rows->map(fn ($r) => [
            'period'       => $r->period,
            'revenue'      => (float) $r->revenue,
            'transactions' => (int)   $r->transactions,
        ])->values()->all();
    }

    // ─── Top 5 products ───────────────────────────────────────────────────────

    private function queryTopProducts(Carbon $start, Carbon $end, int $limit = 5): array
    {
        $rows = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->join('products as p', 'si.product_id', '=', 'p.id')
            ->whereBetween('s.created_at', [$start, $end])
            ->where('s.status', 'completed')
            ->selectRaw('p.name, COALESCE(SUM(si.total), 0) AS revenue, COALESCE(SUM(si.quantity), 0) AS qty')
            ->groupBy('p.id', 'p.name')
            ->orderByRaw('revenue DESC')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => [
            'name'    => $r->name,
            'revenue' => (float) $r->revenue,
            'qty'     => (int)   $r->qty,
        ])->values()->all();
    }

    // ─── Top 5 categories ─────────────────────────────────────────────────────

    private function queryTopCategories(Carbon $start, Carbon $end, int $limit = 5): array
    {
        $rows = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->join('products as p', 'si.product_id', '=', 'p.id')
            ->join('categories as c', 'p.category_id', '=', 'c.id')
            ->whereBetween('s.created_at', [$start, $end])
            ->where('s.status', 'completed')
            ->selectRaw('c.name, COALESCE(SUM(si.total), 0) AS revenue, COALESCE(SUM(si.quantity), 0) AS qty')
            ->groupBy('c.id', 'c.name')
            ->orderByRaw('revenue DESC')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => [
            'name'    => $r->name,
            'revenue' => (float) $r->revenue,
            'qty'     => (int)   $r->qty,
        ])->values()->all();
    }

    // ─── Top 5 brands ─────────────────────────────────────────────────────────

    private function queryTopBrands(Carbon $start, Carbon $end, int $limit = 5): array
    {
        $rows = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->join('products as p', 'si.product_id', '=', 'p.id')
            ->join('brands as b', 'p.brand_id', '=', 'b.id')
            ->whereBetween('s.created_at', [$start, $end])
            ->where('s.status', 'completed')
            ->selectRaw('b.name, COALESCE(SUM(si.total), 0) AS revenue, COALESCE(SUM(si.quantity), 0) AS qty')
            ->groupBy('b.id', 'b.name')
            ->orderByRaw('revenue DESC')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => [
            'name'    => $r->name,
            'revenue' => (float) $r->revenue,
            'qty'     => (int)   $r->qty,
        ])->values()->all();
    }

    // ─── Payment methods ──────────────────────────────────────────────────────

    private function queryPaymentMethods(Carbon $start, Carbon $end): array
    {
        $rows = DB::table('sales')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->selectRaw('payment_method AS method, COUNT(*) AS count, COALESCE(SUM(total_amount), 0) AS revenue')
            ->groupBy('payment_method')
            ->orderByRaw('revenue DESC')
            ->get();

        return $rows->map(fn ($r) => [
            'method'  => $r->method,
            'count'   => (int)   $r->count,
            'revenue' => (float) $r->revenue,
        ])->values()->all();
    }
}
