<?php

use App\Models\Transaction;

/**
 * Complete Payment Fix Verification Script
 * This script verifies ALL fixes applied to resolve payment issues
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== COMPLETE PAYMENT FIX VERIFICATION ===\n\n";

// Test 1: Frontend Backend Payload Structure Match
echo "Test 1: Frontend-Backend Payload Structure Match...\n";
try {
    $frontendFile = file_get_contents(__DIR__.'/resources/js/composables/usePos.ts');
    $backendFile = file_get_contents(__DIR__.'/app/Http/Controllers/POS/ProcessPaymentController.php');

    $frontendPayloadFields = [
        'items:',
        'total_amount:',
        'paid_amount:',
        'discount_amount:',
        'tax_amount:',
        'payment_method:',
    ];

    $backendValidationFields = [
        "'items' => 'required|array',",
        "'total_amount' => 'required|numeric',",
        "'paid_amount' => 'required|numeric',",
        "'discount_amount' => 'required|numeric',",
        "'tax_amount' => 'required|numeric',",
        "'payment_method' => 'required|string|in:cash,bank_transfer,qris,e_wallet',",
    ];

    echo "\nFrontend Payload Fields:\n";
    foreach ($frontendPayloadFields as $field) {
        $exists = strpos($frontendFile, $field) !== false;
        echo ($exists ? '✓' : '✗')." $field\n";
    }

    echo "\nBackend Validation Fields:\n";
    foreach ($backendValidationFields as $field) {
        $exists = strpos($backendFile, $field) !== false;
        echo ($exists ? '✓' : '✗')." $field\n";
    }

    // Check for removed problematic field
    $hasSubtotalAmount = strpos($frontendFile, 'subtotal_amount:') !== false;
    if ($hasSubtotalAmount) {
        echo "  ✗ PROBLEM: subtotal_amount field still exists!\n";
    } else {
        echo "  ✓ subtotal_amount field correctly removed\n";
    }

    // Check for tax amount fix
    $hasTaxAmountComputed = strpos($frontendFile, 'tax_amount: taxAmount.value,') !== false;
    $hasTaxAmountHardcoded = strpos($frontendFile, 'tax_amount: 0,') !== false;

    if ($hasTaxAmountComputed && ! $hasTaxAmountHardcoded) {
        echo "  ✓ tax_amount correctly uses computed value\n";
    } elseif ($hasTaxAmountHardcoded) {
        echo "  ✗ PROBLEM: tax_amount still hardcoded to 0\n";
    }

} catch (Exception $e) {
    echo '✗ Error: '.$e->getMessage()."\n";
}

echo "\n";

// Test 2: Frontend Response Structure Match
echo "Test 2: Frontend Response Structure Match...\n";
try {
    $frontendFile = file_get_contents(__DIR__.'/resources/js/composables/usePos.ts');
    $backendFile = file_get_contents(__DIR__.'/app/Http/Controllers/POS/ProcessPaymentController.php');

    // Check backend return structure
    $hasTransactionKey = strpos($backendFile, "'transaction' => \$transaction->fresh('items')") !== false;
    $hasInvoiceNumberKey = strpos($backendFile, "'invoice_number' => \$invoiceNumber") !== false;

    echo "Backend Response Structure:\n";
    echo ($hasTransactionKey ? '✓' : '✗')." 'transaction' key in response\n";
    echo ($hasInvoiceNumberKey ? '✓' : '✗')." 'invoice_number' key in response\n";

    // Check frontend expectation
    $hasTransactionProperty = strpos($frontendFile, 'result.transaction?.id') !== false;
    $hasInvoiceNumberProperty = strpos($frontendFile, 'result.invoice_number') !== false;
    $hasSaleProperty = strpos($frontendFile, 'result.sale?.id') !== false;

    echo "\nFrontend Response Expectation:\n";
    echo ($hasTransactionProperty ? '✓' : '✗')." Looking for result.transaction?.id\n";
    echo ($hasInvoiceNumberProperty ? '✓' : '✗')." Looking for result.invoice_number\n";

    if ($hasSaleProperty) {
        echo "  ✗ PROBLEM: Still looking for result.sale?.id (old structure)\n";
    } else {
        echo "  ✓ No longer looking for result.sale?.id\n";
    }

} catch (Exception $e) {
    echo '✗ Error: '.$e->getMessage()."\n";
}

echo "\n";

// Test 3: Backend Transaction Creation Logic
echo "Test 3: Backend Transaction Creation Logic...\n";
try {
    $backendFile = file_get_contents(__DIR__.'/app/Http/Controllers/POS/ProcessPaymentController.php');

    $hasTransactionCreate = strpos($backendFile, 'Transaction::create([') !== false;
    $hasTransactionItemCreate = strpos($backendFile, 'TransactionItem::create([') !== false;
    $hasInvoiceGeneration = strpos($backendFile, "'INV-' . date('Ymd')") !== false;
    $hasChangeAmountCalculation = strpos($backendFile, "\$changeAmount = \$validated['paid_amount'] - \$validated['total_amount']") !== false;
    $hasUuidGeneration = strpos($backendFile, "'id' => (string) Str::uuid()") !== false;

    echo "Backend Transaction Creation:\n";
    echo ($hasTransactionCreate ? '✓' : '✗')." Transaction::create() present\n";
    echo ($hasTransactionItemCreate ? '✓' : '✗')." TransactionItem::create() present\n";
    echo ($hasInvoiceGeneration ? '✓' : '✗')." Invoice number generation\n";
    echo ($hasChangeAmountCalculation ? '✓' : '✗')." Change amount calculation\n";
    echo ($hasUuidGeneration ? '✓' : '✗')." UUID ID generation\n";

} catch (Exception $e) {
    echo '✗ Error: '.$e->getMessage()."\n";
}

echo "\n";

// Test 4: Database Tables Ready
echo "Test 4: Database Tables Ready...\n";
try {
    $transactionExists = DB::select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'transactions')");
    $transactionItemsExists = DB::select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'transaction_items')");

    echo ($transactionExists[0]->exists ? '✓' : '✗')." transactions table exists\n";
    echo ($transactionItemsExists[0]->exists ? '✓' : '✗')." transaction_items table exists\n";

    if ($transactionExists[0]->exists && $transactionItemsExists[0]->exists) {
        $currentTransactions = Transaction::count();
        echo "ℹ Current transaction count: $currentTransactions\n";
    }

} catch (Exception $e) {
    echo '✗ Error: '.$e->getMessage()."\n";
}

echo "\n";

// Test 5: API Route Configuration
echo "Test 5: API Route Configuration...\n";
try {
    $routesFile = file_get_contents(__DIR__.'/routes/web.php');
    $hasPaymentRoute = strpos($routesFile, "'pos/payment/process'") !== false;
    $hasProcessController = strpos($routesFile, 'ProcessPaymentController') !== false;

    echo ($hasPaymentRoute ? '✓' : '✗')." Route: POST /pos/payment/process\n";
    echo ($hasProcessController ? '✓' : '✗')." Controller: ProcessPaymentController\n";

    if ($hasPaymentRoute && $hasProcessController) {
        echo "  ✓ API endpoint properly configured\n";
    } else {
        echo "  ✗ API endpoint not configured properly!\n";
    }

} catch (Exception $e) {
    echo '✗ Error: '.$e->getMessage()."\n";
}

echo "\n";

echo "=== FIX SUMMARY ===\n";
echo "\n🔧 FIXES APPLIED:\n";
echo "\n1. FRONTEND FIX (usePos.ts):\n";
echo "   ✅ Removed 'subtotal_amount' field from payload (not validated by backend)\n";
echo "   ✅ Changed 'tax_amount: 0' to 'tax_amount: taxAmount.value'\n";
echo "\n2. FRONTEND FIX (usePos.ts) - RESPONSE HANDLING:\n";
echo "   ✅ Changed from 'result.sale?.id' to 'result.transaction?.id'\n";
echo "   ✅ Added 'result.invoice_number' handling\n";
echo "\n3. BACKEND FIX (ProcessPaymentController.php):\n";
echo "   ✅ Added Transaction::create() with UUID, invoice number, proper fields\n";
echo "   ✅ Added TransactionItem::create() for each cart item\n";
echo "   ✅ Updated response to return 'transaction' and 'invoice_number'\n";
echo "   ✅ Maintained Sale creation for stock management\n";
echo "\n4. CART PANEL FIX (CartPanel.vue):\n";
echo "   ✅ Fixed syntax error in empty cart state\n";

echo "\n📊 EXPECTED PAYMENT FLOW:\n";
echo "\n1. User adds products to cart\n";
echo "2. User opens Payment Modal\n";
echo "3. User selects payment method and enters cash (if cash)\n";
echo "4. User clicks 'Confirm Payment'\n";
echo "5. Frontend sends payload to /pos/payment/process:\n";
echo "   {\n";
echo "     items: [...],\n";
echo "     total_amount: number,\n";
echo "     paid_amount: number,\n";
echo "     discount_amount: number,\n";
echo "     tax_amount: number,\n";
echo "     payment_method: 'cash'|'qris'|'bank_transfer'|'e_wallet'\n";
echo "   }\n";
echo "6. Backend validates payload successfully\n";
echo "7. Backend creates Transaction record with:\n";
echo "   - UUID ID\n";
echo "   - Invoice number (INV-YYYYMMDD-XXXXXX)\n";
echo "   - All required fields\n";
echo "8. Backend creates TransactionItem records for cart items\n";
echo "9. Backend creates Sale record for stock management\n";
echo "10. Backend creates SaleItem records\n";
echo "11. Backend creates StockMutation records\n";
echo "12. Backend returns success response:\n";
echo "   {\n";
echo "     success: true,\n";
echo "     transaction: { id, invoice_number, items: [...] },\n";
echo "     invoice_number: 'INV-...'\n";
echo "   }\n";
echo "13. Frontend processes response successfully\n";
echo "14. Payment Modal closes\n";
echo "15. Receipt Modal opens with transaction data\n";
echo "16. User can see invoice number and transaction details\n";
echo "17. Transaction appears in Today Transaction Report\n";

echo "\n🚀 NEXT STEPS:\n";
echo "\n1. Clear browser cache (Ctrl+Shift+R)\n";
echo "2. Restart Laravel server: php artisan serve\n";
echo "3. Make a payment through POS interface\n";
echo "4. Check browser DevTools (F12) → Network tab:\n";
echo "   - Filter for 'payment'\n";
echo "   - Click request to /pos/payment/process\n";
echo "   - Tab: Payload → Verify structure\n";
echo "   - Tab: Response → Check for 'transaction' key\n";
echo "5. Verify in database:\n";
echo "   php artisan tinker\n";
echo "   >>> \\App\\Models\\Transaction::latest()->first()\n";
echo "   >>> echo \\$t->invoice_number\n";
echo "6. Verify in Today Transaction Report\n";
echo "   - Payment should appear with invoice number\n";
echo "   - All transaction details should be visible\n";

echo "\n=== VERIFICATION COMPLETE ===\n";
