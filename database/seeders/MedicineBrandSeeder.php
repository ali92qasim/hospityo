<?php

namespace Database\Seeders;

use App\Models\MedicineBrand;
use Illuminate\Database\Seeder;

class MedicineBrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            [
                'name' => 'GSK',
                'description' => 'GlaxoSmithKline - Global pharmaceutical company',
                'is_active' => true,
            ],
            [
                'name' => 'Pfizer',
                'description' => 'Pfizer Inc. - Leading pharmaceutical manufacturer',
                'is_active' => true,
            ],
            [
                'name' => 'Novartis',
                'description' => 'Novartis AG - Swiss multinational pharmaceutical company',
                'is_active' => true,
            ],
            [
                'name' => 'Abbott',
                'description' => 'Abbott Laboratories - Healthcare products company',
                'is_active' => true,
            ],
            [
                'name' => 'Sanofi',
                'description' => 'Sanofi S.A. - French multinational pharmaceutical company',
                'is_active' => true,
            ],
            [
                'name' => 'Roche',
                'description' => 'F. Hoffmann-La Roche AG - Swiss healthcare company',
                'is_active' => true,
            ],
            [
                'name' => 'Getz Pharma',
                'description' => 'Getz Pharma - Pakistani pharmaceutical company',
                'is_active' => true,
            ],
            [
                'name' => 'Searle',
                'description' => 'Searle Pakistan - Local pharmaceutical manufacturer',
                'is_active' => true,
            ],
            [
                'name' => 'Hilton Pharma',
                'description' => 'Hilton Pharma - Pakistani pharmaceutical company',
                'is_active' => true,
            ],
            [
                'name' => 'Martin Dow',
                'description' => 'Martin Dow - Pharmaceutical manufacturer',
                'is_active' => true,
            ],
            [
                'name' => 'Ferozsons',
                'description' => 'Ferozsons Laboratories - Pakistani pharmaceutical company',
                'is_active' => true,
            ],
            [
                'name' => 'Highnoon',
                'description' => 'Highnoon Laboratories - Pharmaceutical manufacturer',
                'is_active' => true,
            ],
        ];

        foreach ($brands as $brand) {
            MedicineBrand::create($brand);
        }
    }
}
