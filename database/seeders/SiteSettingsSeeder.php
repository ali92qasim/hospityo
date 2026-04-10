<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'office_address' => '2nd Floor, Software Technology Park',
            'office_city' => 'Lahore',
            'office_country' => 'Pakistan',
            'office_phone' => '+92 42 1234 5678',
            'office_email' => 'info@hospityo.com',
            'office_hours' => 'Mon - Fri: 9:00 AM - 6:00 PM PKT',
            'whatsapp_number' => '+923001234567',
        ];

        foreach ($defaults as $key => $value) {
            SiteSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
