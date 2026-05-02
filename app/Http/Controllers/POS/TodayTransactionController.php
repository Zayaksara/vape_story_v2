<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TodayTransactionController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date')
            ? \Carbon\Carbon::parse($request->query('date'))
            : now();

        $transactions = Transaction::with(['items.product', 'cashier'])
            ->whereDate('created_at', $date)
            ->where('status', 'success')
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = [
            'total_transactions' => $transactions->count(),
            'total_sales' => $transactions->sum('total_amount'),
            'total_items' => $transactions->sum(function ($t) {
                return $t->items->sum('quantity');
            }),
            'payment_methods' => [
                'cash' => $transactions->where('payment_method', 'cash')->count(),
                'debit' => $transactions->where('payment_method', 'debit')->count(),
                'qris' => $transactions->where('payment_method', 'qris')->count(),
                'ewallet' => $transactions->where('payment_method', 'ewallet')->count(),
            ],
        ];

        return Inertia::render('POS/ReportTodayTransaction', [
            'transactions' => $transactions,
            'summary' => $summary,
            'selectedDate' => $date->format('Y-m-d'),
            'today' => now()->format('Y-m-d'),
        ]);
    }

    public function getData(Request $request)
    {
        $date = $request->query('date')
            ? \Carbon\Carbon::parse($request->query('date'))
            : now();

        $transactions = Transaction::with(['items.product', 'cashier'])
            ->whereDate('created_at', $date)
            ->where('status', 'success')
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = [
            'total_transactions' => $transactions->count(),
            'total_sales' => $transactions->sum('total_amount'),
            'total_items' => $transactions->sum(function ($t) {
                return $t->items->sum('quantity');
            }),
            'payment_methods' => [
                'cash' => $transactions->where('payment_method', 'cash')->count(),
                'debit' => $transactions->where('payment_method', 'debit')->count(),
                'qris' => $transactions->where('payment_method', 'qris')->count(),
                'ewallet' => $transactions->where('payment_method', 'ewallet')->count(),
            ],
        ];

        return response()->json([
            'transactions' => $transactions,
            'summary' => $summary,
        ]);
    }
}
