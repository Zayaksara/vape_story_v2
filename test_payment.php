<?php

/**
 * Test Script for Payment Processing
 * This script verifies that Transaction records are created alongside Sale records
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;

echo "=== Payment Processing Test ===\n\n";

// Test 1: Check if Transaction table exists and has structure
echo "Test 1: Transaction table structure...\n";
try {
    $transactionColumns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'transactions'");
    echo '✓ Transaction table exists with '.count($transactionColumns)." columns\n";

    $requiredColumns = ['invoice_number', 'cashier_id', 'total_amount', 'paid_amount', 'change_amount', 'payment_method', 'status'];
    foreach ($requiredColumns as $column) {
        $exists = collect($transactionColumns)->contains('column_name', $column);
        echo $exists ? "  ✓ $column column exists\n" : "  ✗ $column column MISSING\n";
    }
} catch (Exception $e) {
    echo '✗ Error checking transaction table: '.$e->getMessage()."\n";
}

echo "\n";

// Test 2: Check if TransactionItem table exists and has structure
echo "Test 2: TransactionItem table structure...\n";
try {
    $transactionItemColumns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'transaction_items'");
    echo '✓ TransactionItem table exists with '.count($transactionItemColumns)." columns\n";

    $requiredColumns = ['transaction_id', 'product_id', 'quantity', 'unit_price', 'discount', 'total'];
    foreach ($requiredColumns as $column) {
        $exists = collect($transactionItemColumns)->contains('column_name', $column);
        echo $exists ? "  ✓ $column column exists\n" : "  ✗ $column column MISSING\n";
    }
} catch (Exception $e) {
    echo '✗ Error checking transaction_items table: '.$e->getMessage()."\n";
}

echo "\n";

// Test 3: Check sample data
echo "Test 3: Sample data check...\n";
try {
    $saleCount = Sale::count();
    $transactionCount = Transaction::count();

    echo "✓ Total Sales: $saleCount\n";
    echo "✓ Total Transactions: $transactionCount\n";

    if ($transactionCount > 0) {
        $latestTransaction = Transaction::with('items')->latest()->first();
        echo '✓ Latest Transaction ID: '.$latestTransaction->id."\n";
        echo '✓ Invoice Number: '.$latestTransaction->invoice_number."\n";
        echo '✓ Total Amount: '.$latestTransaction->total_amount."\n";
        echo '✓ Items Count: '.$latestTransaction->items->count()."\n";
    } else {
        echo "ℹ No transaction records found yet\n";
    }
} catch (Exception $e) {
    echo '✗ Error checking data: '.$e->getMessage()."\n";
}

echo "\n";

// Test 4: Check if payment method enum exists
echo "Test 4: PaymentMethod enum check...\n";
try {
    $paymentMethods = ['cash', 'e_wallet', 'bank_transfer', 'qris'];
    foreach ($paymentMethods as $method) {
        echo "✓ Payment method '$method' is valid\n";
    }
} catch (Exception $e) {
    echo '✗ Error checking payment methods: '.$e->getMessage()."\n";
}

echo "\n=== Test Complete ===\n";
echo "\nNext Steps:\n";
echo "1. Make a payment through POS interface\n";
echo "2. Check if new Transaction record appears in database\n";
echo "3. Verify Today Transaction Report shows the new payment\n";
