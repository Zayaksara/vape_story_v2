<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

    /** Status sale yang dihitung sebagai transaksi terjadi (termasuk yang di-return). */
    private const COUNTED_STATUSES = ['completed', 'partial_return', 'returned'];

    public function index(Request $request)
    {
        $period      = $request->query('period', 'daily');
        $singleDate  = $request->query('date');
        $customStart = $request->query('start_date');
        $customEnd   = $request->query('end_date');

        [$start, $end, $prevStart, $prevEnd] = $this->resolvePeriod(
            $period, $singleDate, $customStart, $customEnd
        );

        // Termasuk transaksi yang sebagian/seluruhnya di-return (status berubah jadi
        // partial_return/returned, tapi transaksinya tetap terjadi) — selaras Dashboard.
        $sales = Sale::with(['items.product', 'items.allocations', 'user', 'productReturns.returnItems'])
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', self::COUNTED_STATUSES)
            ->orderBy('created_at', 'desc')
            ->get();

        $transactions = $this->mapSalesToTransactions($sales);

        $summary = [
            'total_transactions' => $transactions->count(),
            // Nilai bersih (sudah dikurangi retur) agar konsisten dengan Dashboard.
            'total_sales' => $transactions->sum('total_amount'),
            'total_items' => $transactions->sum('net_quantity'),
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
            ->whereIn('status', self::COUNTED_STATUSES)
            ->selectRaw('COUNT(*) as total_transactions, COALESCE(SUM(total_amount), 0) as total_sales')
            ->first();

        // Kurangi total refund pada periode (selaras Dashboard) → nilai bersih.
        $refundTotal = (float) DB::table('returns')
            ->join('return_items', 'return_items.return_id', '=', 'returns.id')
            ->whereBetween('returns.created_at', [$start, $end])
            ->where('returns.status', '!=', 'rejected')
            ->sum('return_items.subtotal');

        return [
            'total_transactions' => (int)   ($row->total_transactions ?? 0),
            'total_sales'        => (float) ($row->total_sales ?? 0) - $refundTotal,
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
            ->whereIn('status', self::COUNTED_STATUSES)
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

        $sales = Sale::with(['items.product', 'items.allocations', 'user', 'productReturns.returnItems'])
            ->whereDate('created_at', $date)
            ->whereIn('status', self::COUNTED_STATUSES)
            ->orderBy('created_at', 'desc')
            ->get();

        $transactions = $this->mapSalesToTransactions($sales);

        $summary = [
            'total_transactions' => $transactions->count(),
            'total_sales' => $transactions->sum('total_amount'),
            'total_items' => $transactions->sum('net_quantity'),
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
        return $sales->map(function (Sale $sale) {
            $discountAmount = (float) $sale->discount_amount;
            $grossAmount    = (float) $sale->total_amount;
            $itemsSubtotal  = (float) $sale->items->sum(fn ($i) => (float) $i->total);
            $subtotal       = $itemsSubtotal > 0 ? $itemsSubtotal : ($grossAmount + $discountAmount);
            $discountFactor = $subtotal > 0 ? ($grossAmount / $subtotal) : 1.0;

            // Nilai refund tercatat (return_items.subtotal, sudah dibulatkan sesuai metode)
            // dari retur non-rejected — selaras dengan Dashboard.
            $refundTotal  = (float) $sale->productReturns
                ->filter(fn ($r) => (is_object($r->status) ? $r->status->value : $r->status) !== 'rejected')
                ->sum(fn ($r) => (float) $r->returnItems->sum('subtotal'));
            // Qty yang sudah di-return (dari snapshot alokasi FIFO).
            $returnedQty  = (int) $sale->items->sum(
                fn ($i) => $i->allocations->sum(fn ($a) => (int) $a->returned_quantity)
            );
            // Pembulatan ke rupiah utuh (tidak ada pecahan sen di transaksi nyata).
            $netAmount    = max(0, (float) round($grossAmount - $refundTotal));
            $netQuantity  = max(0, (int) $sale->items->sum(fn ($i) => (int) $i->quantity) - $returnedQty);
            $isReturned   = in_array($sale->status, ['partial_return', 'returned'], true);

            return [
                'id' => (string) $sale->id,
                'invoice_number' => 'SALE-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
                'payment_method' => $sale->payment_method,
                'status' => $isReturned ? $sale->status : 'success',
                'is_returned' => $isReturned,
                'tax_amount' => $netAmount,
                // total_amount = nilai bersih (sudah dikurangi retur), dibulatkan ke rupiah.
                'total_amount' => $netAmount,
                'gross_amount' => round($grossAmount),
                'returned_amount' => round($refundTotal),
                'net_quantity' => $netQuantity,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'has_discount' => $discountAmount > 0,
                'created_at' => $sale->created_at?->toISOString(),
                'cashier' => $sale->user ? [
                    'id' => (string) $sale->user->id,
                    'name' => $sale->user->name,
                ] : null,
                'items' => $sale->items->map(function ($item) use ($discountFactor) {
                    $lineTotal    = (float) $item->total;
                    $quantity     = (int) $item->quantity;
                    $returnedQty  = (int) $item->allocations->sum(fn ($a) => (int) $a->returned_quantity);
                    // HPP bersih dari snapshot alokasi FIFO (unit yg masih di customer).
                    $hppTotal     = round((float) $item->allocations->sum(
                        fn ($a) => (float) $a->unit_cost * ((int) $a->quantity - (int) $a->returned_quantity)
                    ));
                    $refundItem   = round((float) $item->allocations->sum(
                        fn ($a) => (float) $a->unit_price * (int) $a->returned_quantity
                    ));
                    // Pendapatan efektif (net diskon transaksi) − refund retur item ini.
                    $netRevenue   = max(0, round($lineTotal * $discountFactor) - $refundItem);
                    $itemDiscount = round($lineTotal - round($lineTotal * $discountFactor));
                    $profit       = round($netRevenue - $hppTotal);

                    return [
                        'id' => (string) $item->id,
                        'quantity' => $quantity,
                        'returned_quantity' => $returnedQty,
                        'unit_price' => (float) $item->unit_price,
                        'discount' => (float) ($item->discount ?? 0) + $itemDiscount,
                        'promo_discount' => (float) ($item->promo_discount ?? 0),
                        'promo_units' => (int) ($item->promo_units ?? 0),
                        'total' => $lineTotal,
                        'net_revenue' => $netRevenue,
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

}
