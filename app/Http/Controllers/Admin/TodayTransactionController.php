<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TodayTransactionController extends Controller
{
    private const STORE_ADDRESS = 'Jl. Raya Kedawung No.02, Panembahan, Kec. Plered, Kabupaten Cirebon, Jawa Barat 45154';

    private const REPORT_TITLE = 'Laporan Penjualan Harian Vape Story';

    private const TZ = 'Asia/Jakarta';

    public function index(Request $request)
    {
        $period      = $request->query('period', 'daily');
        $singleDate  = $request->query('date');
        $customStart = $request->query('start_date');
        $customEnd   = $request->query('end_date');

        [$start, $end, $prevStart, $prevEnd] = $this->resolvePeriod(
            $period, $singleDate, $customStart, $customEnd
        );

        $sales = Sale::with(['items.product', 'user'])
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        $transactions = $this->mapSalesToTransactions($sales);

        $summary = [
            'total_transactions' => $transactions->count(),
            'total_sales' => $transactions->sum('total_amount'),
            'total_items' => $transactions->sum(function ($t) {
                return collect($t['items'] ?? [])->sum('quantity');
            }),
            'payment_methods' => [
                'cash'          => $transactions->where('payment_method', 'cash')->sum('total_amount'),
                'bank_transfer' => $transactions->where('payment_method', 'bank_transfer')->sum('total_amount'),
                'qris'          => $transactions->where('payment_method', 'qris')->sum('total_amount'),
                'e_wallet'      => $transactions->where('payment_method', 'e_wallet')->sum('total_amount'),
            ],
        ];

        $viewer = $request->user();

        $curTotals  = $this->totalsFor($start, $end);
        $prevTotals = $this->totalsFor($prevStart, $prevEnd);
        $trend      = $this->buildTrend($start, $end, $prevStart, $prevEnd, $period);

        return Inertia::render('admin/ReportTodayTransaction', [
            'transactions'  => $transactions,
            'summary'       => $summary,
            'selectedDate'  => $start->copy()->setTimezone(self::TZ)->toDateString(),
            'today'         => now(self::TZ)->toDateString(),
            'report_title'  => self::REPORT_TITLE,
            'store_address' => self::STORE_ADDRESS,
            'cashier' => $viewer ? [
                'id'    => (string) $viewer->id,
                'name'  => $viewer->name,
                'email' => $viewer->email,
            ] : null,
            'period' => $period,
            'date_range' => [
                'start'      => $start->toDateString(),
                'end'        => $end->toDateString(),
                'prev_start' => $prevStart->toDateString(),
                'prev_end'   => $prevEnd->toDateString(),
            ],
            'comparison' => [
                'current'  => $curTotals,
                'previous' => $prevTotals,
            ],
            'trend' => $trend,
        ]);
    }

    // ─── Period resolution ────────────────────────────────────────────────────

    private function resolvePeriod(string $period, ?string $singleDate, ?string $customStart, ?string $customEnd): array
    {
        $tz   = self::TZ;
        $base = $singleDate ? Carbon::parse($singleDate, $tz) : Carbon::now($tz);

        switch ($period) {
            case 'weekly':
                $start = $base->copy()->startOfWeek();
                $end   = $base->copy()->endOfWeek();
                break;
            case 'monthly':
                $start = $base->copy()->startOfMonth();
                $end   = $base->copy()->endOfMonth();
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
                $end   = Carbon::parse($customEnd   ?? $base->toDateString(), $tz)->endOfDay();
                break;
            default: // daily
                $start = $base->copy()->startOfDay();
                $end   = $base->copy()->endOfDay();
        }

        $durationDays = max(1, (int) $start->diffInDays($end));
        $prevEnd      = $start->copy()->subSecond();
        $prevStart    = $prevEnd->copy()->subDays($durationDays)->startOfDay();

        return [$start, $end, $prevStart, $prevEnd];
    }

    // ─── Comparison totals ────────────────────────────────────────────────────

    private function totalsFor(Carbon $start, Carbon $end): array
    {
        $row = DB::table('sales')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as total_transactions, COALESCE(SUM(total_amount), 0) as total_sales')
            ->first();

        return [
            'total_transactions' => (int)   ($row->total_transactions ?? 0),
            'total_sales'        => (float) ($row->total_sales        ?? 0),
        ];
    }

    // ─── Trend (current vs previous, aligned by bucket index) ─────────────────

    private function buildTrend(Carbon $start, Carbon $end, Carbon $prevStart, Carbon $prevEnd, string $period): array
    {
        [$truncUnit, $labelFmt] = match ($period) {
            'daily'     => ['hour',  'HH24:00'],
            'quarterly' => ['week',  '"W"WW'],
            'yearly'    => ['month', 'Mon YYYY'],
            default     => ['day',   'DD Mon'], // weekly / monthly / custom
        };

        $cur  = $this->bucketSales($start, $end, $truncUnit, $labelFmt);
        $prev = $this->bucketSales($prevStart, $prevEnd, $truncUnit, $labelFmt);

        $count   = max(count($cur), count($prev));
        $labels  = [];
        $curArr  = [];
        $prevArr = [];

        for ($i = 0; $i < $count; $i++) {
            $labels[]  = $cur[$i]['label']   ?? $prev[$i]['label'] ?? '';
            $curArr[]  = $cur[$i]['revenue'] ?? 0;
            $prevArr[] = $prev[$i]['revenue'] ?? 0;
        }

        return [
            'labels'   => $labels,
            'current'  => $curArr,
            'previous' => $prevArr,
        ];
    }

    /**
     * @return array<int, array{label:string, revenue:float}>
     */
    private function bucketSales(Carbon $start, Carbon $end, string $truncUnit, string $labelFmt): array
    {
        $tz        = self::TZ;
        $truncExpr = "DATE_TRUNC('{$truncUnit}', created_at AT TIME ZONE '{$tz}')";
        $labelExpr = "TO_CHAR({$truncExpr}, '{$labelFmt}')";

        $rows = DB::table('sales')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->selectRaw("{$labelExpr} AS label, COALESCE(SUM(total_amount), 0) AS revenue, {$truncExpr} AS bucket")
            ->groupByRaw($truncExpr)
            ->orderByRaw($truncExpr)
            ->get();

        return $rows->map(fn ($r) => [
            'label'   => (string) $r->label,
            'revenue' => (float)  $r->revenue,
        ])->all();
    }

    // ─── Existing helpers ─────────────────────────────────────────────────────

    public function getData(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : now();

        $sales = Sale::with(['items.product', 'user'])
            ->whereDate('created_at', $date)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        $transactions = $this->mapSalesToTransactions($sales);

        $summary = [
            'total_transactions' => $transactions->count(),
            'total_sales' => $transactions->sum('total_amount'),
            'total_items' => $transactions->sum(function ($t) {
                return collect($t['items'] ?? [])->sum('quantity');
            }),
            'payment_methods' => [
                'cash'          => $transactions->where('payment_method', 'cash')->sum('total_amount'),
                'bank_transfer' => $transactions->where('payment_method', 'bank_transfer')->sum('total_amount'),
                'qris'          => $transactions->where('payment_method', 'qris')->sum('total_amount'),
                'e_wallet'      => $transactions->where('payment_method', 'e_wallet')->sum('total_amount'),
            ],
        ];

        return response()->json([
            'transactions' => $transactions,
            'summary'      => $summary,
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function mapSalesToTransactions(EloquentCollection $sales)
    {
        $avgCostByProduct = $this->averageBatchCostByProductId($sales);

        return $sales->map(function (Sale $sale) use ($avgCostByProduct) {
            return [
                'id' => (string) $sale->id,
                'invoice_number' => 'SALE-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
                'payment_method' => $sale->payment_method,
                'status' => 'success',
                'tax_amount' => (float) $sale->total_amount,
                'total_amount' => (float) $sale->total_amount,
                'created_at' => $sale->created_at?->toISOString(),
                'cashier' => $sale->user ? [
                    'id' => (string) $sale->user->id,
                    'name' => $sale->user->name,
                ] : null,
                'items' => $sale->items->map(function ($item) use ($avgCostByProduct) {
                    $unitCost  = (float) ($avgCostByProduct[(string) $item->product_id] ?? 0);
                    $lineTotal = (float) $item->total;
                    $quantity  = (int) $item->quantity;
                    $hppTotal  = round($unitCost * $quantity, 0);
                    $profit    = round($lineTotal - $hppTotal, 0);

                    return [
                        'id' => (string) $item->id,
                        'quantity' => $quantity,
                        'unit_price' => (float) $item->unit_price,
                        'total' => $lineTotal,
                        'hpp_total' => $hppTotal,
                        'profit' => $profit,
                        'product' => $item->product ? [
                            'id'   => $item->product->id,
                            'name' => $item->product->name,
                            'code' => $item->product->code,
                        ] : null,
                    ];
                })->values()->all(),
            ];
        })->values();
    }

    /**
     * @return array<string, float>
     */
    private function averageBatchCostByProductId(EloquentCollection $sales): array
    {
        $productIds = [];
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if ($item->product_id) {
                    $productIds[(string) $item->product_id] = true;
                }
            }
        }
        $ids = array_keys($productIds);
        if ($ids === []) {
            return [];
        }

        $rows = Batch::query()
            ->whereIn('product_id', $ids)
            ->groupBy('product_id')
            ->selectRaw('product_id, AVG(cost_price)::numeric as avg_cost')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row->product_id] = (float) $row->avg_cost;
        }

        return $out;
    }
}
