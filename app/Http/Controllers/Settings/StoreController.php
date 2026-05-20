<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/Store', [
            'store' => StoreSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'tagline' => 'nullable|string|max:100',
            'receipt_header' => 'nullable|string|max:1000',
            'receipt_footer' => 'nullable|string|max:1000',
            'show_logo_on_receipt' => 'boolean',
            'logo' => 'nullable|image|max:2048',
        ]);

        $store = StoreSetting::current();

        if ($request->hasFile('logo')) {
            if ($store->logo_path) {
                Storage::disk('public')->delete($store->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('store/logo', 'public');
        }

        unset($validated['logo']);
        $store->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pengaturan toko diperbarui.')]);

        return to_route('store.edit');
    }
}
