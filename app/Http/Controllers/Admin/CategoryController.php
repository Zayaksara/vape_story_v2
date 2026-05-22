<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
        ]);

        $category = Category::create([
            'name'      => $data['name'],
            'is_active' => true,
        ]);

        return response()->json([
            'id'   => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
        ], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name,'.$category->id],
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => \Illuminate\Support\Str::slug($data['name']),
        ]);

        return response()->json([
            'id'   => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $inUse = Product::where('category_id', $category->id)->exists();
        if ($inUse) {
            return response()->json([
                'message' => 'Kategori masih dipakai oleh produk. Pindahkan produk dulu sebelum menghapus.',
            ], 422);
        }

        $category->delete();

        return response()->json(['ok' => true]);
    }
}
