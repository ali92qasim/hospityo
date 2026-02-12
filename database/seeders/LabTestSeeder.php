<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LabTest;

class LabTestSeeder extends Seeder
{
    public function run(): void
    {
        $tests = [
            // Hematology Tests
            [
                'code' => 'CBC001',
                'name' => 'Complete Blood Count (CBC)',
                'category' => 'hematology',
                'sample_type' => 'blood',
                'price' => 800,
                'turnaround_time' => 2,
                'parameters' => [
                    ['name' => 'Hemoglobin', 'unit' => 'g/dL', 'male_range' => '13.5-17.5', 'female_range' => '12.0-15.5', 'critical_low' => '<7.0', 'critical_high' => '>20.0'],
                    ['name' => 'RBC Count', 'unit' => 'million/μL', 'male_range' => '4.5-5.9', 'female_range' => '4.1-5.1'],
                    ['name' => 'WBC Count', 'unit' => 'thousand/μL', 'range' => '4.0-11.0', 'critical_low' => '<2.0', 'critical_high' => '>30.0'],
                    ['name' => 'Platelet Count', 'unit' => 'thousand/μL', 'range' => '150-450', 'critical_low' => '<50', 'critical_high' => '>1000'],
                    ['name' => 'Hematocrit', 'unit' => '%', 'male_range' => '38.8-50.0', 'female_range' => '34.9-44.5'],
                    ['name' => 'MCV', 'unit' => 'fL', 'range' => '80-100'],
                    ['name' => 'MCH', 'unit' => 'pg', 'range' => '27-33'],
                    ['name' => 'MCHC', 'unit' => 'g/dL', 'range' => '32-36'],
                    ['name' => 'Neutrophils', 'unit' => '%', 'range' => '40-70'],
                    ['name' => 'Lymphocytes', 'unit' => '%', 'range' => '20-40'],
                    ['name' => 'Monocytes', 'unit' => '%', 'range' => '2-8'],
                    ['name' => 'Eosinophils', 'unit' => '%', 'range' => '1-4'],
                    ['name' => 'Basophils', 'unit' => '%', 'range' => '0-1']
                ]
            ],
            [
                'code' => 'ESR001',
                'name' => 'Erythrocyte Sedimentation Rate (ESR)',
                'category' => 'hematology',
                'sample_type' => 'blood',
                'price' => 300,
                'turnaround_time' => 1,
                'parameters' => [
                    ['name' => 'ESR', 'unit' => 'mm/hr', 'male_range' => '0-15', 'female_range' => '0-20']
                ]
            ],
            
            // Biochemistry Tests
            [
                'code' => 'BMP001',
                'name' => 'Basic Metabolic Panel',
                'category' => 'biochemistry',
                'sample_type' => 'blood',
                'price' => 1200,
                'turnaround_time' => 4,
                'parameters' => [
                    ['name' => 'Glucose (Fasting)', 'unit' => 'mg/dL', 'range' => '70-100', 'critical_low' => '<40', 'critical_high' => '>400'],
                    ['name' => 'Creatinine', 'unit' => 'mg/dL', 'male_range' => '0.7-1.3', 'female_range' => '0.6-1.1'],
                    ['name' => 'Blood Urea Nitrogen (BUN)', 'unit' => 'mg/dL', 'range' => '10-20'],
                    ['name' => 'Sodium', 'unit' => 'mEq/L', 'range' => '136-145', 'critical_low' => '<120', 'critical_high' => '>160'],
                    ['name' => 'Potassium', 'unit' => 'mEq/L', 'range' => '3.5-5.0', 'critical_low' => '<2.5', 'critical_high' => '>6.5'],
                    ['name' => 'Chloride', 'unit' => 'mEq/L', 'range' => '98-107'],
                    ['name' => 'Bicarbonate', 'unit' => 'mEq/L', 'range' => '22-29']
                ]
            ],
            [
                'code' => 'LFT001',
                'name' => 'Liver Function Test (LFT)',
                'category' => 'biochemistry',
                'sample_type' => 'blood',
                'price' => 1500,
                'turnaround_time' => 6,
                'parameters' => [
                    ['name' => 'ALT (SGPT)', 'unit' => 'U/L', 'range' => '7-56'],
                    ['name' => 'AST (SGOT)', 'unit' => 'U/L', 'range' => '10-40'],
                    ['name' => 'Alkaline Phosphatase', 'unit' => 'U/L', 'range' => '44-147'],
                    ['name' => 'Bilirubin Total', 'unit' => 'mg/dL', 'range' => '0.3-1.2'],
                    ['name' => 'Bilirubin Direct', 'unit' => 'mg/dL', 'range' => '0.0-0.3'],
                    ['name' => 'Albumin', 'unit' => 'g/dL', 'range' => '3.5-5.0'],
                    ['name' => 'Total Protein', 'unit' => 'g/dL', 'range' => '6.0-8.3'],
                    ['name' => 'GGT', 'unit' => 'U/L', 'male_range' => '10-71', 'female_range' => '6-42']
                ]
            ],
            [
                'code' => 'RFT001',
                'name' => 'Renal Function Test (RFT)',
                'category' => 'biochemistry',
                'sample_type' => 'blood',
                'price' => 1000,
                'turnaround_time' => 4,
                'parameters' => [
                    ['name' => 'Creatinine', 'unit' => 'mg/dL', 'male_range' => '0.7-1.3', 'female_range' => '0.6-1.1'],
                    ['name' => 'Blood Urea Nitrogen', 'unit' => 'mg/dL', 'range' => '10-20'],
                    ['name' => 'Uric Acid', 'unit' => 'mg/dL', 'male_range' => '3.5-7.2', 'female_range' => '2.6-6.0'],
                    ['name' => 'eGFR', 'unit' => 'mL/min/1.73m²', 'range' => '>60']
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
                    ['name' => 'HDL Cholesterol', 'unit' => 'mg/dL', 'male_range' => '>40', 'female_range' => '>50'],
                    ['name' => 'LDL Cholesterol', 'unit' => 'mg/dL', 'range' => '<100'],
                    ['name' => 'Triglycerides', 'unit' => 'mg/dL', 'range' => '<150'],
                    ['name' => 'VLDL', 'unit' => 'mg/dL', 'range' => '5-40']
                ]
            ],
            [
                'code' => 'HBA1C001',
                'name' => 'Hemoglobin A1c (HbA1c)',
                'category' => 'biochemistry',
                'sample_type' => 'blood',
                'price' => 1000,
                'turnaround_time' => 4,
                'parameters' => [
                    ['name' => 'HbA1c', 'unit' => '%', 'range' => '<5.7']
                ]
            ],
            
            // Thyroid Tests
            [
                'code' => 'TSH001',
                'name' => 'Thyroid Stimulating Hormone (TSH)',
                'category' => 'immunology',
                'sample_type' => 'blood',
                'price' => 1800,
                'turnaround_time' => 24,
                'parameters' => [
                    ['name' => 'TSH', 'unit' => 'mIU/L', 'range' => '0.4-4.0']
                ]
            ],
            [
                'code' => 'TFT001',
                'name' => 'Thyroid Function Test (Complete)',
                'category' => 'immunology',
                'sample_type' => 'blood',
                'price' => 2500,
                'turnaround_time' => 24,
                'parameters' => [
                    ['name' => 'TSH', 'unit' => 'mIU/L', 'range' => '0.4-4.0'],
                    ['name' => 'T3 Total', 'unit' => 'ng/dL', 'range' => '80-200'],
                    ['name' => 'T4 Total', 'unit' => 'μg/dL', 'range' => '5.0-12.0'],
                    ['name' => 'Free T3', 'unit' => 'pg/mL', 'range' => '2.3-4.2'],
                    ['name' => 'Free T4', 'unit' => 'ng/dL', 'range' => '0.8-1.8']
                ]
            ],
            
            // Cardiac Markers
            [
                'code' => 'TROP001',
                'name' => 'Troponin I',
                'category' => 'biochemistry',
                'sample_type' => 'blood',
                'price' => 2000,
                'turnaround_time' => 2,
                'parameters' => [
                    ['name' => 'Troponin I', 'unit' => 'ng/mL', 'range' => '<0.04', 'critical_high' => '>0.4']
                ]
            ],
            
            // Vitamins & Minerals
            [
                'code' => 'VITD001',
                'name' => 'Vitamin D (25-OH)',
                'category' => 'biochemistry',
                'sample_type' => 'blood',
                'price' => 2200,
                'turnaround_time' => 24,
                'parameters' => [
                    ['name' => 'Vitamin D', 'unit' => 'ng/mL', 'range' => '30-100']
                ]
            ],
            [
                'code' => 'VITB12001',
                'name' => 'Vitamin B12',
                'category' => 'biochemistry',
                'sample_type' => 'blood',
                'price' => 1800,
                'turnaround_time' => 24,
                'parameters' => [
                    ['name' => 'Vitamin B12', 'unit' => 'pg/mL', 'range' => '200-900']
                ]
            ],
            [
                'code' => 'IRON001',
                'name' => 'Iron Studies',
                'category' => 'biochemistry',
                'sample_type' => 'blood',
                'price' => 1500,
                'turnaround_time' => 12,
                'parameters' => [
                    ['name' => 'Serum Iron', 'unit' => 'μg/dL', 'male_range' => '65-175', 'female_range' => '50-170'],
                    ['name' => 'TIBC', 'unit' => 'μg/dL', 'range' => '250-450'],
                    ['name' => 'Ferritin', 'unit' => 'ng/mL', 'male_range' => '12-300', 'female_range' => '10-150'],
                    ['name' => 'Transferrin Saturation', 'unit' => '%', 'range' => '20-50']
                ]
            ],
            [
                'code' => 'CALC001',
                'name' => 'Calcium Total',
                'category' => 'biochemistry',
                'sample_type' => 'blood',
                'price' => 400,
                'turnaround_time' => 4,
                'parameters' => [
                    ['name' => 'Calcium', 'unit' => 'mg/dL', 'range' => '8.5-10.5', 'critical_low' => '<7.0', 'critical_high' => '>13.0']
                ]
            ],
            
            // Urinalysis
            [
                'code' => 'URIN001',
                'name' => 'Urinalysis (Complete)',
                'category' => 'biochemistry',
                'sample_type' => 'urine',
                'price' => 400,
                'turnaround_time' => 1,
                'parameters' => [
                    ['name' => 'Color', 'unit' => '', 'range' => 'Yellow'],
                    ['name' => 'Appearance', 'unit' => '', 'range' => 'Clear'],
                    ['name' => 'pH', 'unit' => '', 'range' => '4.5-8.0'],
                    ['name' => 'Specific Gravity', 'unit' => '', 'range' => '1.005-1.030'],
                    ['name' => 'Protein', 'unit' => '', 'range' => 'Negative'],
                    ['name' => 'Glucose', 'unit' => '', 'range' => 'Negative'],
                    ['name' => 'Ketones', 'unit' => '', 'range' => 'Negative'],
                    ['name' => 'Blood', 'unit' => '', 'range' => 'Negative'],
                    ['name' => 'Bilirubin', 'unit' => '', 'range' => 'Negative'],
                    ['name' => 'Urobilinogen', 'unit' => 'mg/dL', 'range' => '0.1-1.0'],
                    ['name' => 'RBC', 'unit' => '/hpf', 'range' => '0-2'],
                    ['name' => 'WBC', 'unit' => '/hpf', 'range' => '0-5'],
                    ['name' => 'Epithelial Cells', 'unit' => '/hpf', 'range' => 'Few'],
                    ['name' => 'Casts', 'unit' => '/lpf', 'range' => '0-2'],
                    ['name' => 'Crystals', 'unit' => '', 'range' => 'None'],
                    ['name' => 'Bacteria', 'unit' => '', 'range' => 'None']
                ]
            ],
            
            // Microbiology
            [
                'code' => 'CULT001',
                'name' => 'Blood Culture & Sensitivity',
                'category' => 'microbiology',
                'sample_type' => 'blood',
                'price' => 2000,
                'turnaround_time' => 72,
                'parameters' => [
                    ['name' => 'Growth', 'result_type' => 'text'],
                    ['name' => 'Organism Identified', 'result_type' => 'text'],
                    ['name' => 'Antibiotic Sensitivity', 'result_type' => 'text']
                ]
            ],
            [
                'code' => 'CULT002',
                'name' => 'Urine Culture & Sensitivity',
                'category' => 'microbiology',
                'sample_type' => 'urine',
                'price' => 1500,
                'turnaround_time' => 48,
                'parameters' => [
                    ['name' => 'Growth', 'result_type' => 'text'],
                    ['name' => 'Colony Count', 'unit' => 'CFU/mL', 'result_type' => 'text'],
                    ['name' => 'Organism Identified', 'result_type' => 'text'],
                    ['name' => 'Antibiotic Sensitivity', 'result_type' => 'text']
                ]
            ],
            
            // Coagulation Tests
            [
                'code' => 'PT001',
                'name' => 'Prothrombin Time (PT/INR)',
                'category' => 'hematology',
                'sample_type' => 'blood',
                'price' => 600,
                'turnaround_time' => 2,
                'parameters' => [
                    ['name' => 'PT', 'unit' => 'seconds', 'range' => '11-13.5'],
                    ['name' => 'INR', 'unit' => '', 'range' => '0.8-1.2']
                ]
            ],
            [
                'code' => 'APTT001',
                'name' => 'Activated Partial Thromboplastin Time',
                'category' => 'hematology',
                'sample_type' => 'blood',
                'price' => 600,
                'turnaround_time' => 2,
                'parameters' => [
                    ['name' => 'APTT', 'unit' => 'seconds', 'range' => '25-35']
                ]
            ],
            
            // Hepatitis Markers
            [
                'code' => 'HBSAG001',
                'name' => 'Hepatitis B Surface Antigen',
                'category' => 'immunology',
                'sample_type' => 'blood',
                'price' => 1200,
                'turnaround_time' => 24,
                'parameters' => [
                    ['name' => 'HBsAg', 'unit' => '', 'range' => 'Non-Reactive']
                ]
            ],
            [
                'code' => 'HCV001',
                'name' => 'Hepatitis C Antibody',
                'category' => 'immunology',
                'sample_type' => 'blood',
                'price' => 1500,
                'turnaround_time' => 24,
                'parameters' => [
                    ['name' => 'Anti-HCV', 'unit' => '', 'range' => 'Non-Reactive']
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
                $criticalValues = [];
                
                if (isset($paramData['range'])) {
                    $referenceRanges['range'] = $paramData['range'];
                }
                if (isset($paramData['male_range'])) {
                    $referenceRanges['male'] = $paramData['male_range'];
                }
                if (isset($paramData['female_range'])) {
                    $referenceRanges['female'] = $paramData['female_range'];
                }
                if (isset($paramData['critical_low'])) {
                    $criticalValues['low'] = $paramData['critical_low'];
                }
                if (isset($paramData['critical_high'])) {
                    $criticalValues['high'] = $paramData['critical_high'];
                }
                
                $dataType = isset($paramData['result_type']) && $paramData['result_type'] === 'text' ? 'text' : 'numeric';
                
                $test->parameters()->firstOrCreate(
                    ['parameter_name' => $paramData['name']],
                    [
                        'parameter_name' => $paramData['name'],
                        'unit' => $paramData['unit'] ?? null,
                        'data_type' => $dataType,
                        'reference_ranges' => $referenceRanges,
                        'critical_values' => count($criticalValues) > 0 ? $criticalValues : null,
                        'display_order' => $index + 1,
                        'is_active' => true
                    ]
                );
            }
        }
    }
}
