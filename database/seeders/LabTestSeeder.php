<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LabTest;

class LabTestSeeder extends Seeder
{
    public function run(): void
    {
        $tests = [
            [
                'code' => 'CBC001',
                'name' => 'Complete Blood Count (CBC)',
                'category' => 'hematology',
                'sample_type' => 'blood',
                'price' => 800,
                'turnaround_time' => 2,
                'parameters' => [
                    ['name' => 'Hemoglobin', 'unit' => 'g/dL', 'male_range' => '13.5-17.5', 'female_range' => '12.0-15.5'],
                    ['name' => 'RBC Count', 'unit' => 'million/μL', 'male_range' => '4.5-5.9', 'female_range' => '4.1-5.1'],
                    ['name' => 'WBC Count', 'unit' => 'thousand/μL', 'range' => '4.0-11.0'],
                    ['name' => 'Platelet Count', 'unit' => 'thousand/μL', 'range' => '150-450']
                ]
            ],
            [
                'code' => 'BMP001',
                'name' => 'Basic Metabolic Panel',
                'category' => 'biochemistry',
                'sample_type' => 'blood',
                'price' => 1200,
                'turnaround_time' => 4,
                'parameters' => [
                    ['name' => 'Glucose', 'unit' => 'mg/dL', 'range' => '70-100'],
                    ['name' => 'Creatinine', 'unit' => 'mg/dL', 'male_range' => '0.7-1.3', 'female_range' => '0.6-1.1'],
                    ['name' => 'Sodium', 'unit' => 'mEq/L', 'range' => '136-145'],
                    ['name' => 'Potassium', 'unit' => 'mEq/L', 'range' => '3.5-5.0']
                ]
            ],
            [
                'code' => 'LFT001',
                'name' => 'Liver Function Test',
                'category' => 'biochemistry',
                'sample_type' => 'blood',
                'price' => 1500,
                'turnaround_time' => 6,
                'parameters' => [
                    ['name' => 'ALT', 'unit' => 'U/L', 'range' => '7-56'],
                    ['name' => 'AST', 'unit' => 'U/L', 'range' => '10-40'],
                    ['name' => 'Bilirubin Total', 'unit' => 'mg/dL', 'range' => '0.3-1.2'],
                    ['name' => 'Albumin', 'unit' => 'g/dL', 'range' => '3.5-5.0']
                ]
            ],
            [
                'code' => 'URIN001',
                'name' => 'Urinalysis',
                'category' => 'biochemistry',
                'sample_type' => 'urine',
                'price' => 400,
                'turnaround_time' => 1,
                'parameters' => [
                    ['name' => 'Protein', 'unit' => 'mg/dL', 'range' => 'Negative'],
                    ['name' => 'Glucose', 'unit' => 'mg/dL', 'range' => 'Negative'],
                    ['name' => 'RBC', 'unit' => '/hpf', 'range' => '0-2'],
                    ['name' => 'WBC', 'unit' => '/hpf', 'range' => '0-5']
                ]
            ],
            [
                'code' => 'CULT001',
                'name' => 'Blood Culture',
                'category' => 'microbiology',
                'sample_type' => 'blood',
                'price' => 2000,
                'turnaround_time' => 72,
                'parameters' => [
                    ['name' => 'Growth', 'result_type' => 'text'],
                    ['name' => 'Organism', 'result_type' => 'text'],
                    ['name' => 'Sensitivity', 'result_type' => 'text']
                ]
            ],
            [
                'code' => 'TSH001',
                'name' => 'Thyroid Stimulating Hormone',
                'category' => 'immunology',
                'sample_type' => 'blood',
                'price' => 1800,
                'turnaround_time' => 24,
                'parameters' => [
                    ['name' => 'TSH', 'unit' => 'mIU/L', 'range' => '0.4-4.0']
                ]
            ],
            [
                'code' => 'HBA1C001',
                'name' => 'Hemoglobin A1c',
                'category' => 'biochemistry',
                'sample_type' => 'blood',
                'price' => 1000,
                'turnaround_time' => 4,
                'parameters' => [
                    ['name' => 'HbA1c', 'unit' => '%', 'range' => '<5.7']
                ]
            ],
            [
                'code' => 'LIPID001',
                'name' => 'Lipid Profile',
                'category' => 'biochemistry',
                'sample_type' => 'blood',
                'price' => 1200,
                'turnaround_time' => 6,
                'parameters' => [
                    ['name' => 'Total Cholesterol', 'unit' => 'mg/dL', 'range' => '<200'],
                    ['name' => 'HDL', 'unit' => 'mg/dL', 'male_range' => '>40', 'female_range' => '>50'],
                    ['name' => 'LDL', 'unit' => 'mg/dL', 'range' => '<100'],
                    ['name' => 'Triglycerides', 'unit' => 'mg/dL', 'range' => '<150']
                ]
            ]
        ];

        foreach ($tests as $testData) {
            $parameters = $testData['parameters'];
            unset($testData['parameters']);
            
            $test = LabTest::firstOrCreate(
                ['code' => $testData['code']],
                $testData
            );
            
            // Create parameters
            foreach ($parameters as $index => $paramData) {
                $referenceRanges = [];
                if (isset($paramData['range'])) {
                    $referenceRanges['range'] = $paramData['range'];
                }
                if (isset($paramData['male_range'])) {
                    $referenceRanges['male'] = $paramData['male_range'];
                }
                if (isset($paramData['female_range'])) {
                    $referenceRanges['female'] = $paramData['female_range'];
                }
                
                $test->parameters()->firstOrCreate(
                    ['parameter_name' => $paramData['name']],
                    [
                        'parameter_name' => $paramData['name'],
                        'unit' => $paramData['unit'] ?? null,
                        'data_type' => 'numeric',
                        'reference_ranges' => $referenceRanges,
                        'display_order' => $index + 1,
                        'is_active' => true
                    ]
                );
            }
        }
    }
}