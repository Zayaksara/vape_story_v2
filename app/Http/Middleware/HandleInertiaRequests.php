<?php

namespace App\Http\Middleware;

use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $store = Schema::hasTable('store_settings') ? StoreSetting::current() : null;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'storeName' => $store?->name ?? config('app.name'),
            'storeLogo' => $store?->logo_url,
            'storeTagline' => $store?->tagline,
            'storeAddress' => $store?->address,
            'storePhone' => $store?->phone,
            'storeReceiptHeader' => $store?->receipt_header,
            'storeReceiptFooter' => $store?->receipt_footer,
            'storeShowLogoOnReceipt' => (bool) ($store?->show_logo_on_receipt ?? false),
            'storeReceiptOptions' => $store?->receipt_options_resolved ?? \App\Models\StoreSetting::DEFAULT_RECEIPT_OPTIONS,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
