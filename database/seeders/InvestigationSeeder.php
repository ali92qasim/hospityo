<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Investigation;

class InvestigationSeeder extends Seeder
{
    public function run(): void
    {
        $investigations = [
            // PATHOLOGY TESTS
            // Hematology
            ['code' => 'CBC001', 'name' => 'Complete Blood Count (CBC)', 'type' => 'pathology', 'category' => 'hematology', 'sample_type' => 'blood', 'price' => 800, 'turnaround_time' => 2, 'is_active' => true],
            ['code' => 'ESR001', 'name' => 'Erythrocyte Sedimentation Rate (ESR)', 'type' => 'pathology', 'category' => 'hematology', 'sample_type' => 'blood', 'price' => 300, 'turnaround_time' => 1, 'is_active' => true],
            ['code' => 'PT001', 'name' => 'Prothrombin Time (PT/INR)', 'type' => 'pathology', 'category' => 'hematology', 'sample_type' => 'blood', 'price' => 600, 'turnaround_time' => 2, 'is_active' => true],
            ['code' => 'APTT001', 'name' => 'Activated Partial Thromboplastin Time', 'type' => 'pathology', 'category' => 'hematology', 'sample_type' => 'blood', 'price' => 600, 'turnaround_time' => 2, 'is_active' => true],
            
            // Biochemistry
            ['code' => 'BMP001', 'name' => 'Basic Metabolic Panel', 'type' => 'pathology', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 1200, 'turnaround_time' => 4, 'is_active' => true],
            ['code' => 'LFT001', 'name' => 'Liver Function Test (LFT)', 'type' => 'pathology', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 1500, 'turnaround_time' => 6, 'is_active' => true],
            ['code' => 'RFT001', 'name' => 'Renal Function Test (RFT)', 'type' => 'pathology', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 1000, 'turnaround_time' => 4, 'is_active' => true],
            ['code' => 'LIPID001', 'name' => 'Lipid Profile', 'type' => 'pathology', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 1200, 'turnaround_time' => 6, 'is_active' => true],
            ['code' => 'HBA1C001', 'name' => 'Hemoglobin A1c (HbA1c)', 'type' => 'pathology', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 1000, 'turnaround_time' => 4, 'is_active' => true],
            ['code' => 'TROP001', 'name' => 'Troponin I', 'type' => 'pathology', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 2000, 'turnaround_time' => 2, 'is_active' => true],
            ['code' => 'VITD001', 'name' => 'Vitamin D (25-OH)', 'type' => 'pathology', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 2200, 'turnaround_time' => 24, 'is_active' => true],
            ['code' => 'VITB12001', 'name' => 'Vitamin B12', 'type' => 'pathology', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 1800, 'turnaround_time' => 24, 'is_active' => true],
            ['code' => 'IRON001', 'name' => 'Iron Studies', 'type' => 'pathology', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 1500, 'turnaround_time' => 12, 'is_active' => true],
            ['code' => 'CALC001', 'name' => 'Calcium Total', 'type' => 'pathology', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 400, 'turnaround_time' => 4, 'is_active' => true],
            ['code' => 'URIN001', 'name' => 'Urinalysis (Complete)', 'type' => 'pathology', 'category' => 'biochemistry', 'sample_type' => 'urine', 'price' => 400, 'turnaround_time' => 1, 'is_active' => true],
            
            // Immunology
            ['code' => 'TSH001', 'name' => 'Thyroid Stimulating Hormone (TSH)', 'type' => 'pathology', 'category' => 'immunology', 'sample_type' => 'blood', 'price' => 1800, 'turnaround_time' => 24, 'is_active' => true],
            ['code' => 'TFT001', 'name' => 'Thyroid Function Test (Complete)', 'type' => 'pathology', 'category' => 'immunology', 'sample_type' => 'blood', 'price' => 2500, 'turnaround_time' => 24, 'is_active' => true],
            ['code' => 'HBSAG001', 'name' => 'Hepatitis B Surface Antigen', 'type' => 'pathology', 'category' => 'immunology', 'sample_type' => 'blood', 'price' => 1200, 'turnaround_time' => 24, 'is_active' => true],
            ['code' => 'HCV001', 'name' => 'Hepatitis C Antibody', 'type' => 'pathology', 'category' => 'immunology', 'sample_type' => 'blood', 'price' => 1500, 'turnaround_time' => 24, 'is_active' => true],
            
            // Microbiology
            ['code' => 'CULT001', 'name' => 'Blood Culture & Sensitivity', 'type' => 'pathology', 'category' => 'microbiology', 'sample_type' => 'blood', 'price' => 2000, 'turnaround_time' => 72, 'is_active' => true],
            ['code' => 'CULT002', 'name' => 'Urine Culture & Sensitivity', 'type' => 'pathology', 'category' => 'microbiology', 'sample_type' => 'urine', 'price' => 1500, 'turnaround_time' => 48, 'is_active' => true],
            
            // RADIOLOGY TESTS
            ['code' => 'XRAY001', 'name' => 'X-Ray Chest PA View', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 1500, 'turnaround_time' => 2, 'is_active' => true],
            ['code' => 'XRAY002', 'name' => 'X-Ray Chest AP View', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 1500, 'turnaround_time' => 2, 'is_active' => true],
            ['code' => 'XRAY003', 'name' => 'X-Ray Abdomen', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 1800, 'turnaround_time' => 2, 'is_active' => true],
            ['code' => 'XRAY004', 'name' => 'X-Ray Spine (Cervical)', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 2000, 'turnaround_time' => 2, 'is_active' => true],
            ['code' => 'XRAY005', 'name' => 'X-Ray Spine (Lumbar)', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 2000, 'turnaround_time' => 2, 'is_active' => true],
            ['code' => 'XRAY006', 'name' => 'X-Ray Knee (Both)', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 2200, 'turnaround_time' => 2, 'is_active' => true],
            ['code' => 'XRAY007', 'name' => 'X-Ray Hand', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 1500, 'turnaround_time' => 2, 'is_active' => true],
            ['code' => 'XRAY008', 'name' => 'X-Ray Foot', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 1500, 'turnaround_time' => 2, 'is_active' => true],
            ['code' => 'US001', 'name' => 'Ultrasound Abdomen & Pelvis', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 3000, 'turnaround_time' => 4, 'is_active' => true],
            ['code' => 'US002', 'name' => 'Ultrasound Obstetric', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 2500, 'turnaround_time' => 4, 'is_active' => true],
            ['code' => 'US003', 'name' => 'Ultrasound Thyroid', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 2000, 'turnaround_time' => 4, 'is_active' => true],
            ['code' => 'US004', 'name' => 'Ultrasound Breast', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 2500, 'turnaround_time' => 4, 'is_active' => true],
            ['code' => 'CT001', 'name' => 'CT Scan Brain (Plain)', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 8000, 'turnaround_time' => 6, 'is_active' => true],
            ['code' => 'CT002', 'name' => 'CT Scan Brain (Contrast)', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 12000, 'turnaround_time' => 6, 'is_active' => true],
            ['code' => 'CT003', 'name' => 'CT Scan Chest', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 10000, 'turnaround_time' => 6, 'is_active' => true],
            ['code' => 'CT004', 'name' => 'CT Scan Abdomen & Pelvis', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 12000, 'turnaround_time' => 6, 'is_active' => true],
            ['code' => 'MRI001', 'name' => 'MRI Brain (Plain)', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 15000, 'turnaround_time' => 24, 'is_active' => true],
            ['code' => 'MRI002', 'name' => 'MRI Brain (Contrast)', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 20000, 'turnaround_time' => 24, 'is_active' => true],
            ['code' => 'MRI003', 'name' => 'MRI Spine (Cervical)', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 18000, 'turnaround_time' => 24, 'is_active' => true],
            ['code' => 'MRI004', 'name' => 'MRI Spine (Lumbar)', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 18000, 'turnaround_time' => 24, 'is_active' => true],
            ['code' => 'MRI005', 'name' => 'MRI Knee', 'type' => 'radiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 16000, 'turnaround_time' => 24, 'is_active' => true],
            
            // CARDIOLOGY TESTS
            ['code' => 'ECG001', 'name' => 'Electrocardiogram (ECG/EKG)', 'type' => 'cardiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 800, 'turnaround_time' => 1, 'is_active' => true],
            ['code' => 'ECHO001', 'name' => 'Echocardiography (2D Echo)', 'type' => 'cardiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 5000, 'turnaround_time' => 4, 'is_active' => true],
            ['code' => 'STRESS001', 'name' => 'Stress Test (Treadmill)', 'type' => 'cardiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 4000, 'turnaround_time' => 2, 'is_active' => true],
            ['code' => 'HOLTER001', 'name' => 'Holter Monitoring (24 Hour)', 'type' => 'cardiology', 'category' => 'molecular', 'sample_type' => 'other', 'price' => 6000, 'turnaround_time' => 48, 'is_active' => true],
        ];

        foreach ($investigations as $data) {
            Investigation::firstOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
