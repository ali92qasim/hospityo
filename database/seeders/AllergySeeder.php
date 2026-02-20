<?php

namespace Database\Seeders;

use App\Models\Allergy;
use Illuminate\Database\Seeder;

class AllergySeeder extends Seeder
{
    public function run(): void
    {
        $allergies = [
            // Drug Allergies
            ['name' => 'Penicillin', 'category' => 'drug', 'is_standard' => true],
            ['name' => 'Amoxicillin', 'category' => 'drug', 'is_standard' => true],
            ['name' => 'Aspirin', 'category' => 'drug', 'is_standard' => true],
            ['name' => 'Ibuprofen', 'category' => 'drug', 'is_standard' => true],
            ['name' => 'Sulfa Drugs', 'category' => 'drug', 'is_standard' => true],
            ['name' => 'Cephalosporins', 'category' => 'drug', 'is_standard' => true],
            ['name' => 'Codeine', 'category' => 'drug', 'is_standard' => true],
            ['name' => 'Morphine', 'category' => 'drug', 'is_standard' => true],
            ['name' => 'Latex', 'category' => 'drug', 'is_standard' => true],
            ['name' => 'Anesthesia', 'category' => 'drug', 'is_standard' => true],
            
            // Food Allergies
            ['name' => 'Peanuts', 'category' => 'food', 'is_standard' => true],
            ['name' => 'Tree Nuts', 'category' => 'food', 'is_standard' => true],
            ['name' => 'Milk/Dairy', 'category' => 'food', 'is_standard' => true],
            ['name' => 'Eggs', 'category' => 'food', 'is_standard' => true],
            ['name' => 'Wheat/Gluten', 'category' => 'food', 'is_standard' => true],
            ['name' => 'Soy', 'category' => 'food', 'is_standard' => true],
            ['name' => 'Fish', 'category' => 'food', 'is_standard' => true],
            ['name' => 'Shellfish', 'category' => 'food', 'is_standard' => true],
            ['name' => 'Sesame', 'category' => 'food', 'is_standard' => true],
            
            // Environmental Allergies
            ['name' => 'Pollen', 'category' => 'environmental', 'is_standard' => true],
            ['name' => 'Dust Mites', 'category' => 'environmental', 'is_standard' => true],
            ['name' => 'Mold', 'category' => 'environmental', 'is_standard' => true],
            ['name' => 'Pet Dander', 'category' => 'environmental', 'is_standard' => true],
            ['name' => 'Insect Stings', 'category' => 'environmental', 'is_standard' => true],
            
            // Other Common Allergies
            ['name' => 'Contrast Dye', 'category' => 'other', 'is_standard' => true],
            ['name' => 'Adhesive Tape', 'category' => 'other', 'is_standard' => true],
            ['name' => 'No Known Allergies', 'category' => 'other', 'is_standard' => true],
        ];

        foreach ($allergies as $allergy) {
            Allergy::create($allergy);
        }
    }
}
