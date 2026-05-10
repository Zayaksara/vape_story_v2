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
        Route::get('dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard.index');
        Route::get('products', [App\Http\Controllers\Admin\ProductController::class, 'index'])->name('products.index');
        Route::get('products/create', [App\Http\Controllers\Admin\ProductController::class, 'create'])->name('products.create');
        Route::post('products', [App\Http\Controllers\Admin\ProductController::class, 'store'])->name('products.store');
        Route::get('products/{product}/edit', [App\Http\Controllers\Admin\ProductController::class, 'edit'])->name('products.edit');
        Route::put('products/{product}', [App\Http\Controllers\Admin\ProductController::class, 'update'])->name('products.update');
        Route::delete('products/{product}', [App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('categories', [App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('categories.store');
        Route::post('products/{product}/batches', [App\Http\Controllers\Admin\BatchController::class, 'store'])->name('products.batches.store');
        Route::put('products/{product}/batches/{batch}', [App\Http\Controllers\Admin\BatchController::class, 'update'])->name('products.batches.update');
        Route::delete('products/{product}/batches/{batch}', [App\Http\Controllers\Admin\BatchController::class, 'destroy'])->name('products.batches.destroy');
        Route::get('transactions/today',[App\Http\Controllers\Admin\TodayTransactionController::class, 'index'])->name('transactions.today');

        Route::get('reports/sales',        [App\Http\Controllers\Admin\ReportSaleController::class, 'index'])->name('reports.sales');
        Route::get('reports/sales/export', [App\Http\Controllers\Admin\ReportSaleController::class, 'export'])->name('reports.sales.export');
        Route::get('reports/sales/shopping-list', [App\Http\Controllers\Admin\ReportSaleController::class, 'shoppingList'])->name('reports.sales.shopping-list');

        Route::get('users',              [App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
        Route::post('users',             [App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}',       [App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}',    [App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');

        Route::get('promotions',                  [App\Http\Controllers\Admin\PromotionController::class, 'index'])->name('promotions.index');
        Route::post('promotions',                 [App\Http\Controllers\Admin\PromotionController::class, 'store'])->name('promotions.store');
        Route::put('promotions/{promotion}',      [App\Http\Controllers\Admin\PromotionController::class, 'update'])->name('promotions.update');
        Route::delete('promotions/{promotion}',   [App\Http\Controllers\Admin\PromotionController::class, 'destroy'])->name('promotions.destroy');
        Route::patch('promotions/{promotion}/toggle', [App\Http\Controllers\Admin\PromotionController::class, 'toggle'])->name('promotions.toggle');
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
