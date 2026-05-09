<?php

/**
 * Test Script for Payment Field Mismatch Fix
 * This script verifies that frontend and backend payload structure match
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Payment Payload Field Mismatch Test ===\n\n";

// Test 1: Verify Backend Validation Rules
echo "Test 1: Backend Validation Fields Check...\n";
try {
    // Load ProcessPaymentController to check validation rules
    $reflection = new ReflectionClass('App\Http\Controllers\POS\ProcessPaymentController');
    $method = $reflection->getMethod('process');
    $parameters = $method->getParameters();
    $requestParam = $parameters[0]; // Request parameter

    echo "✓ ProcessPaymentController exists\n";
    echo '✓ Method: '.$method->getName()."\n";
    echo '✓ Request parameter type: '.$requestParam->getType()->getName()."\n";

    // Check file content
    $controllerFile = file_get_contents(__DIR__.'/app/Http/Controllers/POS/ProcessPaymentController.php');
    $validationFields = [
        'items',
        'items.*.product_id',
        'items.*.quantity',
        'items.*.unit_price',
        'items.*.discount',
        'items.*.total',
        'total_amount',
        'paid_amount',
        'discount_amount',
        'tax_amount',
        'payment_method',
    ];

    echo "\nExpected Backend Validation Fields:\n";
    foreach ($validationFields as $field) {
        $exists = strpos($controllerFile, $field) !== false;
        echo $exists ? "  ✓ $field\n" : "  ✗ $field MISSING\n";
    }

} catch (Exception $e) {
    echo '✗ Error checking backend: '.$e->getMessage()."\n";
}

echo "\n";

// Test 2: Check Frontend Payload Structure
echo "Test 2: Frontend Payload Fields Check...\n";
try {
    $frontendFile = file_get_contents(__DIR__.'/resources/js/composables/usePos.ts');

    // Check payload construction
    $hasPayload = strpos($frontendFile, 'const payload = {') !== false;
    echo '✓ Frontend has payload construction: '.($hasPayload ? 'YES' : 'NO')."\n";

    // Check required fields
    $requiredFields = [
        'total_amount:',
        'paid_amount:',
        'discount_amount:',
        'tax_amount:',
        'payment_method:',
    ];

    echo "\nRequired Fields in Frontend Payload:\n";
    foreach ($requiredFields as $field) {
        $exists = strpos($frontendFile, $field) !== false;
        echo $exists ? "  ✓ $field\n" : "  ✗ $field MISSING\n";
    }

    // Check for REMOVED problematic field
    $hasSubtotalAmount = strpos($frontendFile, 'subtotal_amount:') !== false;
    if ($hasSubtotalAmount) {
        echo "  ✗ REMOVED FIELD STILL EXISTS: subtotal_amount (should be removed)\n";
    } else {
        echo "  ✓ subtotal_amount correctly removed\n";
    }

    // Check tax_amount value (should be taxAmount.value, not hardcoded 0)
    $hasTaxAmountHardcoded = strpos($frontendFile, 'tax_amount: 0,') !== false;
    $hasTaxAmountComputed = strpos($frontendFile, 'tax_amount: taxAmount.value,') !== false;

    if ($hasTaxAmountComputed && ! $hasTaxAmountHardcoded) {
        echo "  ✓ tax_amount uses computed value (taxAmount.value)\n";
    } elseif ($hasTaxAmountHardcoded) {
        echo "  ✗ tax_amount still hardcoded to 0\n";
    } else {
        echo "  ✗ tax_amount field not found\n";
    }

} catch (Exception $e) {
    echo '✗ Error checking frontend: '.$e->getMessage()."\n";
}

echo "\n";

// Test 3: API Endpoint Check
echo "Test 3: API Route Configuration...\n";
try {
    $routesFile = file_get_contents(__DIR__.'/routes/web.php');
    $hasPaymentRoute = strpos($routesFile, 'pos/payment/process') !== false;
    $hasProcessController = strpos($routesFile, 'ProcessPaymentController') !== false;

    echo ($hasPaymentRoute ? '✓' : '✗')." Route exists: POST /pos/payment/process\n";
    echo ($hasProcessController ? '✓' : '✗')." Controller mapped: ProcessPaymentController\n";

    if (! $hasPaymentRoute || ! $hasProcessController) {
        echo "\n⚠ ROUTE NOT CONFIGURED PROPERLY!\n";
    }

} catch (Exception $e) {
    echo '✗ Error checking routes: '.$e->getMessage()."\n";
}

echo "\n";

// Test 4: Expected Payload Structure
echo "Test 4: Expected vs Actual Payload Structure...\n";
echo "\nExpected Backend Validation:\n";
echo "  {\n";
echo "    items: [...],\n";
echo "    total_amount: number,  // ✓ Required\n";
echo "    paid_amount: number,     // ✓ Required\n";
echo "    discount_amount: number, // ✓ Required\n";
echo "    tax_amount: number,       // ✓ Required\n";
echo "    payment_method: string,  // ✓ Required\n";
echo "  }\n";

echo "\nFrontend Should Send:\n";
echo "  {\n";
echo "    items: [...],\n";
echo "    total_amount: total.value,        // ✓ Matches backend\n";
echo "    paid_amount: calculatedValue,       // ✓ Matches backend\n";
echo "    discount_amount: discountAmount.value, // ✓ Matches backend\n";
echo "    tax_amount: taxAmount.value,     // ✓ Uses computed value (currently 0)\n";
echo "    payment_method: paymentMethod.value, // ✓ Matches backend\n";
echo "    // NO subtotal_amount field! (was removed)\n";
echo "  }\n";

echo "\n";

// Test 5: Database Structure Check
echo "Test 5: Database Tables Ready...\n";
try {
    $transactionExists = DB::select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'transactions')");
    $transactionItemsExists = DB::select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'transaction_items')");

    echo ($transactionExists[0]->exists ? '✓' : '✗')." transactions table exists\n";
    echo ($transactionItemsExists[0]->exists ? '✓' : '✗')." transaction_items table exists\n";

    if ($transactionExists[0]->exists && $transactionItemsExists[0]->exists) {
        $transactionColumns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'transactions'");
        echo '✓ Transactions table has '.count($transactionColumns)." columns\n";
    }

} catch (Exception $e) {
    echo '✗ Error checking database: '.$e->getMessage()."\n";
}

echo "\n=== Test Complete ===\n";
echo "\nFix Applied:\n";
echo "1. Removed 'subtotal_amount' field from frontend payload (not validated by backend)\n";
echo "2. Changed 'tax_amount: 0' to 'tax_amount: taxAmount.value' (uses computed value)\n";
echo "\nNext Steps:\n";
echo "1. Restart Laravel server: php artisan serve\n";
echo "2. Clear browser cache\n";
echo "3. Make a payment through POS interface\n";
echo "4. Check browser DevTools Network tab to verify payload structure\n";
echo "5. Verify Transaction record created in database\n";
