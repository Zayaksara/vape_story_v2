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
            'revenue_trend'   => $this->queryRevenueTrend($start, $end, $prevStart, $prevEnd, $period),
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

    private function queryRevenueTrend(Carbon $start, Carbon $end, Carbon $prevStart, Carbon $prevEnd, string $period): array
    {
        [$truncUnit, $labelFormat, $carbonStep] = match ($period) {
            'daily'     => ['hour',  'YYYY-MM-DD HH24:00', 'addHour'],
            'yearly'    => ['month', 'YYYY-MM-DD',         'addMonth'],
            'quarterly' => ['week',  'YYYY-MM-DD',         'addWeek'],
            default     => ['day',   'YYYY-MM-DD',         'addDay'],
        };

        $current  = $this->fetchTrendSeries($start, $end, $period, $truncUnit, $labelFormat, $carbonStep);
        $previous = $this->fetchTrendSeries($prevStart, $prevEnd, $period, $truncUnit, $labelFormat, $carbonStep);

        // Align previous to same bucket count as current (so they overlay on the same X axis).
        $bucketCount = count($current);
        $previous    = array_slice($previous, 0, $bucketCount);
        while (count($previous) < $bucketCount) {
            $previous[] = ['period' => '', 'revenue' => 0.0, 'transactions' => 0];
        }

        // Simple "tick" labels — index 1..N — so the chart x-axis is uniform across all 3 series.
        $labels = $this->buildTickLabels($current, $period);

        // Forecast: moving-average of last 3 buckets, extended forward for the same bucket count.
        $forecast = $this->buildForecast($current, window: 3);

        return [
            'current'  => $current,
            'previous' => $previous,
            'forecast' => $forecast,
            'labels'   => $labels,
            'summary'  => $this->buildTrendSummary($current),
        ];
    }

    private function fetchTrendSeries(Carbon $start, Carbon $end, string $period, string $truncUnit, string $labelFormat, string $carbonStep): array
    {
        $truncExpr = "DATE_TRUNC('{$truncUnit}', created_at AT TIME ZONE 'Asia/Jakarta')";
        $labelExpr = "TO_CHAR({$truncExpr}, '{$labelFormat}')";

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
            ->get()
            ->keyBy('period');

        return $this->buildTrendBuckets($start, $end, $period, $carbonStep, $labelFormat, $rows);
    }

    private function buildTickLabels(array $current, string $period): array
    {
        return array_map(function ($bucket) use ($period) {
            $key = $bucket['period'] ?? '';
            if ($key === '') {
                return '';
            }

            try {
                $dt = Carbon::parse($key, 'Asia/Jakarta');
            } catch (\Throwable) {
                return $key;
            }

            return match ($period) {
                'daily'     => $dt->format('H:00'),
                'yearly'    => $dt->translatedFormat('M'),
                'quarterly' => 'W' . $dt->isoWeek,
                default     => $dt->format('d M'),
            };
        }, $current);
    }

    private function buildForecast(array $current, int $window = 3): array
    {
        $forecast = [];
        $values   = array_column($current, 'revenue');
        $txValues = array_column($current, 'transactions');
        $count    = count($current);

        foreach ($current as $i => $bucket) {
            $isFuture = $bucket['revenue'] === 0.0 && $bucket['transactions'] === 0;
            $start    = max(0, $i - $window);
            $sliceRev = array_slice($values, $start, $i - $start);
            $sliceTx  = array_slice($txValues, $start, $i - $start);

            // Filter out zero values from the moving-average source so future-empty buckets
            // don't drag the projection down to 0.
            $sliceRev = array_filter($sliceRev, fn ($v) => $v > 0);
            $sliceTx  = array_filter($sliceTx, fn ($v) => $v > 0);

            $avgRev = count($sliceRev) > 0 ? array_sum($sliceRev) / count($sliceRev) : 0.0;
            $avgTx  = count($sliceTx)  > 0 ? array_sum($sliceTx)  / count($sliceTx)  : 0.0;

            $forecast[] = [
                'period'       => $bucket['period'],
                'revenue'      => $isFuture ? round($avgRev, 2) : $bucket['revenue'],
                'transactions' => $isFuture ? (int) round($avgTx) : $bucket['transactions'],
            ];

            unset($start, $sliceRev, $sliceTx);
        }

        // If nothing in current has data, forecast stays all zeros — frontend will hide the series.
        if (array_sum($values) <= 0) {
            return array_map(fn ($b) => ['period' => $b['period'], 'revenue' => 0.0, 'transactions' => 0], $current);
        }

        return $forecast;
    }

    private function buildTrendSummary(array $current): array
    {
        $maxRev = 0.0;
        $maxKey = '';
        $minRev = PHP_FLOAT_MAX;
        $minKey = '';
        $sumRev = 0.0;
        $sumTx  = 0;
        $nonZero = 0;

        foreach ($current as $b) {
            $sumRev += $b['revenue'];
            $sumTx  += $b['transactions'];

            if ($b['revenue'] > $maxRev) {
                $maxRev = $b['revenue'];
                $maxKey = $b['period'];
            }
            if ($b['revenue'] > 0 && $b['revenue'] < $minRev) {
                $minRev = $b['revenue'];
                $minKey = $b['period'];
            }
            if ($b['revenue'] > 0) {
                $nonZero++;
            }
        }

        if ($minRev === PHP_FLOAT_MAX) {
            $minRev = 0.0;
        }

        return [
            'max_revenue'        => $maxRev,
            'max_period'         => $maxKey,
            'min_revenue'        => $minRev,
            'min_period'         => $minKey,
            'avg_revenue'        => $nonZero > 0 ? round($sumRev / $nonZero, 2) : 0.0,
            'total_revenue'      => $sumRev,
            'total_transactions' => $sumTx,
        ];
    }

    /**
     * Generate a complete time series with zero-filled gaps so the chart always
     * has a continuous baseline (24 hours for daily, 7 days for weekly, etc.).
     */
    private function buildTrendBuckets(
        Carbon $start,
        Carbon $end,
        string $period,
        string $carbonStep,
        string $labelFormat,
        \Illuminate\Support\Collection $rows
    ): array {
        $tz = 'Asia/Jakarta';

        $cursor = match ($period) {
            'daily'     => $start->copy()->setTimezone($tz)->startOfDay(),
            'yearly'    => $start->copy()->setTimezone($tz)->startOfMonth(),
            'quarterly' => $start->copy()->setTimezone($tz)->startOfWeek(),
            default     => $start->copy()->setTimezone($tz)->startOfDay(),
        };

        $stop = $end->copy()->setTimezone($tz);

        $phpFormat = $this->pgFormatToPhpFormat($labelFormat);

        $buckets = [];
        $safety  = 0;

        while ($cursor->lte($stop) && $safety++ < 500) {
            $key  = $cursor->format($phpFormat);
            $row  = $rows->get($key);

            $buckets[] = [
                'period'       => $key,
                'revenue'      => $row ? (float) $row->revenue      : 0.0,
                'transactions' => $row ? (int)   $row->transactions : 0,
            ];

            $cursor->{$carbonStep}();
        }

        return $buckets;
    }

    private function pgFormatToPhpFormat(string $pgFormat): string
    {
        return strtr($pgFormat, [
            'YYYY'   => 'Y',
            'MM'     => 'm',
            'DD'     => 'd',
            'HH24'   => 'H',
            ':00'    => ':00',
        ]);
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
