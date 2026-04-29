<?php

namespace Database\Seeders;

use App\Models\DocumentRequirement;
use Illuminate\Database\Seeder;

class DocumentRequirementSeeder extends Seeder
{
    public function run(): void
    {
        $requirements = [
            // All employees
            ['document_type' => 'cnic', 'label' => 'CNIC Copy', 'applicable_to' => 'all', 'is_mandatory' => true, 'has_expiry' => true, 'expiry_reminder_days' => 60, 'description' => 'National Identity Card copy (front & back)'],
            ['document_type' => 'photo', 'label' => 'Passport Size Photo', 'applicable_to' => 'all', 'is_mandatory' => true, 'has_expiry' => false],
            ['document_type' => 'contract', 'label' => 'Employment Contract', 'applicable_to' => 'all', 'is_mandatory' => true, 'has_expiry' => true, 'expiry_reminder_days' => 30],

            // Medical staff
            ['document_type' => 'pmdc', 'label' => 'PMDC Registration', 'applicable_to' => 'medical', 'is_mandatory' => true, 'has_expiry' => true, 'expiry_reminder_days' => 60, 'description' => 'Pakistan Medical & Dental Council registration certificate'],
            ['document_type' => 'medical_degree', 'label' => 'Medical Degree (MBBS/BDS)', 'applicable_to' => 'medical', 'is_mandatory' => true, 'has_expiry' => false],
            ['document_type' => 'specialization', 'label' => 'Specialization Certificate', 'applicable_to' => 'medical', 'is_mandatory' => false, 'has_expiry' => false],

            // Nursing staff
            ['document_type' => 'pnc', 'label' => 'PNC Registration', 'applicable_to' => 'nursing', 'is_mandatory' => true, 'has_expiry' => true, 'expiry_reminder_days' => 60, 'description' => 'Pakistan Nursing Council registration'],
            ['document_type' => 'nursing_degree', 'label' => 'Nursing Degree/Diploma', 'applicable_to' => 'nursing', 'is_mandatory' => true, 'has_expiry' => false],

            // Technical staff
            ['document_type' => 'technical_cert', 'label' => 'Technical Certification', 'applicable_to' => 'technical', 'is_mandatory' => true, 'has_expiry' => true, 'expiry_reminder_days' => 30],
        ];

        foreach ($requirements as $data) {
            DocumentRequirement::firstOrCreate(
                ['document_type' => $data['document_type'], 'applicable_to' => $data['applicable_to']],
                $data
            );
        }
    }
}
