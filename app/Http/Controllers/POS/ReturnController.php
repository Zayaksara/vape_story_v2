<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\ProductReturn;
use App\Models\Sale;
use App\Services\ReturnService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReturnController extends Controller
{
    public function __construct(protected ReturnService $returnService) {}

    public function index(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : now();

        $sales = Sale::with(['items.product', 'user', 'productReturns'])
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        $eligibleSales = $sales->map(function (Sale $sale) {
            return [
                'id' => (int) $sale->id,
                'invoice_number' => 'SALE-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
                'status' => $sale->status,
                'total_amount' => (float) $sale->total_amount,
                'payment_method' => $sale->payment_method,
                'created_at' => $sale->created_at?->toISOString(),
                'has_return' => $sale->productReturns->isNotEmpty(),
                'cashier' => $sale->user ? [
                    'id' => (string) $sale->user->id,
                    'name' => $sale->user->name,
                ] : null,
                'items' => $sale->items->map(fn ($it) => [
                    'id' => (int) $it->id,
                    'product_id' => (string) $it->product_id,
                    'product_name' => $it->product?->name ?? '-',
                    'product_code' => $it->product?->code ?? null,
                    'quantity' => (int) $it->quantity,
                    'unit_price' => (float) $it->unit_price,
                    'total' => (float) $it->total,
                ])->values()->all(),
            ];
        })->values();

        $returns = ProductReturn::with(['sale', 'cashier', 'returnItems'])
            ->whereNotNull('sale_id')
            ->latest()
            ->limit(50)
            ->get()
            ->map(function (ProductReturn $r) {
                return [
                    'id' => (string) $r->id,
                    'return_number' => $r->return_number,
                    'sale_id' => $r->sale_id,
                    'invoice_number' => $r->sale_id
                        ? 'SALE-'.str_pad((string) $r->sale_id, 6, '0', STR_PAD_LEFT)
                        : '-',
                    'reason' => $r->reason,
                    'notes' => $r->notes,
                    'status' => $r->status?->value ?? (string) $r->status,
                    'created_at' => $r->created_at?->toISOString(),
                    'cashier_name' => $r->cashier?->name ?? '-',
                    'items' => $r->returnItems->map(fn ($it) => [
                        'id' => (string) $it->id,
                        'product_name' => $it->product_name,
                        'quantity' => (int) $it->quantity,
                        'unit_price' => (float) $it->unit_price,
                        'subtotal' => (float) $it->subtotal,
                    ])->values()->all(),
                    'total' => (float) $r->returnItems->sum('subtotal'),
                ];
            });

        $viewer = $request->user();

        return Inertia::render('POS/ReturnTransaction', [
            'sales' => $eligibleSales,
            'returns' => $returns,
            'selectedDate' => $date->format('Y-m-d'),
            'today' => now()->format('Y-m-d'),
            'cashier' => $viewer ? [
                'id' => (string) $viewer->id,
                'name' => $viewer->name,
                'email' => $viewer->email,
            ] : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sale_id' => 'required|integer|exists:sales,id',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|integer|exists:sale_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $sale = Sale::findOrFail($data['sale_id']);
            $this->returnService->processSaleReturn($sale, Auth::user(), $data);
        } catch (\Throwable $e) {
            return back()->withErrors(['return' => $e->getMessage()]);
        }

        return redirect()->route('pos.returns.index')
            ->with('success', 'Return berhasil diproses. Stok sudah dikembalikan.');
    }
}
