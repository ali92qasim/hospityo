<?php

namespace App\Support;

final class PrescriptionPrintFieldCatalog
{
    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::labels());
    }

    /** @return list<string> */
    public static function coreKeys(): array
    {
        return ['patient_name', 'age', 'date', 'doctor_name'];
    }

    public static function isCore(string $key): bool
    {
        return in_array($key, self::coreKeys(), true);
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'hospital_name' => 'Hospital name',
            'hospital_address' => 'Hospital address',
            'hospital_phone' => 'Hospital phone',
            'hospital_email' => 'Hospital email',
            'doctor_name' => 'Doctor name',
            'doctor_qualification' => 'Doctor qualification',
            'doctor_specialization' => 'Doctor specialization',
            'doctor_registration_no' => 'Doctor registration (PMDC)',
            'patient_name' => 'Patient name',
            'age' => 'Age',
            'gender' => 'Gender',
            'age_gender' => 'Age / gender',
            'patient_phone' => 'Mobile',
            'patient_no' => 'Patient #',
            'visit_no' => 'Visit #',
            'date' => 'Date',
            'diagnosis' => 'Provisional diagnosis',
            'allergies' => 'Allergies',
            'presenting_complaints' => 'Presenting complaints',
            'patient_history' => 'Patient history',
            'investigations' => 'Investigations',
            'vital_bp' => 'BP',
            'vital_temp' => 'Temperature',
            'vital_pulse' => 'Pulse',
            'vital_spo2' => 'SpO₂',
            'vital_bsr' => 'BSR',
            'vital_weight' => 'Weight',
            'vital_height' => 'Height',
            'gpe_chest' => 'GPE chest',
            'gpe_abdomen' => 'GPE abdomen',
            'gpe_cvs' => 'GPE CVS',
            'gpe_cns' => 'GPE CNS',
            'gpe_pupils' => 'GPE pupils',
            'gpe_conjunctiva' => 'GPE conjunctiva',
            'gpe_nails' => 'GPE nails',
            'gpe_throat' => 'GPE throat',
            'gpe_sclera' => 'GPE sclera',
            'gpe_gcs' => 'GPE GCS',
            'instructions' => 'Instructions',
            'next_visit_date' => 'Next visit date',
        ];
    }
}
