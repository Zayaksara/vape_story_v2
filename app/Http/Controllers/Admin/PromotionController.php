<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function index(): Response
    {
        $promos = Promotion::with('products:id')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Promotion $p) => [
                'id'           => $p->id,
                'code'         => $p->code,
                'name'         => $p->name,
                'description'  => $p->description,
                'type'         => $p->type,
                'value'        => (float) $p->value,
                'min_purchase' => (float) $p->min_purchase,
                'max_discount' => $p->max_discount !== null ? (float) $p->max_discount : null,
                'usage_limit'  => $p->usage_limit,
                'used_count'   => (int) $p->used_count,
                'start_date'   => $p->start_date?->format('Y-m-d'),
                'end_date'     => $p->end_date?->format('Y-m-d'),
                'is_active'    => (bool) $p->is_active,
                'target'       => $p->target,
                'product_ids'  => $p->products->pluck('id')->all(),
            ]);

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Product $p) => [
                'id'   => $p->id,
                'name' => $p->name,
                'sku'  => $p->code,
            ]);

        return Inertia::render('admin/ManajemenPromo', [
            'promos'   => $promos,
            'products' => $products,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);

        $promo = Promotion::create($this->extractAttributes($data));
        $promo->products()->sync($data['target'] === 'specific' ? ($data['product_ids'] ?? []) : []);

        return back()->with('success', 'Promo berhasil dibuat.');
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $data = $this->validatePayload($request, $promotion->id);

        $promotion->update($this->extractAttributes($data));
        $promotion->products()->sync($data['target'] === 'specific' ? ($data['product_ids'] ?? []) : []);

        return back()->with('success', 'Promo berhasil diperbarui.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();

        return back()->with('success', 'Promo berhasil dihapus.');
    }

    public function toggle(Promotion $promotion): RedirectResponse
    {
        $promotion->update(['is_active' => ! $promotion->is_active]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code'         => ['required', 'string', 'max:50', Rule::unique('promotions', 'code')->ignore($ignoreId)],
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'type'         => ['required', Rule::in(['percentage', 'fixed', 'bogo'])],
            'value'        => ['required', 'numeric', 'min:0'],
            'min_purchase' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit'  => ['nullable', 'integer', 'min:0'],
            'start_date'   => ['required', 'date'],
            'end_date'     => ['required', 'date', 'after_or_equal:start_date'],
            'is_active'    => ['required', 'boolean'],
            'target'       => ['required', Rule::in(['all', 'specific'])],
            'product_ids'  => ['nullable', 'array'],
            'product_ids.*' => ['string', 'exists:products,id'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function extractAttributes(array $data): array
    {
        return [
            'code'         => strtoupper($data['code']),
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'type'         => $data['type'],
            'value'        => $data['value'],
            'min_purchase' => $data['min_purchase'] ?? 0,
            'max_discount' => $data['max_discount'] ?? null,
            'usage_limit'  => $data['usage_limit'] ?? null,
            'start_date'   => $data['start_date'],
            'end_date'     => $data['end_date'],
            'is_active'    => (bool) $data['is_active'],
            'target'       => $data['target'],
        ];
    }
}
