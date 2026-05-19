<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:brands,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $brand = Brand::create([
            'id'          => (string) Str::uuid(),
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active'   => true,
        ]);

        return response()->json([
            'id'   => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
        ], 201);
    }
}
