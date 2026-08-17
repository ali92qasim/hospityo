<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class DoctorShareRule extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'doctor_id',
        'service_id',
        'lab_test_id',
        'imaging_study_id',
        'investigation_scope',
        'share_type',
        'share_value',
        'applies_to',
        'is_active',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'share_value' => 'decimal:2',
        'is_active'   => 'boolean',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'doctor_share_rule_service');
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    public function imagingStudy(): BelongsTo
    {
        return $this->belongsTo(ImagingStudy::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shareItems(): HasMany
    {
        return $this->hasMany(DoctorShareItem::class, 'rule_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * Rules that apply to a given bill line category and parent bill type.
     * Lab/imaging lines may also match visit-type rules scoped via investigation_scope.
     */
    public function scopeForBillContext(Builder $q, string $itemCategory, ?string $billType = null): Builder
    {
        return $q->where(function (Builder $sub) use ($itemCategory, $billType) {
            $sub->where('applies_to', 'all')
                ->orWhere('applies_to', $itemCategory);

            if ($billType && $billType !== $itemCategory) {
                if (in_array($itemCategory, ['lab', 'imaging'], true)) {
                    $sub->orWhere(function (Builder $narrow) use ($billType, $itemCategory) {
                        $narrow->where('applies_to', $billType)
                            ->where('investigation_scope', $itemCategory);
                    });
                } else {
                    $sub->orWhere('applies_to', $billType);
                }
            }
        });
    }

    /**
     * @deprecated Use scopeForBillContext instead.
     */
    public function scopeForBillType(Builder $q, string $billType): Builder
    {
        return $this->scopeForBillContext($q, $billType);
    }

    public function hasSpecificScope(): bool
    {
        if ($this->relationLoaded('services')) {
            if ($this->services->isNotEmpty()) {
                return true;
            }
        } elseif ($this->services()->exists()) {
            return true;
        }

        return $this->service_id !== null
            || $this->lab_test_id !== null
            || $this->imaging_study_id !== null
            || in_array($this->investigation_scope, ['lab', 'imaging'], true);
    }

    public function investigationScopeLabel(): string
    {
        return match ($this->investigation_scope) {
            'lab' => 'Lab Tests Only',
            'imaging' => 'Imaging Only',
            default => 'All Lab & Imaging',
        };
    }

    public function scopeSummary(): string
    {
        $services = $this->relationLoaded('services')
            ? $this->services
            : $this->services()->orderBy('name')->get();

        if ($services->isNotEmpty()) {
            return $services->pluck('name')->join(', ');
        }

        if ($this->labTest) {
            return $this->labTest->name;
        }

        if ($this->imagingStudy) {
            return $this->imagingStudy->name;
        }

        if (in_array($this->investigation_scope, ['lab', 'imaging'], true)) {
            return $this->investigationScopeLabel();
        }

        if ($this->service) {
            return $this->service->name;
        }

        if ($this->doctor_id) {
            return 'All Services, Lab Tests & Imaging';
        }

        return 'All Services, Lab Tests & Imaging (global default)';
    }
}
