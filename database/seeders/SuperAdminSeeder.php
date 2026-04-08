<?php

namespace Database\Seeders;

use App\Models\SuperAdmin;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        SuperAdmin::firstOrCreate(
            ['email' => 'superadmin@hospityo.com'],
            [
                'name'     => 'Qasim Ali',
                'password' => bcrypt('password'),
            ]
        );
    }
}
