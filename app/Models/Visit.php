<?php

namespace App\Models;

use App\Services\VisitTypeDetailSyncService;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Visit extends Model
{
    use HasFactory, Auditable, UsesTenantConnection;

    protected $fillable = [
        'visit_no',
        'patient_id',
        'doctor_id',
        'visit_type',
        'status',
        'visit_datetime',
        'closed_at',
    ];

    protected $casts = [
        'visit_datetime' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($visit) {
            $prefix = match($visit->visit_type) {
                'opd' => 'OPD',
                'ipd' => 'IPD',
                'emergency' => 'EMR'
            };

            $visit->visit_no = $prefix . str_pad(
                (Visit::where('visit_type', $visit->visit_type)->max('id') ?? 0) + 1,
                5,
                '0',
                STR_PAD_LEFT
            );
        });

        static::created(function (Visit $visit) {
            if (config('visits.dual_write_enabled')) {
                VisitTypeDetailSyncService::createForVisit($visit);
            }
        });

        static::updated(function (Visit $visit) {
            if (config('visits.dual_write_enabled')) {
                VisitTypeDetailSyncService::syncLegacyToChild($visit, $visit->getChanges());
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @deprecated Use primaryDoctor() / careTeam() for IPD. Kept for OPD/emergency and legacy data. */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function careTeam(): HasMany
    {
        return $this->hasMany(IpdCareTeam::class)->active()->latest('added_at');
    }

    public function primaryDoctor(): HasOne
    {
        return $this->hasOne(IpdCareTeam::class)
            ->active()
            ->primary();
    }

    public function doctorVisitNotes(): HasMany
    {
        return $this->hasMany(IpdDoctorVisitNote::class)->latest('visited_at');
    }

    public function hasActiveCareTeam(): bool
    {
        if ($this->relationLoaded('careTeam')) {
            return $this->careTeam->isNotEmpty();
        }

        return $this->careTeam()->exists();
    }

    public function attendingDoctor(): ?Doctor
    {
        if ($this->visit_type === 'ipd') {
            $this->loadMissing('primaryDoctor.doctor');

            return $this->primaryDoctor?->doctor;
        }

        return $this->doctor;
    }

    public function assignedDoctor(): ?Doctor
    {
        if ($this->visit_type === 'ipd') {
            return $this->attendingDoctor();
        }

        return $this->doctor;
    }

    public function opdDetails(): HasOne
    {
        return $this->hasOne(OpdVisit::class, 'visit_id');
    }

    public function ipdDetails(): HasOne
    {
        return $this->hasOne(IpdVisit::class, 'visit_id');
    }

    public function emergencyDetails(): HasOne
    {
        return $this->hasOne(EmergencyVisit::class, 'visit_id');
    }

    public function classHistories(): HasMany
    {
        return $this->hasMany(VisitClassHistory::class);
    }

    public function typeDetails(): HasOne
    {
        return match ($this->visit_type) {
            'opd' => $this->opdDetails(),
            'ipd' => $this->ipdDetails(),
            'emergency' => $this->emergencyDetails(),
            default => throw new \InvalidArgumentException("Unknown visit type: {$this->visit_type}"),
        };
    }

    public function queuePriority(): string
    {
        if ($this->visit_type !== 'opd') {
            return 'medium';
        }

        return $this->opdDetails?->queue_priority ?? 'medium';
    }

    public function readsTypeDetailFromChild(): bool
    {
        return (bool) config("visits.read_from_child.{$this->visit_type}", false);
    }

    public function typeDetailForRead(): ?Model
    {
        if (! $this->readsTypeDetailFromChild()) {
            return null;
        }

        return match ($this->visit_type) {
            'opd' => $this->opdDetails,
            'ipd' => $this->ipdDetails,
            'emergency' => $this->emergencyDetails,
            default => null,
        };
    }

    public function vitalSigns(): HasOne
    {
        return $this->hasOne(VitalSign::class)->latestOfMany();
    }

    public function allVitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class)->latest();
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function currentConsultation(): HasOne
    {
        return $this->hasOne(Consultation::class)->where('is_current', true);
    }

    public function consultation(): HasOne
    {
        return $this->currentConsultation();
    }

    public function testOrders(): HasMany
    {
        return $this->hasMany(TestOrder::class);
    }

    public function admission(): HasOne
    {
        return $this->hasOne(Admission::class);
    }

    public function triage(): HasOne
    {
        return $this->hasOne(Triage::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function labOrders(): HasMany
    {
        return $this->hasMany(InvestigationOrder::class);
    }

    public function investigationOrders(): HasMany
    {
        return $this->hasMany(InvestigationOrder::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function draftBill(): HasOne
    {
        return $this->hasOne(Bill::class)
            ->where('status', 'draft')
            ->where('bill_type', 'ipd')
            ->latestOfMany();
    }

    public function ipdGpeRecords(): HasMany
    {
        return $this->hasMany(IpdGpeRecord::class)->latest();
    }
}
