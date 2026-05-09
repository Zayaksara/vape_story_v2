<?php

use App\Enums\TransactionStatus;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// Test payment process endpoint
test('payment_process_validates_required_fields', function () {
    $cashier = User::factory()->create(['role' => 'cashier']);

    $response = $this->actingAs($cashier)
        ->postJson('/pos/payment/process', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items', 'total_amount', 'paid_amount', 'payment_method']);
});

test('payment_process_fails_with_empty_items', function () {
    $cashier = User::factory()->create(['role' => 'cashier']);

    $response = $this->actingAs($cashier)
        ->postJson('/pos/payment/process', [
            'items' => [],
            'total_amount' => 50000,
            'paid_amount' => 50000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'payment_method' => 'cash',
        ]);

    $response->assertStatus(422);
});

test('payment_process_succeeds_with_valid_data', function () {
    $cashier = User::factory()->create(['role' => 'cashier']);

    // Create category first (required for foreign key)
    $category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
    ]);

    // Create product with UUID
    $productId = (string) Str::uuid();
    $batchId = (string) Str::uuid();

    $product = Product::create([
        'id' => $productId,
        'code' => 'TEST-001',
        'name' => 'Test Product',
        'category_id' => $category->id,
        'base_price' => 50000,
    ]);

    $batch = Batch::create([
        'id' => $batchId,
        'product_id' => $product->id,
        'stock_quantity' => 10,
        'expired_date' => now()->addYear(),
    ]);

    $response = $this->actingAs($cashier)
        ->postJson('/pos/payment/process', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 50000,
                    'discount' => 0,
                    'total' => 100000,
                ],
            ],
            'total_amount' => 100000,
            'paid_amount' => 100000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'payment_method' => 'cash',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('transaction.invoice_number', fn ($value) => str_starts_with($value, 'INV-'));

    // Verify transaction was created
    $this->assertDatabaseHas('transactions', [
        'cashier_id' => $cashier->id,
        'total_amount' => 100000,
    ]);

    // Verify transaction items were created
    $transaction = Transaction::latest()->first();
    $this->assertDatabaseHas('transaction_items', [
        'transaction_id' => $transaction->id,
        'product_id' => $product->id,
    ]);

    // Verify stock was decremented
    $batch->refresh();
    $this->assertEquals(8, $batch->stock_quantity);
});

test('payment_process_fails_with_insufficient_stock', function () {
    $cashier = User::factory()->create(['role' => 'cashier']);

    // Create category first
    $category = Category::create([
        'name' => 'Test Category 2',
        'slug' => 'test-category-2',
    ]);

    $productId = (string) Str::uuid();
    $batchId = (string) Str::uuid();

    $product = Product::create([
        'id' => $productId,
        'code' => 'LOW-001',
        'name' => 'Low Stock Product',
        'category_id' => $category->id,
        'base_price' => 50000,
    ]);

    Batch::create([
        'id' => $batchId,
        'product_id' => $product->id,
        'stock_quantity' => 2, // Only 2 in stock
        'expired_date' => now()->addYear(),
    ]);

    $response = $this->actingAs($cashier)
        ->postJson('/pos/payment/process', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5, // Request 5
                    'unit_price' => 50000,
                    'discount' => 0,
                    'total' => 250000,
                ],
            ],
            'total_amount' => 250000,
            'paid_amount' => 250000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'payment_method' => 'cash',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', fn ($msg) => str_contains($msg, 'Stok tidak mencukupi'));
});

test('transaction_report_shows_created_transactions', function () {
    $cashier = User::factory()->create(['role' => 'cashier']);
    $transactionId = (string) Str::uuid();
    $batchId = (string) Str::uuid();
    $productId = (string) Str::uuid();
    $itemId = (string) Str::uuid();

    // Create category
    $category = Category::create([
        'name' => 'Test Category 3',
        'slug' => 'test-category-3',
    ]);

    // Create product and batch for transaction item
    Product::create([
        'id' => $productId,
        'code' => 'TEST-002',
        'name' => 'Test Product 2',
        'category_id' => $category->id,
        'base_price' => 100000,
    ]);

    Batch::create([
        'id' => $batchId,
        'product_id' => $productId,
        'stock_quantity' => 10,
        'expired_date' => now()->addYear(),
    ]);

    // Create a transaction directly
    $transaction = Transaction::create([
        'id' => $transactionId,
        'invoice_number' => 'INV-TEST-001',
        'cashier_id' => $cashier->id,
        'subtotal' => 100000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 100000,
        'paid_amount' => 100000,
        'change_amount' => 0,
        'payment_method' => 'cash',
        'status' => TransactionStatus::COMPLETED,
        'created_at' => now(),
    ]);

    TransactionItem::create([
        'id' => $itemId,
        'transaction_id' => $transaction->id,
        'product_id' => $productId,
        'batch_id' => $batchId,
        'quantity' => 1,
        'unit_price' => 100000,
        'discount' => 0,
        'total' => 100000,
    ]);

    $response = $this->actingAs($cashier)
        ->get(route('pos.transactions.today'));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('summary.total_transactions', 1)
            ->where('summary.total_sales', 100000)
        );
});

test('transaction_report_excludes_non_completed_transactions', function () {
    $cashier = User::factory()->create(['role' => 'cashier']);

    Transaction::create([
        'id' => (string) Str::uuid(),
        'invoice_number' => 'INV-SUCCESS',
        'cashier_id' => $cashier->id,
        'subtotal' => 100000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 100000,
        'paid_amount' => 100000,
        'change_amount' => 0,
        'payment_method' => 'cash',
        'status' => TransactionStatus::COMPLETED,
        'created_at' => now(),
    ]);

    Transaction::create([
        'id' => (string) Str::uuid(),
        'invoice_number' => 'INV-VOID',
        'cashier_id' => $cashier->id,
        'subtotal' => 50000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 50000,
        'paid_amount' => 50000,
        'change_amount' => 0,
        'payment_method' => 'cash',
        'status' => TransactionStatus::VOID,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($cashier)
        ->get(route('pos.transactions.today'));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('summary.total_transactions', 1)
        );
});
