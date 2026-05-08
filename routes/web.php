<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

// Role-based dashboard redirect (used after login)
Route::get('/dashboard', function () {
    $user = Auth::user();
    if ($user && $user->isAdmin()) {
        return redirect()->route('admin.dashboard.index');
    }

    return redirect()->route('pos.dashboard.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Admin routes - admin only access
    Route::prefix('admin')->name('admin.')->middleware(['admin'])->group(function () {
        Route::get('dashboard', fn () => inertia('admin/dashboard'))->name('dashboard.index');
        // Future admin routes: Route::get('users', ...)->name('users.index');
    });

    // POS routes - cashier and admin access
    Route::prefix('pos')->name('pos.')->middleware(['cashier'])->group(function () {
        Route::get('dashboard', 'App\Http\Controllers\POS\DashboardController@index')->name('dashboard.index');
        Route::post('payment/process', 'App\Http\Controllers\POS\ProcessPaymentController@process')->name('payment.process');
        Route::get('products', [App\Http\Controllers\POS\ProductController::class, 'index'])->name('products.index');
        Route::get('transactions/today', [App\Http\Controllers\POS\TodayTransactionController::class, 'index'])->name('transactions.today');
    });
});

require __DIR__.'/settings.php';
