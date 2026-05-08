<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class ProductFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Allow all authenticated users to filter products
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'category' => ['sometimes', 'string', 'max:100'],
            'category_id' => ['sometimes', 'uuid', 'exists:categories,id'],
            'search' => ['sometimes', 'string', 'max:255'],
            'stock_status' => ['sometimes', 'in:tersedia,habis,stok_rendah'],
            'unit' => ['sometimes', 'string', 'max:20'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'category_id.uuid' => 'Format ID kategori tidak valid.',
            'category_id.exists' => 'Kategori tidak ditemukan.',
            'stock_status.in' => 'Status stok tidak valid.',
        ];
    }
}
