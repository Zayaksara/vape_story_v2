<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('index returns units list derived from size_ml and battery_mah columns', function () {
    // Create a category for products
    $category = Category::create([
        'id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        'name' => 'E-Liquid',
        'slug' => 'e-liquid',
    ]);

    // Create products with different size_ml values
    Product::create([
        'id' => 'p1',
        'code' => 'LIQ-60',
        'name' => '60ml E-Liquid',
        'category_id' => $category->id,
        'size_ml' => 60,
        'is_active' => true,
    ]);
    Product::create([
        'id' => 'p2',
        'code' => 'LIQ-120',
        'name' => '120ml E-Liquid',
        'category_id' => $category->id,
        'size_ml' => 120,
        'is_active' => true,
    ]);

    // Create products with battery_mah values
    Product::create([
        'id' => 'p3',
        'code' => 'DEV-1000',
        'name' => '1000mAh Device',
        'category_id' => $category->id,
        'battery_mah' => 1000,
        'is_active' => true,
    ]);
    Product::create([
        'id' => 'p4',
        'code' => 'DEV-2000',
        'name' => '2000mAh Device',
        'category_id' => $category->id,
        'battery_mah' => 2000,
        'is_active' => true,
    ]);

    $response = $this->get(route('pos.products.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('units', ['1000mAh', '120ml', '2000mAh', '60ml'])
    );
});

test('index filters products by unit using size_ml', function () {
    $category = Category::create([
        'id' => 'cat1',
        'name' => 'Test Category',
        'slug' => 'test-category',
    ]);

    $p1 = Product::create([
        'id' => 'p1',
        'code' => 'LIQ-60',
        'name' => '60ml E-Liquid',
        'category_id' => $category->id,
        'size_ml' => 60,
        'is_active' => true,
    ]);
    Product::create([
        'id' => 'p2',
        'code' => 'LIQ-120',
        'name' => '120ml E-Liquid',
        'category_id' => $category->id,
        'size_ml' => 120,
        'is_active' => true,
    ]);

    $response = $this->get(route('pos.products.index', ['unit' => '60ml']));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('products.data', 1)
        ->where('products.data.0.id', $p1->id)
        ->where('products.data.0.volume', '60ml')
    );
});

test('index filters products by unit using battery_mah', function () {
    $category = Category::create([
        'id' => 'cat1',
        'name' => 'Test Category',
        'slug' => 'test-category',
    ]);

    $p1 = Product::create([
        'id' => 'p1',
        'code' => 'DEV-1000',
        'name' => '1000mAh Device',
        'category_id' => $category->id,
        'battery_mah' => 1000,
        'is_active' => true,
    ]);
    Product::create([
        'id' => 'p2',
        'code' => 'DEV-2000',
        'name' => '2000mAh Device',
        'category_id' => $category->id,
        'battery_mah' => 2000,
        'is_active' => true,
    ]);

    $response = $this->get(route('pos.products.index', ['unit' => '1000mAh']));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('products.data', 1)
        ->where('products.data.0.id', $p1->id)
        ->where('products.data.0.volume', '1000mAh')
    );
});

test('index handles decimal size_ml values in units list', function () {
    $category = Category::create([
        'id' => 'cat1',
        'name' => 'Test Category',
        'slug' => 'test-category',
    ]);

    Product::create([
        'id' => 'p1',
        'code' => 'LIQ-10.5',
        'name' => '10.5ml E-Liquid',
        'category_id' => $category->id,
        'size_ml' => 10.5,
        'is_active' => true,
    ]);
    Product::create([
        'id' => 'p2',
        'code' => 'LIQ-30',
        'name' => '30ml E-Liquid',
        'category_id' => $category->id,
        'size_ml' => 30,
        'is_active' => true,
    ]);

    $response = $this->get(route('pos.products.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('units', ['10.5ml', '30ml'])
    );
});

test('index excludes products with zero or null size_ml/battery_mah from units list', function () {
    $category = Category::create([
        'id' => 'cat1',
        'name' => 'Test Category',
        'slug' => 'test-category',
    ]);

    Product::create([
        'id' => 'p1',
        'code' => 'LIQ-0',
        'name' => 'Zero ml',
        'category_id' => $category->id,
        'size_ml' => 0,
        'is_active' => true,
    ]);
    Product::create([
        'id' => 'p2',
        'code' => 'LIQ-NULL',
        'name' => 'Null size',
        'category_id' => $category->id,
        'size_ml' => null,
        'is_active' => true,
    ]);

    $response = $this->get(route('pos.products.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('units', [])
    );
});
