<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class TodayTransactionController extends Controller
{
    private const STORE_ADDRESS = 'Jl. Raya Kedawung No.02, Panembahan, Kec. Plered, Kabupaten Cirebon, Jawa Barat 45154';

    private const REPORT_TITLE = 'Laporan Penjualan Harian Vape Story';

    public function index(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : now();

        // Termasuk transaksi yang sebagian/seluruhnya di-return — selaras Dashboard & admin.
        $sales = Sale::with(['items.product', 'items.allocations', 'user', 'productReturns.returnItems'])
            ->whereDate('created_at', $date)
            ->whereIn('status', ['completed', 'partial_return', 'returned'])
            ->orderBy('created_at', 'desc')
            ->get();

        $transactions = $this->mapSalesToTransactions($sales);

        $summary = [
            'total_transactions' => $transactions->count(),
            'total_sales' => $transactions->sum('total_amount'),
            'total_items' => $transactions->sum('net_quantity'),
            'payment_methods' => [
                'cash' => $transactions->where('payment_method', 'cash')->sum('total_amount'),
                'bank_transfer' => $transactions->where('payment_method', 'bank_transfer')->sum('total_amount'),
                'qris' => $transactions->where('payment_method', 'qris')->sum('total_amount'),
                'e_wallet' => $transactions->where('payment_method', 'e_wallet')->sum('total_amount'),
            ],
        ];

        $viewer = $request->user();

        return Inertia::render('POS/ReportTodayTransaction', [
            'transactions' => $transactions,
            'summary' => $summary,
            'selectedDate' => $date->format('Y-m-d'),
            'today' => now()->format('Y-m-d'),
            'report_title' => self::REPORT_TITLE,
            'store_address' => self::STORE_ADDRESS,
            'cashier' => $viewer ? [
                'id' => (string) $viewer->id,
                'name' => $viewer->name,
                'email' => $viewer->email,
            ] : null,
        ]);
    }

    public function getData(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : now();

        // Termasuk transaksi yang sebagian/seluruhnya di-return — selaras Dashboard & admin.
        $sales = Sale::with(['items.product', 'items.allocations', 'user', 'productReturns.returnItems'])
            ->whereDate('created_at', $date)
            ->whereIn('status', ['completed', 'partial_return', 'returned'])
            ->orderBy('created_at', 'desc')
            ->get();

        $transactions = $this->mapSalesToTransactions($sales);

        $summary = [
            'total_transactions' => $transactions->count(),
            'total_sales' => $transactions->sum('total_amount'),
            'total_items' => $transactions->sum('net_quantity'),
            'payment_methods' => [
                'cash' => $transactions->where('payment_method', 'cash')->sum('total_amount'),
                'bank_transfer' => $transactions->where('payment_method', 'bank_transfer')->sum('total_amount'),
                'qris' => $transactions->where('payment_method', 'qris')->sum('total_amount'),
                'e_wallet' => $transactions->where('payment_method', 'e_wallet')->sum('total_amount'),
            ],
        ];

        return response()->json([
            'transactions' => $transactions,
            'summary' => $summary,
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

            // Nilai refund tercatat (return_items.subtotal, sudah dibulatkan) dari retur non-rejected.
            $refundTotal  = (float) $sale->productReturns
                ->filter(fn ($r) => (is_object($r->status) ? $r->status->value : $r->status) !== 'rejected')
                ->sum(fn ($r) => (float) $r->returnItems->sum('subtotal'));
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
                'total_amount' => $netAmount,
                'gross_amount' => round($grossAmount),
                'returned_amount' => round($refundTotal),
                'net_quantity' => $netQuantity,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'discount_code'   => $sale->discount_code,
                'discount_label'  => $sale->discount_label,
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
                    $hppTotal     = round((float) $item->allocations->sum(
                        fn ($a) => (float) $a->unit_cost * ((int) $a->quantity - (int) $a->returned_quantity)
                    ));
                    $refundItem   = round((float) $item->allocations->sum(
                        fn ($a) => (float) $a->unit_price * (int) $a->returned_quantity
                    ));
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
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'code' => $item->product->code,
                        ] : null,
                    ];
                })->values()->all(),
            ];
        })->values();
    }

}
