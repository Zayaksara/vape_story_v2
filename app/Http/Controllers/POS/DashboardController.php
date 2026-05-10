<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $promos = Promotion::query()
            ->where('is_active', true)
            ->whereIn('type', ['percentage', 'fixed'])
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('end_date')
            ->get()
            ->filter(fn (Promotion $p) => $p->usage_limit === null || $p->used_count < $p->usage_limit)
            ->values()
            ->map(fn (Promotion $p) => [
                'code'         => $p->code,
                'label'        => $p->name,
                'type'         => $p->type === 'percentage' ? 'percent' : 'fixed',
                'value'        => (float) $p->value,
                'min_purchase' => (float) $p->min_purchase,
                'max_discount' => $p->max_discount !== null ? (float) $p->max_discount : null,
                'expires_at'   => $p->end_date?->toISOString(),
            ]);

        return Inertia::render('POS/dashboard', [
            'products' => Product::with(['category', 'batches'])->active()->get(),
            'categories' => Category::all(),
            'cashier' => Auth::user(),
            'initial_trx_id' => 'TRX-'.date('Ymd').'-'.time(),
            'promos' => $promos,
        ]);
    }
}
