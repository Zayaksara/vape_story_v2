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
            'stats' => $this->buildStats($start, $end, $prevStart, $prevEnd),
            'period' => $period,
            'date_range' => [
                'start'      => $start->toDateString(),
                'end'        => $end->toDateString(),
                'prev_start' => $prevStart->toDateString(),
                'prev_end'   => $prevEnd->toDateString(),
            ],
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

    // ─── Two focused DB queries ───────────────────────────────────────────────

    private function querySaleStats(Carbon $start, Carbon $end): array
    {
        // Q1: revenue + transaction count from sales table (no join = no duplicates)
        $saleRow = DB::table('sales')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as transactions, COALESCE(SUM(total_amount), 0) as revenue')
            ->first();

        // Q2: products sold + profit from sale_items (with average batch cost)
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
}
