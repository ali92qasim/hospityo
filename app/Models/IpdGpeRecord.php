<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class IpdGpeRecord extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'visit_id',
        'doctor_id',
        'recorded_by',
        'gpe_chest',
        'gpe_abdomen',
        'gpe_cvs',
        'gpe_cns',
        'gpe_pupils',
        'gpe_conjunctiva',
        'gpe_nails',
        'gpe_throat',
        'gpe_sclera',
        'gpe_gcs',
        'remarks',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return array<string, string|null> */
    public function systemFindings(): array
    {
        return [
            'Chest'       => $this->gpe_chest,
            'Abdomen'     => $this->gpe_abdomen,
            'CVS'         => $this->gpe_cvs,
            'CNS'         => $this->gpe_cns,
            'Pupils'      => $this->gpe_pupils,
            'Conjunctiva' => $this->gpe_conjunctiva,
            'Nails'       => $this->gpe_nails,
            'Throat'      => $this->gpe_throat,
            'Sclera'      => $this->gpe_sclera,
            'GCS'         => $this->gpe_gcs,
        ];
    }

    public function hasAnyFinding(): bool
    {
        foreach ($this->systemFindings() as $value) {
            if (filled($value)) {
                return true;
            }
        }

        return filled($this->remarks);
    }
}
