<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\MedicineBrand;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class MedicineSeeder extends Seeder
{
    public function run(): void
    {
        $categories = MedicineCategory::all();
        $brands = MedicineBrand::all();
        $units = Unit::all();

        $baseUnit = $units->where('abbreviation', 'pcs')->first();
        $stripUnit = $units->where('abbreviation', 'strip')->first();
        $bottleUnit = $units->where('abbreviation', 'bottle')->first();

        $medicines = [
            // Antibiotics
            [
                'name' => 'Amoxicillin',
                'generic_name' => 'Amoxicillin',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'capsule',
                'strength' => '500mg',
                'base_unit_id' => 1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 50,
                'manage_stock' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Azithromycin',
                'generic_name' => 'Azithromycin',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'tablet',
                'strength' => '500mg',
                'base_unit_id' =>1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 30,
                'manage_stock' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Ciprofloxacin',
                'generic_name' => 'Ciprofloxacin',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'tablet',
                'strength' => '500mg',
                'base_unit_id' => 1,
                'purchase_unit_id' =>  1,
                'dispensing_unit_id' =>  1,
                'reorder_level' => 40,
                'manage_stock' => true,
                'status' => 'active',
            ],

            // Analgesics
            [
                'name' => 'Paracetamol',
                'generic_name' => 'Paracetamol',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'tablet',
                'strength' => '500mg',
                'base_unit_id' =>1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 100,
                'manage_stock' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Ibuprofen',
                'generic_name' => 'Ibuprofen',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'tablet',
                'strength' => '400mg',
                'base_unit_id' =>1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 80,
                'manage_stock' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Diclofenac',
                'generic_name' => 'Diclofenac Sodium',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'tablet',
                'strength' => '50mg',
                'base_unit_id' =>1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 60,
                'manage_stock' => true,
                'status' => 'active',
            ],

            // Antihypertensives
            [
                'name' => 'Amlodipine',
                'generic_name' => 'Amlodipine',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'tablet',
                'strength' => '5mg',
                'base_unit_id' => 1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 50,
                'manage_stock' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Atenolol',
                'generic_name' => 'Atenolol',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'tablet',
                'strength' => '50mg',
                'base_unit_id' => 1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 40,
                'manage_stock' => true,
                'status' => 'active',
            ],

            // Antidiabetics
            [
                'name' => 'Metformin',
                'generic_name' => 'Metformin HCl',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'tablet',
                'strength' => '500mg',
                'base_unit_id' => 1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 70,
                'manage_stock' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Glimepiride',
                'generic_name' => 'Glimepiride',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'tablet',
                'strength' => '2mg',
                'base_unit_id' => 1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 50,
                'manage_stock' => true,
                'status' => 'active',
            ],

            // Gastrointestinal
            [
                'name' => 'Omeprazole',
                'generic_name' => 'Omeprazole',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'capsule',
                'strength' => '20mg',
                'base_unit_id' => 1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 60,
                'manage_stock' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Ranitidine',
                'generic_name' => 'Ranitidine',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'tablet',
                'strength' => '150mg',
                'base_unit_id' => 1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 50,
                'manage_stock' => true,
                'status' => 'active',
            ],

            // Respiratory
            [
                'name' => 'Salbutamol',
                'generic_name' => 'Salbutamol',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'inhaler',
                'strength' => '100mcg',
                'base_unit_id' => 1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 20,
                'manage_stock' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Montelukast',
                'generic_name' => 'Montelukast',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'tablet',
                'strength' => '10mg',
                'base_unit_id' => 1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 30,
                'manage_stock' => true,
                'status' => 'active',
            ],

            // Antihistamines
            [
                'name' => 'Cetirizine',
                'generic_name' => 'Cetirizine',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'tablet',
                'strength' => '10mg',
                'base_unit_id' => 1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 40,
                'manage_stock' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Loratadine',
                'generic_name' => 'Loratadine',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'tablet',
                'strength' => '10mg',
                'base_unit_id' => 1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 35,
                'manage_stock' => true,
                'status' => 'active',
            ],

            // Vitamins
            [
                'name' => 'Multivitamin',
                'generic_name' => 'Multivitamin Complex',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'tablet',
                'strength' => null,
                'base_unit_id' => 1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 50,
                'manage_stock' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Vitamin D3',
                'generic_name' => 'Cholecalciferol',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'capsule',
                'strength' => '50000 IU',
                'base_unit_id' => 1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 30,
                'manage_stock' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Calcium',
                'generic_name' => 'Calcium Carbonate',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'tablet',
                'strength' => '500mg',
                'base_unit_id' => 1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 40,
                'manage_stock' => true,
                'status' => 'active',
            ],

            // Syrups
            [
                'name' => 'Cough Syrup',
                'generic_name' => 'Dextromethorphan',
                'brand_id' => 1,
                'category_id' => 1,
                'dosage_form' => 'syrup',
                'strength' => '120ml',
                'base_unit_id' => 1,
                'purchase_unit_id' => 1,
                'dispensing_unit_id' => 1,
                'reorder_level' => 25,
                'manage_stock' => true,
                'status' => 'active',
            ],
        ];

        foreach ($medicines as $medicine) {
            Medicine::create($medicine);
        }
    }
}
