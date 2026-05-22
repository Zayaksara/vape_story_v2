<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:brands,name'],
        ]);

        $brand = Brand::create([
            'id'        => (string) Str::uuid(),
            'name'      => $data['name'],
            'is_active' => true,
        ]);

        return response()->json([
            'id'   => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
        ], 201);
    }

    public function update(Request $request, Brand $brand): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:brands,name,'.$brand->id],
        ]);

        $brand->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
        ]);

        return response()->json([
            'id'   => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
        ]);
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $inUse = Product::where('brand_id', $brand->id)->exists();
        if ($inUse) {
            return response()->json([
                'message' => 'Brand masih dipakai oleh produk. Pindahkan produk dulu sebelum menghapus.',
            ], 422);
        }

        $brand->delete();

        return response()->json(['ok' => true]);
    }
}
