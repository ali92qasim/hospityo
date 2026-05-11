<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class LabTestParameter extends Model
{
    use Auditable, UsesTenantConnection;

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
        return $this->belongsTo(Investigation::class, 'lab_test_id');
    }

    public function investigation()
    {
        return $this->belongsTo(Investigation::class, 'lab_test_id');
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

        return $ranges['normal'] ?? $ranges['range'] ?? $this->reference_ranges ?? '';
    }

    public function calculateFlag($value, $patientAge = null, $patientGender = null)
    {
        if (!is_numeric($value)) return 'N';

        $range = $this->getReferenceRange($patientAge, $patientGender);
        if (!$range) return 'N';

        // Normalise dashes: en-dash (–) and em-dash (—) → plain hyphen
        $range = str_replace(["\xe2\x80\x93", "\xe2\x80\x94", '–', '—'], '-', $range);

        // If the range contains gender/age prefixes (e.g. "male:13.5-17.5, female:12.0-15.5"),
        // try to extract the segment that matches the patient's gender first.
        if (str_contains($range, ':')) {
            $segment = $this->extractGenderSegment($range, $patientGender);
            if ($segment !== null) {
                $range = $segment;
            }
        }

        // Match the first "number - number" pair (spaces around dash are optional)
        if (!preg_match('/(\d+\.?\d*)\s*-\s*(\d+\.?\d*)/', $range, $matches)) {
            return 'N';
        }

        $low      = floatval($matches[1]);
        $high     = floatval($matches[2]);
        $numValue = floatval($value);

        // Check critical values first
        if ($this->critical_values) {
            $critical = $this->critical_values;
            if (isset($critical['low']) && $numValue <= floatval(str_replace('<', '', $critical['low']))) {
                return 'LL';
            }
            if (isset($critical['high']) && $numValue >= floatval(str_replace('>', '', $critical['high']))) {
                return 'HH';
            }
        }

        if ($numValue < $low)  return 'L';
        if ($numValue > $high) return 'H';

        return 'N';
    }

    /**
     * Given a range string like "male:13.5-17.5, female:12.0-15.5",
     * return the numeric range portion that matches the patient's gender.
     * Falls back to the first segment if gender is unknown.
     */
    private function extractGenderSegment(string $range, ?string $patientGender): ?string
    {
        // Split on commas that separate gender segments
        $segments = preg_split('/,\s*/', $range);

        $first = null;
        foreach ($segments as $segment) {
            if (!str_contains($segment, ':')) {
                continue;
            }

            [$label, $value] = explode(':', $segment, 2);
            $label = strtolower(trim($label));
            $value = trim($value);

            if ($first === null) {
                $first = $value;
            }

            if ($patientGender && str_starts_with($label, strtolower($patientGender[0]))) {
                return $value;
            }
        }

        // No gender match — return the first segment's value so we still get a range
        return $first;
    }
}
