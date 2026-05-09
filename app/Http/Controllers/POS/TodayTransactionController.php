<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Batch;
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
                    $unitCost = (float) ($avgCostByProduct[(string) $item->product_id] ?? 0);
                    $lineTotal = (float) $item->total;
                    $quantity = (int) $item->quantity;
                    $hppTotal = round($unitCost * $quantity, 0);
                    $profit = round($lineTotal - $hppTotal, 0);

                    return [
                        'id' => (string) $item->id,
                        'quantity' => $quantity,
                        'unit_price' => (float) $item->unit_price,
                        'total' => $lineTotal,
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
