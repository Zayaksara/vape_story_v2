<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\OpeningBalance;
use App\Models\ProductReturn;
use App\Models\Sale;
use App\Models\StockMutation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : Carbon::today()->startOfDay();
        $to = $request->query('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : Carbon::today()->endOfDay();

        // Filter status sama persis dengan Dashboard & ReportSale agar angka selaras.
        $sales = Sale::with([
            'items.product',
            'items.allocations.batch',
            'user',
            'productReturns.returnItems',
        ])
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('status', self::COMPLETED_STATUSES)
            ->orderBy('created_at', 'desc')
            ->get();

        $salesData = $sales->map(function (Sale $sale) {
            $items = $sale->items->map(function ($item) {
                $allocations = $item->allocations->map(function ($a) {
                    $qty       = (int) $a->quantity;
                    $returned  = (int) $a->returned_quantity;
                    $netQty    = max(0, $qty - $returned);

                    return [
                        'id'           => (int) $a->id,
                        'batch_id'     => (string) $a->batch_id,
                        'batch_lot'    => $a->batch?->lot_number,
                        'cukai_year'   => $a->batch?->cukai_year,
                        'quantity'     => $qty,
                        'returned_qty' => $returned,
                        'unit_cost'    => (float) $a->unit_cost,
                        'unit_price'   => (float) $a->unit_price,
                        'is_promo'     => (bool) $a->is_promo,
                        'is_synthetic' => (bool) ($a->is_synthetic ?? false),
                        'line_cost'    => (float) $a->unit_cost * $qty,
                        'line_revenue' => (float) $a->unit_price * $qty,
                        'returned_cost'=> (float) $a->unit_cost * $returned,
                        'net_cost'     => (float) $a->unit_cost * $netQty,
                        'net_revenue'  => (float) $a->unit_price * $netQty,
                    ];
                })->values()->all();

                $hpp          = array_sum(array_column($allocations, 'line_cost'));
                $hppReturned  = array_sum(array_column($allocations, 'returned_cost'));
                $revenue      = array_sum(array_column($allocations, 'line_revenue'));

                // Profit kotor "murni jualan": (nilai jual − modal) atas unit yang
                // benar-benar tinggal di customer (qty − returned). Tidak dipotong
                // diskon transaksi/manual/promo; unit yang diretur dikeluarkan.
                $grossProfitItem = array_sum(array_map(
                    fn ($a) => $a['net_revenue'] - $a['net_cost'],
                    $allocations
                ));

                return [
                    'id'             => (int) $item->id,
                    'product_id'     => (string) $item->product_id,
                    'product_name'   => $item->product?->name ?? '-',
                    'product_code'   => $item->product?->code,
                    'quantity'       => (int) $item->quantity,
                    'unit_price'     => (float) $item->unit_price,
                    'manual_discount'=> (float) ($item->discount ?? 0),
                    'promo_discount' => (float) ($item->promo_discount ?? 0),
                    'promo_units'    => (int) ($item->promo_units ?? 0),
                    'line_total'     => (float) $item->total,
                    'hpp_total'      => $hpp,
                    'hpp_returned'   => $hppReturned,
                    'revenue_listed' => $revenue,
                    'gross_profit'   => $grossProfitItem,
                    'profit_pre_txn_discount' => $revenue - $hpp - (float) ($item->discount ?? 0),
                    'has_allocations' => count($allocations) > 0,
                    'allocations'    => $allocations,
                ];
            })->values()->all();

            $hppSale       = array_sum(array_column($items, 'hpp_total'));
            $hppReturnedSale = array_sum(array_column($items, 'hpp_returned'));
            $promoSavings  = array_sum(array_column($items, 'promo_discount'));
            $manualDisc    = array_sum(array_column($items, 'manual_discount'));

            // Refund hanya dihitung dari retur yang benar-benar diproses/approved.
            $refunded = (float) $sale->productReturns
                ->filter(fn ($r) => ! in_array(
                    $r->status?->value ?? (string) $r->status,
                    ['rejected'],
                    true
                ))
                ->flatMap->returnItems
                ->sum('subtotal');

            // Profit kotor = Σ (nilai jual − modal) atas unit non-retur, sebelum diskon.
            $grossProfit = array_sum(array_column($items, 'gross_profit'));
            $netRevenue  = (float) $sale->total_amount - $refunded;
            $netHpp      = $hppSale - $hppReturnedSale;
            $netProfit   = $netRevenue - $netHpp;

            return [
                'id'              => (int) $sale->id,
                'invoice'         => 'SALE-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
                'created_at'      => $sale->created_at?->toISOString(),
                'status'          => $sale->status,
                'payment_method'  => $sale->payment_method,
                'cashier'         => $sale->user?->name,
                'total_amount'    => (float) $sale->total_amount,
                'paid_amount'     => (float) $sale->paid_amount,
                'txn_discount'    => (float) $sale->discount_amount,
                'discount_code'   => $sale->discount_code,
                'discount_label'  => $sale->discount_label,
                'tax_amount'      => (float) $sale->tax_amount,
                'hpp_total'       => $hppSale,
                'hpp_returned'    => $hppReturnedSale,
                'refunded'        => $refunded,
                'net_revenue'     => $netRevenue,
                'net_hpp'         => $netHpp,
                'manual_discount_total' => $manualDisc,
                'promo_savings'   => $promoSavings,
                'profit'          => $grossProfit,
                'profit_net'      => $netProfit,
                'has_return'      => $sale->productReturns->isNotEmpty(),
                'items'           => $items,
            ];
        })->values();

        // Retur: exclude 'rejected' supaya selaras dengan Dashboard refundTotal & ReportSale total_refund.
        $returns = ProductReturn::with(['returnItems.batch', 'cashier', 'sale'])
            ->whereBetween('created_at', [$from, $to])
            ->where('status', '!=', 'rejected')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (ProductReturn $r) {
                return [
                    'id'             => (string) $r->id,
                    'return_number'  => $r->return_number,
                    'sale_id'        => $r->sale_id,
                    'invoice'        => $r->sale_id ? 'SALE-'.str_pad((string) $r->sale_id, 6, '0', STR_PAD_LEFT) : '-',
                    'status'         => $r->status?->value ?? (string) $r->status,
                    'reason'         => $r->reason,
                    'created_at'     => $r->created_at?->toISOString(),
                    'cashier'        => $r->cashier?->name,
                    'total_refunded' => (float) $r->returnItems->sum('subtotal'),
                    'items'          => $r->returnItems->map(fn ($it) => [
                        'product_name' => $it->product_name,
                        'batch_id'     => (string) $it->batch_id,
                        'batch_lot'    => $it->batch?->lot_number,
                        'quantity'     => (int) $it->quantity,
                        'unit_price'   => (float) $it->unit_price,
                        'subtotal'     => (float) $it->subtotal,
                    ])->values()->all(),
                ];
            });

        $mutations = StockMutation::with('batch.product')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get()
            ->map(fn (StockMutation $m) => [
                'id'           => (string) $m->id,
                'created_at'   => $m->created_at?->toISOString(),
                'type'         => $m->mutation_type?->value ?? (string) $m->mutation_type,
                'quantity'     => (int) $m->quantity,
                'product_name' => $m->batch?->product?->name ?? '-',
                'batch_lot'    => $m->batch?->lot_number,
                'cukai_year'   => $m->batch?->cukai_year,
                'notes'        => $m->notes,
                'reference'    => $m->reference_type.' #'.$m->reference_id,
            ]);

        $batches = Batch::with('product')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Batch $b) => [
                'id'             => (string) $b->id,
                'product_name'   => $b->product?->name ?? '-',
                'lot_number'     => $b->lot_number,
                'cukai_year'     => $b->cukai_year,
                'is_promo'       => (bool) $b->is_promo,
                'cost_price'     => (float) $b->cost_price,
                'promo_price'    => $b->promo_price !== null ? (float) $b->promo_price : null,
                'stock_quantity' => (int) $b->stock_quantity,
                'stock_value'    => (float) $b->cost_price * (int) $b->stock_quantity,
                'created_at'     => $b->created_at?->toISOString(),
            ]);

        // Produk terjual bersih (qty − returned_qty) — selaras dengan Dashboard "Produk Terjual"
        // dan ReportSale "Item Terjual".
        $productsSoldNet = 0;
        foreach ($salesData as $s) {
            foreach ($s['items'] as $it) {
                $returnedQty = collect($it['allocations'])->sum('returned_qty');
                $productsSoldNet += max(0, (int) $it['quantity'] - (int) $returnedQty);
            }
        }

        // Net revenue setelah refund — sama dengan "Total Pendapatan" di Dashboard
        // dan "Total Revenue" di ReportSale summary.
        $netRevenueAfterRefund = $salesData->sum('total_amount') - $returns->sum('total_refunded');

        // Cross-period refund detection: retur yang DIBUAT di periode ini, tapi
        // SALE aslinya di luar periode → bikin profit periode lama menyesatkan.
        $salesIdsInPeriod = $sales->pluck('id')->all();
        $crossPeriodRefund = ProductReturn::with('returnItems')
            ->whereBetween('created_at', [$from, $to])
            ->where('status', '!=', 'rejected')
            ->whereNotIn('sale_id', $salesIdsInPeriod)
            ->whereNotNull('sale_id')
            ->get();
        $crossPeriodRefundAmount = (float) $crossPeriodRefund->flatMap->returnItems->sum('subtotal');
        $crossPeriodRefundCount = $crossPeriodRefund->count();

        $totals = [
            'gross_revenue'        => $salesData->sum(fn ($s) => $s['total_amount'] + $s['txn_discount']),
            'txn_discount'         => $salesData->sum('txn_discount'),
            'manual_discount'      => $salesData->sum('manual_discount_total'),
            'promo_savings'        => $salesData->sum('promo_savings'),
            'net_revenue'          => $salesData->sum('total_amount'),
            'net_revenue_after_refund' => $netRevenueAfterRefund,
            'hpp'                  => $salesData->sum('hpp_total'),
            'hpp_returned'         => $salesData->sum('hpp_returned'),
            'profit'               => $salesData->sum('profit'),
            'profit_net'           => $salesData->sum('profit_net'),
            'tax'                  => $salesData->sum('tax_amount'),
            'refunded'             => $returns->sum('total_refunded'),
            'sales_count'          => $salesData->count(),
            'returns_count'        => $returns->count(),
            'products_sold_net'    => $productsSoldNet,
            'inventory_value'      => $batches->sum('stock_value'),
            'cross_period_refund_count'  => $crossPeriodRefundCount,
            'cross_period_refund_amount' => $crossPeriodRefundAmount,
        ];

        $neraca = $this->buildNeraca($from, $to, (float) $totals['inventory_value']);

        return Inertia::render('admin/Audit', [
            'from'      => $from->format('Y-m-d'),
            'to'        => $to->format('Y-m-d'),
            'sales'     => $salesData,
            'returns'   => $returns,
            'mutations' => $mutations,
            'batches'   => $batches,
            'totals'    => $totals,
            'neraca'    => $neraca,
        ]);
    }

    // ─── Status sale yang dianggap "transaksi terjadi" ───────────────────────
    // Sama dengan filter di Dashboard & ReportSale agar angka konsisten.
    private const COMPLETED_STATUSES = ['completed', 'partial_return', 'returned'];

    /**
     * Halaman tracing detail Neraca — buka sumber tiap baris (kas, bank,
     * persediaan, modal, laba, dll) dalam bentuk daftar transaksi sumbernya.
     */
    public function neracaDetail(Request $request)
    {
        $from = $request->query('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : Carbon::today()->startOfDay();
        $to = $request->query('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : Carbon::today()->endOfDay();

        $opening = \App\Models\OpeningBalance::current();
        $asOf = $opening->as_of_date
            ? Carbon::parse($opening->as_of_date)->startOfDay()
            : Carbon::create(2020, 1, 1)->startOfDay();

        // ── KAS — sumber pergerakan kas tunai ────────────────────────────
        $cashIn = DB::table('sales')
            ->whereBetween('created_at', [$asOf, $to])
            ->whereIn('status', self::COMPLETED_STATUSES)
            ->where('payment_method', 'cash')
            ->orderBy('created_at')
            ->get([
                'id', 'created_at', 'total_amount', 'discount_amount', 'payment_method', 'status',
            ])
            ->map(fn ($s) => [
                'id'         => (int) $s->id,
                'invoice'    => 'SALE-'.str_pad((string) $s->id, 6, '0', STR_PAD_LEFT),
                'created_at' => $s->created_at,
                'amount'     => (float) $s->total_amount,
                'status'     => $s->status,
            ]);

        $cashOut = DB::table('returns')
            ->join('return_items', 'return_items.return_id', '=', 'returns.id')
            ->join('sales', 'sales.id', '=', 'returns.sale_id')
            ->whereBetween('returns.created_at', [$asOf, $to])
            ->where('returns.status', '!=', 'rejected')
            ->whereRaw('COALESCE(returns.refund_method, sales.payment_method) = ?', ['cash'])
            ->groupBy('returns.id', 'returns.return_number', 'returns.created_at', 'returns.sale_id')
            ->orderBy('returns.created_at')
            ->select([
                'returns.id',
                'returns.return_number',
                'returns.created_at',
                'returns.sale_id',
                DB::raw('SUM(return_items.subtotal)::numeric as amount'),
            ])
            ->get()
            ->map(fn ($r) => [
                'id'            => (string) $r->id,
                'return_number' => $r->return_number,
                'invoice'       => $r->sale_id ? 'SALE-'.str_pad((string) $r->sale_id, 6, '0', STR_PAD_LEFT) : '-',
                'created_at'    => $r->created_at,
                'amount'        => (float) $r->amount,
            ]);

        // ── BANK / E-WALLET / QRIS ───────────────────────────────────────
        $bankIn = DB::table('sales')
            ->whereBetween('created_at', [$asOf, $to])
            ->whereIn('status', self::COMPLETED_STATUSES)
            ->whereIn('payment_method', ['bank_transfer', 'qris', 'e_wallet'])
            ->orderBy('created_at')
            ->get(['id', 'created_at', 'total_amount', 'payment_method', 'status'])
            ->map(fn ($s) => [
                'id'             => (int) $s->id,
                'invoice'        => 'SALE-'.str_pad((string) $s->id, 6, '0', STR_PAD_LEFT),
                'created_at'     => $s->created_at,
                'amount'         => (float) $s->total_amount,
                'payment_method' => $s->payment_method,
                'status'         => $s->status,
            ]);

        $bankOut = DB::table('returns')
            ->join('return_items', 'return_items.return_id', '=', 'returns.id')
            ->join('sales', 'sales.id', '=', 'returns.sale_id')
            ->whereBetween('returns.created_at', [$asOf, $to])
            ->where('returns.status', '!=', 'rejected')
            ->whereIn(DB::raw('COALESCE(returns.refund_method, sales.payment_method)'), ['bank_transfer', 'qris', 'e_wallet'])
            ->groupBy('returns.id', 'returns.return_number', 'returns.created_at', 'returns.sale_id', 'returns.refund_method', 'sales.payment_method')
            ->orderBy('returns.created_at')
            ->select([
                'returns.id',
                'returns.return_number',
                'returns.created_at',
                'returns.sale_id',
                DB::raw('COALESCE(returns.refund_method, sales.payment_method) as payment_method'),
                DB::raw('SUM(return_items.subtotal)::numeric as amount'),
            ])
            ->get()
            ->map(fn ($r) => [
                'id'             => (string) $r->id,
                'return_number'  => $r->return_number,
                'invoice'        => $r->sale_id ? 'SALE-'.str_pad((string) $r->sale_id, 6, '0', STR_PAD_LEFT) : '-',
                'created_at'     => $r->created_at,
                'amount'         => (float) $r->amount,
                'payment_method' => $r->payment_method,
            ]);

        // ── PERSEDIAAN — list batch dengan nilai stok ────────────────────
        $inventoryBatches = Batch::with('product')
            ->where('stock_quantity', '>', 0)
            ->get()
            ->map(fn (Batch $b) => [
                'id'           => (string) $b->id,
                'product_name' => $b->product?->name ?? '-',
                'lot_number'   => $b->lot_number,
                'cukai_year'   => $b->cukai_year,
                'is_promo'     => (bool) $b->is_promo,
                'cost_price'   => (float) $b->cost_price,
                'stock'        => (int) $b->stock_quantity,
                'value'        => (float) $b->cost_price * (int) $b->stock_quantity,
            ])
            ->sortByDesc('value')
            ->values();

        // ── DISKON — total diskon transaksi & per-item dalam periode ─────
        $discountData = DB::table('sales')
            ->leftJoin('sale_items', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$from, $to])
            ->whereIn('sales.status', self::COMPLETED_STATUSES)
            ->groupBy('sales.id', 'sales.created_at', 'sales.discount_amount', 'sales.discount_code', 'sales.discount_label', 'sales.total_amount')
            ->orderBy('sales.created_at')
            ->select([
                'sales.id',
                'sales.created_at',
                'sales.discount_amount',
                'sales.discount_code',
                'sales.discount_label',
                'sales.total_amount',
                DB::raw('COALESCE(SUM(sale_items.discount), 0)::numeric as manual_discount'),
                DB::raw('COALESCE(SUM(sale_items.promo_discount), 0)::numeric as promo_discount'),
            ])
            ->get();

        $discounts = $discountData->map(fn ($s) => [
            'id'              => (int) $s->id,
            'invoice'         => 'SALE-'.str_pad((string) $s->id, 6, '0', STR_PAD_LEFT),
            'created_at'      => $s->created_at,
            'txn_discount'    => (float) $s->discount_amount,
            'manual_discount' => (float) $s->manual_discount,
            'promo_discount'  => (float) $s->promo_discount,
            'discount_code'   => $s->discount_code,
            'discount_label'  => $s->discount_label,
            'total_amount'    => (float) $s->total_amount,
        ])->filter(fn ($r) => $r['txn_discount'] > 0 || $r['manual_discount'] > 0 || $r['promo_discount'] > 0)->values();

        // ── LABA — breakdown profit per sale dalam periode ───────────────
        $factorSub = DB::table('sales')
            ->join('sale_items', 'sale_items.sale_id', '=', 'sales.id')
            ->groupBy('sales.id', 'sales.total_amount')
            ->selectRaw('sales.id as sale_id, CASE WHEN SUM(sale_items.total) > 0 THEN sales.total_amount / SUM(sale_items.total) ELSE 1 END as factor');

        $hppSub = DB::table('sale_item_batches')
            ->groupBy('sale_item_id')
            ->selectRaw('
                sale_item_id,
                SUM(unit_cost * (quantity - returned_quantity))::numeric AS hpp,
                SUM(unit_price * returned_quantity)::numeric AS refund
            ');

        $profitRows = DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->leftJoinSub($hppSub, 'hpp', 'hpp.sale_item_id', '=', 'si.id')
            ->leftJoinSub($factorSub, 'sf', 'sf.sale_id', '=', 's.id')
            ->whereBetween('s.created_at', [$from, $to])
            ->whereIn('s.status', self::COMPLETED_STATUSES)
            ->groupBy('s.id', 's.created_at', 's.total_amount', 's.payment_method', 's.status')
            ->orderBy('s.created_at')
            ->selectRaw('
                s.id, s.created_at, s.total_amount, s.payment_method, s.status,
                COALESCE(SUM(si.total * COALESCE(sf.factor, 1) - COALESCE(hpp.refund, 0)), 0) AS net_revenue,
                COALESCE(SUM(COALESCE(hpp.hpp, 0)), 0) AS net_hpp,
                COALESCE(SUM(si.total * COALESCE(sf.factor, 1) - COALESCE(hpp.refund, 0) - COALESCE(hpp.hpp, 0)), 0) AS profit
            ')
            ->get()
            ->map(fn ($r) => [
                'id'             => (int) $r->id,
                'invoice'        => 'SALE-'.str_pad((string) $r->id, 6, '0', STR_PAD_LEFT),
                'created_at'     => $r->created_at,
                'payment_method' => $r->payment_method,
                'status'         => $r->status,
                'total_amount'   => (float) $r->total_amount,
                'net_revenue'    => (float) $r->net_revenue,
                'net_hpp'        => (float) $r->net_hpp,
                'profit'         => (float) $r->profit,
            ]);

        // ── Ringkasan total per section ──────────────────────────────────
        $cashOpening = (float) $opening->cash;
        $bankOpening = (float) $opening->bank;
        $cashInSum = (float) $cashIn->sum('amount');
        $cashOutSum = (float) $cashOut->sum('amount');
        $bankInSum = (float) $bankIn->sum('amount');
        $bankOutSum = (float) $bankOut->sum('amount');

        $retainedAccum = 0.0;
        if ($asOf->lt($from)) {
            $retainedAccum = $this->computeNetProfit($asOf, $from->copy()->subSecond());
        }

        return Inertia::render('admin/NeracaDetail', [
            'from'         => $from->format('Y-m-d'),
            'to'           => $to->format('Y-m-d'),
            'as_of_date'   => $opening->as_of_date?->toDateString(),
            'opening'      => [
                'cash'              => (float) $opening->cash,
                'bank'              => (float) $opening->bank,
                'inventory_value'   => (float) $opening->inventory_value,
                'fixed_assets'      => (float) $opening->fixed_assets,
                'accounts_payable'  => (float) $opening->accounts_payable,
                'other_liabilities' => (float) $opening->other_liabilities,
                'equity'            => (float) $opening->equity,
                'retained_earnings' => (float) $opening->retained_earnings,
                'notes'             => $opening->notes,
            ],
            'cash' => [
                'opening'   => $cashOpening,
                'in_list'   => $cashIn,
                'out_list'  => $cashOut,
                'in_sum'    => $cashInSum,
                'out_sum'   => $cashOutSum,
                'ending'    => $cashOpening + $cashInSum - $cashOutSum,
            ],
            'bank' => [
                'opening'  => $bankOpening,
                'in_list'  => $bankIn,
                'out_list' => $bankOut,
                'in_sum'   => $bankInSum,
                'out_sum'  => $bankOutSum,
                'ending'   => $bankOpening + $bankInSum - $bankOutSum,
            ],
            'inventory' => [
                'batches' => $inventoryBatches,
                'total'   => (float) $inventoryBatches->sum('value'),
            ],
            'discounts'      => $discounts,
            'profit_rows'    => $profitRows,
            'profit_period'  => (float) $profitRows->sum('profit'),
            'retained_accum' => $retainedAccum,
        ]);
    }

    /**
     * Net profit untuk rentang waktu sembarang, pakai formula yang IDENTIK
     * dengan DashboardController dan ReportSaleController:
     *   profit = SUM(si.total × factor_voucher − refund_item − hpp_net)
     * Refund per item = SUM(unit_price × returned_qty) — menangkap over-refund
     * akibat voucher/diskon manual yg tidak ikut dikembalikan saat retur.
     */
    private function computeNetProfit(Carbon $from, Carbon $to): float
    {
        $factorSub = DB::table('sales')
            ->join('sale_items', 'sale_items.sale_id', '=', 'sales.id')
            ->groupBy('sales.id', 'sales.total_amount')
            ->selectRaw('sales.id as sale_id, CASE WHEN SUM(sale_items.total) > 0 THEN sales.total_amount / SUM(sale_items.total) ELSE 1 END as factor');

        $hppSub = DB::table('sale_item_batches')
            ->groupBy('sale_item_id')
            ->selectRaw('
                sale_item_id,
                SUM(unit_cost * (quantity - returned_quantity))::numeric AS hpp,
                SUM(unit_price * returned_quantity)::numeric AS refund
            ');

        return (float) DB::table('sale_items as si')
            ->join('sales as s', 'si.sale_id', '=', 's.id')
            ->leftJoinSub($hppSub, 'hpp', 'hpp.sale_item_id', '=', 'si.id')
            ->leftJoinSub($factorSub, 'sf', 'sf.sale_id', '=', 's.id')
            ->whereBetween('s.created_at', [$from, $to])
            ->whereIn('s.status', self::COMPLETED_STATUSES)
            ->selectRaw('COALESCE(SUM(si.total * COALESCE(sf.factor, 1) - COALESCE(hpp.refund, 0) - COALESCE(hpp.hpp, 0)), 0) AS profit')
            ->value('profit');
    }

    /**
     * Net cashflow per kelompok metode bayar:
     *  in  = SUM(sales.total_amount) untuk metode itu (status completed/partial_return/returned)
     *  out = SUM(return_items.subtotal) dari retur sale yg pakai metode itu (excl. rejected)
     */
    private function computeCashFlow(Carbon $from, Carbon $to, array $methods): float
    {
        $in = (float) DB::table('sales')
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('status', self::COMPLETED_STATUSES)
            ->whereIn('payment_method', $methods)
            ->sum('total_amount');

        // Refund method: prioritaskan returns.refund_method, fallback ke sales.payment_method
        // (untuk retur lama yg belum punya kolom refund_method).
        $out = (float) DB::table('returns')
            ->join('return_items', 'return_items.return_id', '=', 'returns.id')
            ->join('sales', 'sales.id', '=', 'returns.sale_id')
            ->whereBetween('returns.created_at', [$from, $to])
            ->where('returns.status', '!=', 'rejected')
            ->whereIn(DB::raw('COALESCE(returns.refund_method, sales.payment_method)'), $methods)
            ->sum('return_items.subtotal');

        return $in - $out;
    }

    /**
     * Bangun data Neraca (Balance Sheet) "as of" akhir periode laporan.
     *
     * Persamaan Neraca: ASET = KEWAJIBAN + EKUITAS
     *
     * - Kas/Bank "saat ini" = saldo awal + akumulasi cashflow sejak as_of_date sampai akhir periode.
     * - Persediaan = nilai stok live (sum batches.stock × cost_price) — sama dengan totals.inventory_value.
     * - Aset Tetap, Hutang, Modal = konstan dari opening balance.
     * - Laba Ditahan = opening.retained_earnings + profit akumulatif dari as_of_date sampai awal periode.
     * - Laba Periode Berjalan = profit dalam rentang periode yang sedang dilihat.
     */
    private function buildNeraca(Carbon $from, Carbon $to, float $inventoryValueLive): array
    {
        $opening = OpeningBalance::current();
        $asOf = $opening->as_of_date
            ? Carbon::parse($opening->as_of_date)->startOfDay()
            : Carbon::create(2020, 1, 1)->startOfDay();

        $cashMethods = ['cash'];
        $bankMethods = ['bank_transfer', 'qris', 'e_wallet'];

        // Akumulasi cashflow sejak saldo awal sampai akhir periode laporan.
        $cashAccum = $this->computeCashFlow($asOf, $to, $cashMethods);
        $bankAccum = $this->computeCashFlow($asOf, $to, $bankMethods);

        // Cashflow di dalam periode laporan saja (untuk highlight di UI).
        $cashPeriod = $this->computeCashFlow($from, $to, $cashMethods);
        $bankPeriod = $this->computeCashFlow($from, $to, $bankMethods);

        // Laba ditahan = laba dari as_of_date sampai awal periode laporan.
        $retainedAccum = 0.0;
        if ($asOf->lt($from)) {
            $retainedAccum = $this->computeNetProfit($asOf, $from->copy()->subSecond());
        }
        $retainedEarnings = (float) $opening->retained_earnings + $retainedAccum;

        // Laba periode berjalan — formula identik dengan Dashboard/ReportSale.
        $periodProfit = $this->computeNetProfit($from, $to);

        // ASET
        $cash = (float) $opening->cash + $cashAccum;
        $bank = (float) $opening->bank + $bankAccum;
        $fixedAssets = (float) $opening->fixed_assets;
        $totalCurrentAssets = $cash + $bank + $inventoryValueLive;
        $totalAssets = $totalCurrentAssets + $fixedAssets;

        // KEWAJIBAN
        $ap = (float) $opening->accounts_payable;
        $otherLiab = (float) $opening->other_liabilities;
        $totalLiabilities = $ap + $otherLiab;

        // EKUITAS
        $capital = (float) $opening->equity;
        $totalEquity = $capital + $retainedEarnings + $periodProfit;

        $totalLiabEquity = $totalLiabilities + $totalEquity;
        $difference = round($totalAssets - $totalLiabEquity, 2);

        return [
            'as_of_date'   => $opening->as_of_date?->toDateString(),
            'period_start' => $from->toDateString(),
            'period_end'   => $to->toDateString(),
            'opening'      => [
                'cash'              => (float) $opening->cash,
                'bank'              => (float) $opening->bank,
                'inventory_value'   => (float) $opening->inventory_value,
                'fixed_assets'      => $fixedAssets,
                'accounts_payable'  => $ap,
                'other_liabilities' => $otherLiab,
                'equity'            => $capital,
                'retained_earnings' => (float) $opening->retained_earnings,
                'notes'             => $opening->notes,
            ],
            'assets' => [
                'cash'                 => $cash,
                'bank'                 => $bank,
                'inventory_value'      => $inventoryValueLive,
                'total_current_assets' => $totalCurrentAssets,
                'fixed_assets'         => $fixedAssets,
                'total'                => $totalAssets,
            ],
            'liabilities' => [
                'accounts_payable'  => $ap,
                'other_liabilities' => $otherLiab,
                'total'             => $totalLiabilities,
            ],
            'equity' => [
                'capital'            => $capital,
                'retained_earnings'  => $retainedEarnings,
                'period_profit'      => $periodProfit,
                'total'              => $totalEquity,
            ],
            'period_cashflow' => [
                'cash_net' => $cashPeriod,
                'bank_net' => $bankPeriod,
            ],
            'total_liab_equity' => $totalLiabEquity,
            'difference'        => $difference,
            'balanced'          => abs($difference) < 1.0,
        ];
    }
}
