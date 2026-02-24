<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Investigation;
use App\Models\LabTestParameter;

class LabTestParameterSeeder extends Seeder
{
    public function run(): void
    {
        // CBC Parameters
        $cbc = Investigation::where('code', 'CBC001')->first();
        if ($cbc) {
            $cbcParams = [
                ['parameter_name' => 'Hemoglobin', 'unit' => 'g/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => 'M: 13.5-17.5, F: 12.0-15.5'], 'display_order' => 1],
                ['parameter_name' => 'RBC Count', 'unit' => 'million/μL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => 'M: 4.5-5.5, F: 4.0-5.0'], 'display_order' => 2],
                ['parameter_name' => 'WBC Count', 'unit' => 'cells/μL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '4,000-11,000'], 'display_order' => 3],
                ['parameter_name' => 'Platelet Count', 'unit' => 'cells/μL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '150,000-400,000'], 'display_order' => 4],
                ['parameter_name' => 'Hematocrit', 'unit' => '%', 'data_type' => 'numeric', 'reference_ranges' => ['range' => 'M: 38.8-50.0, F: 34.9-44.5'], 'display_order' => 5],
                ['parameter_name' => 'MCV', 'unit' => 'fL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '80-100'], 'display_order' => 6],
                ['parameter_name' => 'MCH', 'unit' => 'pg', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '27-33'], 'display_order' => 7],
                ['parameter_name' => 'MCHC', 'unit' => 'g/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '32-36'], 'display_order' => 8],
            ];
            
            foreach ($cbcParams as $param) {
                LabTestParameter::firstOrCreate(
                    ['lab_test_id' => $cbc->id, 'parameter_name' => $param['parameter_name']],
                    $param
                );
            }
        }

        // ESR Parameters
        $esr = Investigation::where('code', 'ESR001')->first();
        if ($esr) {
            LabTestParameter::firstOrCreate(
                ['lab_test_id' => $esr->id, 'parameter_name' => 'ESR'],
                ['parameter_name' => 'ESR', 'unit' => 'mm/hr', 'data_type' => 'numeric', 'reference_ranges' => ['range' => 'M: 0-15, F: 0-20'], 'display_order' => 1]
            );
        }

        // PT/INR Parameters
        $pt = Investigation::where('code', 'PT001')->first();
        if ($pt) {
            $ptParams = [
                ['parameter_name' => 'PT', 'unit' => 'seconds', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '11-13.5'], 'display_order' => 1],
                ['parameter_name' => 'INR', 'unit' => '', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '0.8-1.2'], 'display_order' => 2],
            ];
            
            foreach ($ptParams as $param) {
                LabTestParameter::firstOrCreate(
                    ['lab_test_id' => $pt->id, 'parameter_name' => $param['parameter_name']],
                    $param
                );
            }
        }

        // APTT Parameters
        $aptt = Investigation::where('code', 'APTT001')->first();
        if ($aptt) {
            LabTestParameter::firstOrCreate(
                ['lab_test_id' => $aptt->id, 'parameter_name' => 'APTT'],
                ['parameter_name' => 'APTT', 'unit' => 'seconds', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '25-35'], 'display_order' => 1]
            );
        }

        // Basic Metabolic Panel Parameters
        $bmp = Investigation::where('code', 'BMP001')->first();
        if ($bmp) {
            $bmpParams = [
                ['parameter_name' => 'Glucose', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '70-100'], 'display_order' => 1],
                ['parameter_name' => 'Calcium', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '8.5-10.5'], 'display_order' => 2],
                ['parameter_name' => 'Sodium', 'unit' => 'mEq/L', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '136-145'], 'display_order' => 3],
                ['parameter_name' => 'Potassium', 'unit' => 'mEq/L', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '3.5-5.1'], 'display_order' => 4],
                ['parameter_name' => 'Chloride', 'unit' => 'mEq/L', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '98-107'], 'display_order' => 5],
                ['parameter_name' => 'CO2', 'unit' => 'mEq/L', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '23-29'], 'display_order' => 6],
                ['parameter_name' => 'BUN', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '7-20'], 'display_order' => 7],
                ['parameter_name' => 'Creatinine', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => 'M: 0.7-1.3, F: 0.6-1.1'], 'display_order' => 8],
            ];
            
            foreach ($bmpParams as $param) {
                LabTestParameter::firstOrCreate(
                    ['lab_test_id' => $bmp->id, 'parameter_name' => $param['parameter_name']],
                    $param
                );
            }
        }

        // Liver Function Test Parameters
        $lft = Investigation::where('code', 'LFT001')->first();
        if ($lft) {
            $lftParams = [
                ['parameter_name' => 'Total Bilirubin', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '0.3-1.2'], 'display_order' => 1],
                ['parameter_name' => 'Direct Bilirubin', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '0.0-0.3'], 'display_order' => 2],
                ['parameter_name' => 'SGPT (ALT)', 'unit' => 'U/L', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '7-56'], 'display_order' => 3],
                ['parameter_name' => 'SGOT (AST)', 'unit' => 'U/L', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '10-40'], 'display_order' => 4],
                ['parameter_name' => 'Alkaline Phosphatase', 'unit' => 'U/L', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '44-147'], 'display_order' => 5],
                ['parameter_name' => 'Total Protein', 'unit' => 'g/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '6.0-8.3'], 'display_order' => 6],
                ['parameter_name' => 'Albumin', 'unit' => 'g/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '3.5-5.5'], 'display_order' => 7],
                ['parameter_name' => 'Globulin', 'unit' => 'g/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '2.0-3.5'], 'display_order' => 8],
            ];
            
            foreach ($lftParams as $param) {
                LabTestParameter::firstOrCreate(
                    ['lab_test_id' => $lft->id, 'parameter_name' => $param['parameter_name']],
                    $param
                );
            }
        }

        // Renal Function Test Parameters
        $rft = Investigation::where('code', 'RFT001')->first();
        if ($rft) {
            $rftParams = [
                ['parameter_name' => 'Blood Urea', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '15-40'], 'display_order' => 1],
                ['parameter_name' => 'Serum Creatinine', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => 'M: 0.7-1.3, F: 0.6-1.1'], 'display_order' => 2],
                ['parameter_name' => 'Uric Acid', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => 'M: 3.5-7.2, F: 2.6-6.0'], 'display_order' => 3],
                ['parameter_name' => 'Sodium', 'unit' => 'mEq/L', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '136-145'], 'display_order' => 4],
                ['parameter_name' => 'Potassium', 'unit' => 'mEq/L', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '3.5-5.1'], 'display_order' => 5],
                ['parameter_name' => 'Chloride', 'unit' => 'mEq/L', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '98-107'], 'display_order' => 6],
            ];
            
            foreach ($rftParams as $param) {
                LabTestParameter::firstOrCreate(
                    ['lab_test_id' => $rft->id, 'parameter_name' => $param['parameter_name']],
                    $param
                );
            }
        }

        // Lipid Profile Parameters
        $lipid = Investigation::where('code', 'LIPID001')->first();
        if ($lipid) {
            $lipidParams = [
                ['parameter_name' => 'Total Cholesterol', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '<200 Desirable'], 'display_order' => 1],
                ['parameter_name' => 'Triglycerides', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '<150 Normal'], 'display_order' => 2],
                ['parameter_name' => 'HDL Cholesterol', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '>40 Desirable'], 'display_order' => 3],
                ['parameter_name' => 'LDL Cholesterol', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '<100 Optimal'], 'display_order' => 4],
                ['parameter_name' => 'VLDL Cholesterol', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '5-40'], 'display_order' => 5],
            ];
            
            foreach ($lipidParams as $param) {
                LabTestParameter::firstOrCreate(
                    ['lab_test_id' => $lipid->id, 'parameter_name' => $param['parameter_name']],
                    $param
                );
            }
        }

        // Thyroid Function Test Parameters
        $tft = Investigation::where('code', 'TFT001')->first();
        if ($tft) {
            $tftParams = [
                ['parameter_name' => 'TSH', 'unit' => 'μIU/mL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '0.4-4.0'], 'display_order' => 1],
                ['parameter_name' => 'T3 Total', 'unit' => 'ng/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '80-200'], 'display_order' => 2],
                ['parameter_name' => 'T4 Total', 'unit' => 'μg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '5.0-12.0'], 'display_order' => 3],
                ['parameter_name' => 'Free T3', 'unit' => 'pg/mL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '2.3-4.2'], 'display_order' => 4],
                ['parameter_name' => 'Free T4', 'unit' => 'ng/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '0.8-1.8'], 'display_order' => 5],
            ];
            
            foreach ($tftParams as $param) {
                LabTestParameter::firstOrCreate(
                    ['lab_test_id' => $tft->id, 'parameter_name' => $param['parameter_name']],
                    $param
                );
            }
        }

        // TSH (Standalone) Parameters
        $tsh = Investigation::where('code', 'TSH001')->first();
        if ($tsh) {
            LabTestParameter::firstOrCreate(
                ['lab_test_id' => $tsh->id, 'parameter_name' => 'TSH'],
                ['parameter_name' => 'TSH', 'unit' => 'μIU/mL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '0.4-4.0'], 'display_order' => 1]
            );
        }

        // HbA1c Parameters
        $hba1c = Investigation::where('code', 'HBA1C001')->first();
        if ($hba1c) {
            LabTestParameter::firstOrCreate(
                ['lab_test_id' => $hba1c->id, 'parameter_name' => 'HbA1c'],
                ['parameter_name' => 'HbA1c', 'unit' => '%', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '<5.7 Normal, 5.7-6.4 Prediabetes, ≥6.5 Diabetes'], 'display_order' => 1]
            );
        }

        // Troponin I Parameters
        $troponin = Investigation::where('code', 'TROP001')->first();
        if ($troponin) {
            LabTestParameter::firstOrCreate(
                ['lab_test_id' => $troponin->id, 'parameter_name' => 'Troponin I'],
                ['parameter_name' => 'Troponin I', 'unit' => 'ng/mL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '<0.04'], 'display_order' => 1]
            );
        }

        // Vitamin D Parameters
        $vitd = Investigation::where('code', 'VITD001')->first();
        if ($vitd) {
            LabTestParameter::firstOrCreate(
                ['lab_test_id' => $vitd->id, 'parameter_name' => '25-OH Vitamin D'],
                ['parameter_name' => '25-OH Vitamin D', 'unit' => 'ng/mL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '30-100 Sufficient, 20-30 Insufficient, <20 Deficient'], 'display_order' => 1]
            );
        }

        // Vitamin B12 Parameters
        $vitb12 = Investigation::where('code', 'VITB12001')->first();
        if ($vitb12) {
            LabTestParameter::firstOrCreate(
                ['lab_test_id' => $vitb12->id, 'parameter_name' => 'Vitamin B12'],
                ['parameter_name' => 'Vitamin B12', 'unit' => 'pg/mL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '200-900'], 'display_order' => 1]
            );
        }

        // Iron Studies Parameters
        $iron = Investigation::where('code', 'IRON001')->first();
        if ($iron) {
            $ironParams = [
                ['parameter_name' => 'Serum Iron', 'unit' => 'μg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => 'M: 65-175, F: 50-170'], 'display_order' => 1],
                ['parameter_name' => 'TIBC', 'unit' => 'μg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '250-450'], 'display_order' => 2],
                ['parameter_name' => 'Transferrin Saturation', 'unit' => '%', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '20-50'], 'display_order' => 3],
                ['parameter_name' => 'Ferritin', 'unit' => 'ng/mL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => 'M: 24-336, F: 11-307'], 'display_order' => 4],
            ];
            
            foreach ($ironParams as $param) {
                LabTestParameter::firstOrCreate(
                    ['lab_test_id' => $iron->id, 'parameter_name' => $param['parameter_name']],
                    $param
                );
            }
        }

        // Calcium Total Parameters
        $calcium = Investigation::where('code', 'CALC001')->first();
        if ($calcium) {
            LabTestParameter::firstOrCreate(
                ['lab_test_id' => $calcium->id, 'parameter_name' => 'Calcium Total'],
                ['parameter_name' => 'Calcium Total', 'unit' => 'mg/dL', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '8.5-10.5'], 'display_order' => 1]
            );
        }

        // Urinalysis Parameters
        $urinalysis = Investigation::where('code', 'URIN001')->first();
        if ($urinalysis) {
            $urinParams = [
                ['parameter_name' => 'Color', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'Yellow'], 'display_order' => 1],
                ['parameter_name' => 'Appearance', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'Clear'], 'display_order' => 2],
                ['parameter_name' => 'pH', 'unit' => '', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '4.5-8.0'], 'display_order' => 3],
                ['parameter_name' => 'Specific Gravity', 'unit' => '', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '1.005-1.030'], 'display_order' => 4],
                ['parameter_name' => 'Protein', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'Negative'], 'display_order' => 5],
                ['parameter_name' => 'Glucose', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'Negative'], 'display_order' => 6],
                ['parameter_name' => 'Ketones', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'Negative'], 'display_order' => 7],
                ['parameter_name' => 'Blood', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'Negative'], 'display_order' => 8],
                ['parameter_name' => 'Bilirubin', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'Negative'], 'display_order' => 9],
                ['parameter_name' => 'Urobilinogen', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'Normal'], 'display_order' => 10],
                ['parameter_name' => 'Nitrite', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'Negative'], 'display_order' => 11],
                ['parameter_name' => 'Leukocyte Esterase', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'Negative'], 'display_order' => 12],
                ['parameter_name' => 'WBC', 'unit' => '/HPF', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '0-5'], 'display_order' => 13],
                ['parameter_name' => 'RBC', 'unit' => '/HPF', 'data_type' => 'numeric', 'reference_ranges' => ['range' => '0-2'], 'display_order' => 14],
                ['parameter_name' => 'Epithelial Cells', 'unit' => '/HPF', 'data_type' => 'text', 'reference_ranges' => ['range' => 'Few'], 'display_order' => 15],
                ['parameter_name' => 'Casts', 'unit' => '/LPF', 'data_type' => 'text', 'reference_ranges' => ['range' => 'None'], 'display_order' => 16],
                ['parameter_name' => 'Crystals', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'None'], 'display_order' => 17],
                ['parameter_name' => 'Bacteria', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'None'], 'display_order' => 18],
            ];
            
            foreach ($urinParams as $param) {
                LabTestParameter::firstOrCreate(
                    ['lab_test_id' => $urinalysis->id, 'parameter_name' => $param['parameter_name']],
                    $param
                );
            }
        }

        // Hepatitis B Surface Antigen Parameters
        $hbsag = Investigation::where('code', 'HBSAG001')->first();
        if ($hbsag) {
            LabTestParameter::firstOrCreate(
                ['lab_test_id' => $hbsag->id, 'parameter_name' => 'HBsAg'],
                ['parameter_name' => 'HBsAg', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'Non-Reactive'], 'display_order' => 1]
            );
        }

        // Hepatitis C Antibody Parameters
        $hcv = Investigation::where('code', 'HCV001')->first();
        if ($hcv) {
            LabTestParameter::firstOrCreate(
                ['lab_test_id' => $hcv->id, 'parameter_name' => 'Anti-HCV'],
                ['parameter_name' => 'Anti-HCV', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'Non-Reactive'], 'display_order' => 1]
            );
        }

        // Blood Culture & Sensitivity Parameters
        $bloodCulture = Investigation::where('code', 'CULT001')->first();
        if ($bloodCulture) {
            $bloodCultureParams = [
                ['parameter_name' => 'Culture Result', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'No Growth'], 'display_order' => 1],
                ['parameter_name' => 'Organism Identified', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'None'], 'display_order' => 2],
                ['parameter_name' => 'Colony Count', 'unit' => 'CFU/mL', 'data_type' => 'text', 'reference_ranges' => ['range' => 'N/A'], 'display_order' => 3],
                ['parameter_name' => 'Sensitivity Report', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'N/A'], 'display_order' => 4],
            ];
            
            foreach ($bloodCultureParams as $param) {
                LabTestParameter::firstOrCreate(
                    ['lab_test_id' => $bloodCulture->id, 'parameter_name' => $param['parameter_name']],
                    $param
                );
            }
        }

        // Urine Culture & Sensitivity Parameters
        $urineCulture = Investigation::where('code', 'CULT002')->first();
        if ($urineCulture) {
            $urineCultureParams = [
                ['parameter_name' => 'Culture Result', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'No Growth'], 'display_order' => 1],
                ['parameter_name' => 'Organism Identified', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'None'], 'display_order' => 2],
                ['parameter_name' => 'Colony Count', 'unit' => 'CFU/mL', 'data_type' => 'text', 'reference_ranges' => ['range' => '<10,000'], 'display_order' => 3],
                ['parameter_name' => 'Sensitivity Report', 'unit' => '', 'data_type' => 'text', 'reference_ranges' => ['range' => 'N/A'], 'display_order' => 4],
            ];
            
            foreach ($urineCultureParams as $param) {
                LabTestParameter::firstOrCreate(
                    ['lab_test_id' => $urineCulture->id, 'parameter_name' => $param['parameter_name']],
                    $param
                );
            }
        }
    }
}
