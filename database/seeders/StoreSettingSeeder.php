<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        StoreSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Story Vape',
                'address' => null,
                'phone' => null,
                'receipt_header' => 'Story Vape',
                'receipt_footer' => 'Terima kasih telah berbelanja!',
                'show_logo_on_receipt' => true,
            ],
        );
    }
}
