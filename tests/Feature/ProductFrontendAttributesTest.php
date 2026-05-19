<?php

use App\Models\Product;
use App\Models\Batch;
use Illuminate\Support\Str;

test('product model has required appended attributes', function () {
    $appends = (new Product)->getAppends();

    // These accessors must be in $appends to be included in JSON for frontend
    expect($appends)->toContain('price');
    expect($appends)->toContain('stock');
    expect($appends)->toContain('sku');
    expect($appends)->toContain('image_url');
    expect($appends)->toContain('volume');
});

test('price accessor returns float from base_price', function () {
    $product = new Product(['base_price' => 150000]);
    expect($product->price)->toBeFloat()->toBe(150000.0);
});

test('sku accessor returns code, empty string if code is null', function () {
    $product = new Product(['code' => 'SKU-123']);
    expect($product->sku)->toBe('SKU-123');

    $product2 = new Product(['code' => null]);
    expect($product2->sku)->toBe('');
});

test('volume accessor returns formatted size_ml or battery_mAh', function () {
    // size_ml integer-like
    $p1 = new Product(['size_ml' => 60]);
    expect($p1->volume)->toBe('60ml');

    // size_ml with decimals
    $p2 = new Product(['size_ml' => 60.5]);
    expect($p2->volume)->toBe('60.5ml');

    // battery_mAh
    $p3 = new Product(['battery_mah' => 2000]);
    expect($p3->volume)->toBe('2000mAh');

    // neither
    $p4 = new Product();
    expect($p4->volume)->toBeNull();
});

test('image_url accessor returns Storage URL when image is set', function () {
    $product = new Product(['image' => 'products/test.png']);
    $url = $product->image_url;
    expect($url)->toContain('/storage/');
    expect($url)->toContain('test.png');
});

test('image_url accessor returns null when image is empty', function () {
    $product = new Product(['image' => null]);
    expect($product->image_url)->toBeNull();

    $product2 = new Product(['image' => '']);
    expect($product2->image_url)->toBeNull();
});

test('stock accessor sums quantities from loaded batches relationship', function () {
    $product = new Product;

    // Mock empty batches relation to avoid DB query
    $product->setRelation('batches', collect([]));
    expect($product->stock)->toBe(0);

    // Mock batches relation with two batch models
    $batch1 = new Batch(['stock_quantity' => 10]);
    $batch2 = new Batch(['stock_quantity' => 20]);
    $product->setRelation('batches', collect([$batch1, $batch2]));

    expect($product->stock)->toBe(30);
});
