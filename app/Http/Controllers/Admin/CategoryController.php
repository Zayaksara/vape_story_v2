<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $category = Category::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active'   => true,
        ]);

        return response()->json([
            'id'   => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
        ], 201);
    }
}
