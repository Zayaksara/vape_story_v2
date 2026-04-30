<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('POS/dashboard', [
            'products' => Product::with(['category', 'batches'])->active()->get(),
            'categories' => Category::all(),
            'cashier' => auth()->user(),
            'initial_trx_id' => 'TRX-'.date('Ymd').'-'.time(),
        ]);
    }
}
