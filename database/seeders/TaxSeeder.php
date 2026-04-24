<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        // GST — applies to all bill types
        $gst = Tax::firstOrCreate(
            ['code' => 'GST'],
            [
                'name' => 'General Sales Tax',
                'percentage' => 17.00,
                'is_inclusive' => false,
                'is_active' => false, // disabled by default — admin enables when ready
                'description' => 'Standard GST rate applicable in Pakistan',
            ]
        );

        $gst->mappings()->firstOrCreate([
            'applicable_on' => 'all',
            'applicable_value' => 'all',
        ]);
    }
}
