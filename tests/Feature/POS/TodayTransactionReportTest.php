<?php

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('payment_methods_returns_amounts_not_counts', function () {
    // Create a cashier user
    $cashier = User::factory()->create(['role' => 'cashier']);

    // Create transactions with different payment methods
    Transaction::create([
        'id' => 't1',
        'invoice_number' => 'INV-001',
        'cashier_id' => $cashier->id,
        'subtotal' => 100000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 100000,
        'paid_amount' => 100000,
        'change_amount' => 0,
        'payment_method' => 'cash',
        'status' => 'completed',
        'created_at' => now(),
    ]);

    Transaction::create([
        'id' => 't2',
        'invoice_number' => 'INV-002',
        'cashier_id' => $cashier->id,
        'subtotal' => 50000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 50000,
        'paid_amount' => 50000,
        'change_amount' => 0,
        'payment_method' => 'qris',
        'status' => 'completed',
        'created_at' => now(),
    ]);

    Transaction::create([
        'id' => 't3',
        'invoice_number' => 'INV-003',
        'cashier_id' => $cashier->id,
        'subtotal' => 200000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 200000,
        'paid_amount' => 200000,
        'change_amount' => 0,
        'payment_method' => 'cash',
        'status' => 'completed',
        'created_at' => now(),
    ]);

    // Act as cashier and request the page (Inertia)
    $response = $this->actingAs($cashier)
        ->get(route('pos.transactions.today'));

    $response->assertSuccessful();

    // Assert payment methods return amounts, not counts using Inertia
    $response->assertInertia(fn ($page) => $page
        ->where('summary.payment_methods.cash', 300000) // 100000 + 200000
        ->where('summary.payment_methods.qris', 50000) // 50000
        ->where('summary.payment_methods.bank_transfer', 0)
        ->where('summary.payment_methods.e_wallet', 0)
    );
});

test('summary_calculates_correct_totals', function () {
    // Create a cashier user
    $cashier = User::factory()->create(['role' => 'cashier']);

    // Create 3 transactions of 50000 each
    for ($i = 1; $i <= 3; $i++) {
        Transaction::create([
            'id' => "t{$i}",
            'invoice_number' => "INV-00{$i}",
            'cashier_id' => $cashier->id,
            'subtotal' => 50000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 50000,
            'paid_amount' => 50000,
            'change_amount' => 0,
            'payment_method' => 'cash',
            'status' => 'completed',
            'created_at' => now(),
        ]);
    }

    // Act as cashier and request the page
    $response = $this->actingAs($cashier)
        ->get(route('pos.transactions.today'));

    $response->assertSuccessful();

    // Assert summary calculations are correct using Inertia
    $response->assertInertia(fn ($page) => $page
        ->where('summary.total_transactions', 3)
        ->where('summary.total_sales', 150000) // 3 * 50000
        ->where('summary.payment_methods.cash', 150000) // All 3 transactions
    );
});

test('index_page_loads_with_correct_payment_amounts', function () {
    // Create a cashier user
    $cashier = User::factory()->create(['role' => 'cashier']);

    // Create transactions with different payment methods
    Transaction::create([
        'id' => 't1',
        'invoice_number' => 'INV-001',
        'cashier_id' => $cashier->id,
        'subtotal' => 75000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 75000,
        'paid_amount' => 75000,
        'change_amount' => 0,
        'payment_method' => 'bank_transfer',
        'status' => 'completed',
        'created_at' => now(),
    ]);

    Transaction::create([
        'id' => 't2',
        'invoice_number' => 'INV-002',
        'cashier_id' => $cashier->id,
        'subtotal' => 25000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 25000,
        'paid_amount' => 25000,
        'change_amount' => 0,
        'payment_method' => 'e_wallet',
        'status' => 'completed',
        'created_at' => now(),
    ]);

    // Act as cashier and request the page
    $response = $this->actingAs($cashier)
        ->get(route('pos.transactions.today'));

    $response->assertSuccessful();

    // Assert Inertia props contain correct payment amounts
    $response->assertInertia(fn ($page) => $page
        ->where('summary.payment_methods.bank_transfer', 75000)
        ->where('summary.payment_methods.e_wallet', 25000)
        ->where('summary.payment_methods.cash', 0)
        ->where('summary.payment_methods.qris', 0)
    );
});

test('only_successful_transactions_are_included_in_payment_amounts', function () {
    // Create a cashier user
    $cashier = User::factory()->create(['role' => 'cashier']);

    // Create a successful transaction
    Transaction::create([
        'id' => 't1',
        'invoice_number' => 'INV-001',
        'cashier_id' => $cashier->id,
        'subtotal' => 100000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 100000,
        'paid_amount' => 100000,
        'change_amount' => 0,
        'payment_method' => 'cash',
        'status' => 'completed',
        'created_at' => now(),
    ]);

    // Create a failed transaction with the same payment method
    Transaction::create([
        'id' => 't2',
        'invoice_number' => 'INV-002',
        'cashier_id' => $cashier->id,
        'subtotal' => 50000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 50000,
        'paid_amount' => 50000,
        'change_amount' => 0,
        'payment_method' => 'cash',
        'status' => 'void',
        'created_at' => now(),
    ]);

    // Act as cashier and request the page
    $response = $this->actingAs($cashier)
        ->get(route('pos.transactions.today'));

    $response->assertSuccessful();

    // Assert only successful transaction amount is included using Inertia
    $response->assertInertia(fn ($page) => $page
        ->where('summary.total_transactions', 1)
        ->where('summary.total_sales', 100000)
        ->where('summary.payment_methods.cash', 100000) // Only the successful one
    );
});
