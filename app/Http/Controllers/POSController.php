<?php

namespace App\Http\Controllers;

use App\Enums\MutationType;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMutation;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;    

class POSController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'batches'])
            ->active()
            ->get();

        $categories = Category::withCount('products')
            ->active()
            ->get();

        return Inertia::render('POS/dashboard', [
            'products' => $products,
            'categories' => $categories,
            'cashier' => Auth::user(),
        ]);
    }

    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.batch_id' => 'required|exists:batches,id',
            'items.*.quantity' => 'required|integer|min:1',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'discount_amount' => 'numeric|min:0',
            'tax_amount' => 'numeric|min:0',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber();

            // Create transaction
            $transaction = Transaction::create([
                'invoice_number' => $invoiceNumber,
                'cashier_id' => Auth::id(),
                'subtotal' => $validated['total_amount'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'total_amount' => $validated['total_amount'],
                'paid_amount' => $validated['paid_amount'],
                'change_amount' => $validated['paid_amount'] - $validated['total_amount'],
                'payment_method' => $validated['payment_method'],
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Process each item
            foreach ($validated['items'] as $item) {
                // Create transaction item
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product_id'],
                    'batch_id' => $item['batch_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'total' => $item['total'],
                ]);

                // Decrement batch stock
                $batch = Batch::find($item['batch_id']);
                $batch->decrement('stock_quantity', $item['quantity']);

                // Create stock mutation record
                StockMutation::create([
                    'batch_id' => $item['batch_id'],
                    'mutation_type' => MutationType::SALE,
                    'quantity' => $item['quantity'],
                    'reference_type' => Transaction::class,
                    'reference_id' => $transaction->id,
                    'notes' => 'POS sale - Invoice: '.$invoiceNumber,
                ]);
            }

            return response()->json([
                'success' => true,
                'transaction' => $transaction,
                'message' => 'Payment processed successfully',
            ]);
        });
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = strtoupper('INV-'.date('ymd').'-');
        $random = strtoupper(Str::random(5));

        return $prefix.$random;
    }
}
