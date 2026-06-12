<?php

use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

function makeAdmin(): User
{
    return User::factory()->create(['role' => UserRole::ADMIN]);
}

function makeProduct(): Product
{
    $category = Category::create(['name' => 'Test '.Str::random(6)]);

    return Product::create([
        'code'        => 'TST-'.strtoupper(Str::random(8)),
        'name'        => 'Produk Uji',
        'category_id' => $category->id,
        'base_price'  => 10000,
    ]);
}

function makeBatch(Product $product, int $stock = 0): Batch
{
    $batch = new Batch();
    $batch->id             = (string) Str::uuid();
    $batch->product_id     = $product->id;
    $batch->lot_number     = 'LOT-'.strtoupper(Str::random(6));
    $batch->stock_quantity = $stock;
    $batch->cost_price     = 5000;
    $batch->save();

    return $batch;
}

function makeSaleFor(Product $product, User $cashier): void
{
    $saleId = DB::table('sales')->insertGetId([
        'user_id'        => $cashier->id,
        'total_amount'   => 10000,
        'paid_amount'    => 10000,
        'payment_method' => 'cash',
        'status'         => 'completed',
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    DB::table('sale_items')->insert([
        'sale_id'    => $saleId,
        'product_id' => $product->id,
        'quantity'   => 1,
        'unit_price' => 10000,
        'total'      => 10000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('product with sales history cannot be deleted', function () {
    $admin = makeAdmin();
    $product = makeProduct();
    makeSaleFor($product, $admin);

    $response = $this
        ->actingAs($admin)
        ->delete(route('admin.products.destroy', $product));

    $response->assertSessionHasErrors('product');
    expect(Product::find($product->id))->not->toBeNull();
    expect(DB::table('sale_items')->where('product_id', $product->id)->exists())->toBeTrue();
});

test('product without sales history can be deleted', function () {
    $admin = makeAdmin();
    $product = makeProduct();

    $response = $this
        ->actingAs($admin)
        ->delete(route('admin.products.destroy', $product));

    $response->assertSessionHasNoErrors();
    expect(Product::find($product->id))->toBeNull();
});

test('batch with remaining stock cannot be deleted', function () {
    $admin = makeAdmin();
    $product = makeProduct();
    $batch = makeBatch($product, stock: 5);

    $response = $this
        ->actingAs($admin)
        ->delete(route('admin.products.batches.destroy', [$product, $batch]));

    $response->assertSessionHasErrors('batch');
    expect(Batch::find($batch->id))->not->toBeNull();
});

test('empty batch without history can be deleted', function () {
    $admin = makeAdmin();
    $product = makeProduct();
    $batch = makeBatch($product, stock: 0);

    $response = $this
        ->actingAs($admin)
        ->delete(route('admin.products.batches.destroy', [$product, $batch]));

    $response->assertSessionHasNoErrors();
    expect(Batch::find($batch->id))->toBeNull();
});

test('batch of another product cannot be deleted via mismatched url', function () {
    $admin = makeAdmin();
    $productA = makeProduct();
    $productB = makeProduct();
    $batchB = makeBatch($productB);

    $response = $this
        ->actingAs($admin)
        ->delete(route('admin.products.batches.destroy', [$productA, $batchB]));

    $response->assertNotFound();
    expect(Batch::find($batchB->id))->not->toBeNull();
});

test('deleting a category soft deletes it', function () {
    $admin = makeAdmin();
    $category = Category::create(['name' => 'Hapus '.Str::random(6)]);

    $response = $this
        ->actingAs($admin)
        ->delete(route('admin.categories.destroy', $category));

    $response->assertOk();
    expect(Category::find($category->id))->toBeNull();
    expect(Category::withTrashed()->find($category->id)->trashed())->toBeTrue();
});
