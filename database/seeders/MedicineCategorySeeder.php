<?php

namespace Database\Seeders;

use App\Models\MedicineCategory;
use Illuminate\Database\Seeder;

class MedicineCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Antibiotics',
                'code' => 'ANTI',
                'description' => 'Medications used to treat bacterial infections',
                'is_active' => true,
            ],
            [
                'name' => 'Analgesics',
                'code' => 'ANAL',
                'description' => 'Pain relief medications',
                'is_active' => true,
            ],
            [
                'name' => 'Antipyretics',
                'code' => 'APYR',
                'description' => 'Fever reducing medications',
                'is_active' => true,
            ],
            [
                'name' => 'Antihypertensives',
                'code' => 'AHYP',
                'description' => 'Blood pressure lowering medications',
                'is_active' => true,
            ],
            [
                'name' => 'Antidiabetics',
                'code' => 'ADIAB',
                'description' => 'Diabetes management medications',
                'is_active' => true,
            ],
            [
                'name' => 'Cardiovascular',
                'code' => 'CARD',
                'description' => 'Heart and blood vessel medications',
                'is_active' => true,
            ],
            [
                'name' => 'Gastrointestinal',
                'code' => 'GI',
                'description' => 'Digestive system medications',
                'is_active' => true,
            ],
            [
                'name' => 'Respiratory',
                'code' => 'RESP',
                'description' => 'Breathing and lung medications',
                'is_active' => true,
            ],
            [
                'name' => 'Antihistamines',
                'code' => 'AHIST',
                'description' => 'Allergy relief medications',
                'is_active' => true,
            ],
            [
                'name' => 'Vitamins & Supplements',
                'code' => 'VIT',
                'description' => 'Nutritional supplements and vitamins',
                'is_active' => true,
            ],
            [
                'name' => 'Dermatological',
                'code' => 'DERM',
                'description' => 'Skin condition medications',
                'is_active' => true,
            ],
            [
                'name' => 'Antacids',
                'code' => 'ATAC',
                'description' => 'Stomach acid neutralizers',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            MedicineCategory::create($category);
        }
    }
}
