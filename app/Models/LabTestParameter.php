<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabTestParameter extends Model
{
    protected $fillable = [
        'lab_test_id', 'parameter_name', 'unit', 'data_type',
        'reference_ranges', 'critical_values', 'select_options',
        'is_calculated', 'calculation_formula', 'display_order', 'is_active'
    ];

    protected $casts = [
        'reference_ranges' => 'array',
        'critical_values' => 'array',
        'select_options' => 'array',
        'is_calculated' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function labTest()
    {
        return $this->belongsTo(LabTest::class);
    }

    public function resultItems()
    {
        return $this->hasMany(LabResultItem::class);
    }

    public function getReferenceRange($patientAge = null, $patientGender = null)
    {
        $ranges = $this->reference_ranges;
        
        if ($patientAge && $patientAge < 18 && isset($ranges['pediatric'])) {
            return $ranges['pediatric'];
        }
        
        if ($patientGender && isset($ranges[strtolower($patientGender)])) {
            return $ranges[strtolower($patientGender)];
        }
        
        return $ranges['normal'] ?? $ranges['range'] ?? '';
    }

    public function calculateFlag($value, $patientAge = null, $patientGender = null)
    {
        if (!is_numeric($value)) return 'N';
        
        $range = $this->getReferenceRange($patientAge, $patientGender);
        if (!$range || !preg_match('/(\d+\.?\d*)-(\d+\.?\d*)/', $range, $matches)) {
            return 'N';
        }
        
        $low = floatval($matches[1]);
        $high = floatval($matches[2]);
        $numValue = floatval($value);
        
        // Check critical values
        if ($this->critical_values) {
            $critical = $this->critical_values;
            if (isset($critical['low']) && $numValue <= floatval(str_replace('<', '', $critical['low']))) {
                return 'LL';
            }
            if (isset($critical['high']) && $numValue >= floatval(str_replace('>', '', $critical['high']))) {
                return 'HH';
            }
        }
        
        if ($numValue < $low) return 'L';
        if ($numValue > $high) return 'H';
        
        return 'N';
    }
}