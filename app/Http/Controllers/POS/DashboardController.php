<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('POS/dashboard', [
            'products' => Product::with(['category', 'batches'])->active()->get(),
            'categories' => Category::all(),
            'cashier' => Auth::user(),
            'initial_trx_id' => 'TRX-'.date('Ymd').'-'.time(),
        ]);
    }
}
