<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'MedSupply Pakistan',
                'contact_person' => 'Ahmed Khan',
                'email' => 'ahmed@medsupply.pk',
                'phone' => '042-35123456',
                'address' => 'Plot 45, Industrial Area, Lahore',
                'city' => 'Lahore',
                'status' => 'active',
            ],
            [
                'name' => 'PharmaDist Karachi',
                'contact_person' => 'Fatima Ali',
                'email' => 'fatima@pharmadist.pk',
                'phone' => '021-34567890',
                'address' => 'Building 12, SITE Area, Karachi',
                'city' => 'Karachi',
                'status' => 'active',
            ],
            [
                'name' => 'HealthCare Distributors',
                'contact_person' => 'Hassan Raza',
                'email' => 'hassan@healthcare.pk',
                'phone' => '051-2345678',
                'address' => 'Sector I-9, Islamabad',
                'city' => 'Islamabad',
                'status' => 'active',
            ],
            [
                'name' => 'Medical Supplies Co.',
                'contact_person' => 'Ayesha Malik',
                'email' => 'ayesha@medicalsupplies.pk',
                'phone' => '042-36789012',
                'address' => 'Ferozepur Road, Lahore',
                'city' => 'Lahore',
                'status' => 'active',
            ],
            [
                'name' => 'Global Pharma Trading',
                'contact_person' => 'Usman Tariq',
                'email' => 'usman@globalpharma.pk',
                'phone' => '021-35678901',
                'address' => 'Clifton Block 5, Karachi',
                'city' => 'Karachi',
                'status' => 'active',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
