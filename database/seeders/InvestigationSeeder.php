<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Investigation;

class InvestigationSeeder extends Seeder
{
    public function run(): void
    {
        $investigations = [
            // PATHOLOGY — Hematology
            ['code' => 'CBC001', 'name' => 'Complete Blood Count (CBC)', 'category' => 'hematology', 'sample_type' => 'blood', 'price' => 800, 'turnaround_time' => '2 hours', 'is_active' => true],
            ['code' => 'ESR001', 'name' => 'Erythrocyte Sedimentation Rate (ESR)', 'category' => 'hematology', 'sample_type' => 'blood', 'price' => 300, 'turnaround_time' => '1 hour', 'is_active' => true],
            ['code' => 'PT001', 'name' => 'Prothrombin Time (PT/INR)', 'category' => 'hematology', 'sample_type' => 'blood', 'price' => 600, 'turnaround_time' => '2 hours', 'is_active' => true],
            ['code' => 'APTT001', 'name' => 'Activated Partial Thromboplastin Time', 'category' => 'hematology', 'sample_type' => 'blood', 'price' => 600, 'turnaround_time' => '2 hours', 'is_active' => true],

            // PATHOLOGY — Biochemistry
            ['code' => 'BMP001', 'name' => 'Basic Metabolic Panel', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 1200, 'turnaround_time' => '4 hours', 'is_active' => true],
            ['code' => 'LFT001', 'name' => 'Liver Function Test (LFT)', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 1500, 'turnaround_time' => '6 hours', 'is_active' => true],
            ['code' => 'RFT001', 'name' => 'Renal Function Test (RFT)', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 1000, 'turnaround_time' => '4 hours', 'is_active' => true],
            ['code' => 'LIPID001', 'name' => 'Lipid Profile', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 1200, 'turnaround_time' => '6 hours', 'is_active' => true],
            ['code' => 'HBA1C001', 'name' => 'Hemoglobin A1c (HbA1c)', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 1000, 'turnaround_time' => '4 hours', 'is_active' => true],
            ['code' => 'TROP001', 'name' => 'Troponin I', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 2000, 'turnaround_time' => '2 hours', 'is_active' => true],
            ['code' => 'VITD001', 'name' => 'Vitamin D (25-OH)', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 2200, 'turnaround_time' => '24 hours', 'is_active' => true],
            ['code' => 'VITB12001', 'name' => 'Vitamin B12', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 1800, 'turnaround_time' => '24 hours', 'is_active' => true],
            ['code' => 'IRON001', 'name' => 'Iron Studies', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 1500, 'turnaround_time' => '12 hours', 'is_active' => true],
            ['code' => 'CALC001', 'name' => 'Calcium Total', 'category' => 'biochemistry', 'sample_type' => 'blood', 'price' => 400, 'turnaround_time' => '4 hours', 'is_active' => true],
            ['code' => 'URIN001', 'name' => 'Urinalysis (Complete)', 'category' => 'biochemistry', 'sample_type' => 'urine', 'price' => 400, 'turnaround_time' => '1 hour', 'is_active' => true],

            // PATHOLOGY — Immunology
            ['code' => 'TSH001', 'name' => 'Thyroid Stimulating Hormone (TSH)', 'category' => 'immunology', 'sample_type' => 'blood', 'price' => 1800, 'turnaround_time' => '24 hours', 'is_active' => true],
            ['code' => 'TFT001', 'name' => 'Thyroid Function Test (Complete)', 'category' => 'immunology', 'sample_type' => 'blood', 'price' => 2500, 'turnaround_time' => '24 hours', 'is_active' => true],
            ['code' => 'HBSAG001', 'name' => 'Hepatitis B Surface Antigen', 'category' => 'immunology', 'sample_type' => 'blood', 'price' => 1200, 'turnaround_time' => '24 hours', 'is_active' => true],
            ['code' => 'HCV001', 'name' => 'Hepatitis C Antibody', 'category' => 'immunology', 'sample_type' => 'blood', 'price' => 1500, 'turnaround_time' => '24 hours', 'is_active' => true],

            // PATHOLOGY — Microbiology
            ['code' => 'CULT001', 'name' => 'Blood Culture & Sensitivity', 'category' => 'microbiology', 'sample_type' => 'blood', 'price' => 2000, 'turnaround_time' => '72 hours', 'is_active' => true],
            ['code' => 'CULT002', 'name' => 'Urine Culture & Sensitivity', 'category' => 'microbiology', 'sample_type' => 'urine', 'price' => 1500, 'turnaround_time' => '48 hours', 'is_active' => true],

            // RADIOLOGY — X-Ray
            ['code' => 'XRAY001', 'name' => 'X-Ray Chest PA View', 'category' => 'x-ray', 'sample_type' => 'other', 'price' => 1500, 'turnaround_time' => '2 hours', 'is_active' => true],
            ['code' => 'XRAY002', 'name' => 'X-Ray Chest AP View', 'category' => 'x-ray', 'sample_type' => 'other', 'price' => 1500, 'turnaround_time' => '2 hours', 'is_active' => true],
            ['code' => 'XRAY003', 'name' => 'X-Ray Abdomen', 'category' => 'x-ray', 'sample_type' => 'other', 'price' => 1800, 'turnaround_time' => '2 hours', 'is_active' => true],
            ['code' => 'XRAY004', 'name' => 'X-Ray Spine (Cervical)', 'category' => 'x-ray', 'sample_type' => 'other', 'price' => 2000, 'turnaround_time' => '2 hours', 'is_active' => true],
            ['code' => 'XRAY005', 'name' => 'X-Ray Spine (Lumbar)', 'category' => 'x-ray', 'sample_type' => 'other', 'price' => 2000, 'turnaround_time' => '2 hours', 'is_active' => true],
            ['code' => 'XRAY006', 'name' => 'X-Ray Knee (Both)', 'category' => 'x-ray', 'sample_type' => 'other', 'price' => 2200, 'turnaround_time' => '2 hours', 'is_active' => true],
            ['code' => 'XRAY007', 'name' => 'X-Ray Hand', 'category' => 'x-ray', 'sample_type' => 'other', 'price' => 1500, 'turnaround_time' => '2 hours', 'is_active' => true],
            ['code' => 'XRAY008', 'name' => 'X-Ray Foot', 'category' => 'x-ray', 'sample_type' => 'other', 'price' => 1500, 'turnaround_time' => '2 hours', 'is_active' => true],

            // RADIOLOGY — Ultrasound
            ['code' => 'US001', 'name' => 'Ultrasound Abdomen & Pelvis', 'category' => 'ultrasound', 'sample_type' => 'other', 'price' => 3000, 'turnaround_time' => '4 hours', 'is_active' => true],
            ['code' => 'US002', 'name' => 'Ultrasound Obstetric', 'category' => 'ultrasound', 'sample_type' => 'other', 'price' => 2500, 'turnaround_time' => '4 hours', 'is_active' => true],
            ['code' => 'US003', 'name' => 'Ultrasound Thyroid', 'category' => 'ultrasound', 'sample_type' => 'other', 'price' => 2000, 'turnaround_time' => '4 hours', 'is_active' => true],
            ['code' => 'US004', 'name' => 'Ultrasound Breast', 'category' => 'ultrasound', 'sample_type' => 'other', 'price' => 2500, 'turnaround_time' => '4 hours', 'is_active' => true],

            // RADIOLOGY — CT Scan
            ['code' => 'CT001', 'name' => 'CT Scan Brain (Plain)', 'category' => 'ct-scan', 'sample_type' => 'other', 'price' => 8000, 'turnaround_time' => '6 hours', 'is_active' => true],
            ['code' => 'CT002', 'name' => 'CT Scan Brain (Contrast)', 'category' => 'ct-scan', 'sample_type' => 'other', 'price' => 12000, 'turnaround_time' => '6 hours', 'is_active' => true],
            ['code' => 'CT003', 'name' => 'CT Scan Chest', 'category' => 'ct-scan', 'sample_type' => 'other', 'price' => 10000, 'turnaround_time' => '6 hours', 'is_active' => true],
            ['code' => 'CT004', 'name' => 'CT Scan Abdomen & Pelvis', 'category' => 'ct-scan', 'sample_type' => 'other', 'price' => 12000, 'turnaround_time' => '6 hours', 'is_active' => true],

            // RADIOLOGY — MRI
            ['code' => 'MRI001', 'name' => 'MRI Brain (Plain)', 'category' => 'mri', 'sample_type' => 'other', 'price' => 15000, 'turnaround_time' => '24 hours', 'is_active' => true],
            ['code' => 'MRI002', 'name' => 'MRI Brain (Contrast)', 'category' => 'mri', 'sample_type' => 'other', 'price' => 20000, 'turnaround_time' => '24 hours', 'is_active' => true],
            ['code' => 'MRI003', 'name' => 'MRI Spine (Cervical)', 'category' => 'mri', 'sample_type' => 'other', 'price' => 18000, 'turnaround_time' => '24 hours', 'is_active' => true],
            ['code' => 'MRI004', 'name' => 'MRI Spine (Lumbar)', 'category' => 'mri', 'sample_type' => 'other', 'price' => 18000, 'turnaround_time' => '24 hours', 'is_active' => true],
            ['code' => 'MRI005', 'name' => 'MRI Knee', 'category' => 'mri', 'sample_type' => 'other', 'price' => 16000, 'turnaround_time' => '24 hours', 'is_active' => true],

            // CARDIOLOGY — Cardiac Diagnostics
            ['code' => 'ECG001', 'name' => 'Electrocardiogram (ECG/EKG)', 'category' => 'cardiac-diagnostics', 'sample_type' => 'other', 'price' => 800, 'turnaround_time' => '1 hour', 'is_active' => true],
            ['code' => 'ECHO001', 'name' => 'Echocardiography (2D Echo)', 'category' => 'cardiac-diagnostics', 'sample_type' => 'other', 'price' => 5000, 'turnaround_time' => '4 hours', 'is_active' => true],
            ['code' => 'STRESS001', 'name' => 'Stress Test (Treadmill)', 'category' => 'cardiac-diagnostics', 'sample_type' => 'other', 'price' => 4000, 'turnaround_time' => '2 hours', 'is_active' => true],
            ['code' => 'HOLTER001', 'name' => 'Holter Monitoring (24 Hour)', 'category' => 'cardiac-diagnostics', 'sample_type' => 'other', 'price' => 6000, 'turnaround_time' => '48 hours', 'is_active' => true],
        ];

        foreach ($investigations as $data) {
            Investigation::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
