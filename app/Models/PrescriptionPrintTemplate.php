<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class PrescriptionPrintTemplate extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'doctor_id',
        'name',
        'mode',
        'paper_size',
        'orientation',
        'background_image_path',
        'is_active',
        'rx_start_y',
        'rx_row_height',
        'rx_max_rows',
        'rx_overflow_policy',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rx_start_y' => 'float',
        'rx_row_height' => 'float',
        'rx_max_rows' => 'integer',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(PrescriptionPrintField::class, 'template_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function resolvePrintTemplate(?int $doctorId): ?self
    {
        if ($doctorId) {
            $forDoctor = static::query()
                ->active()
                ->where('doctor_id', $doctorId)
                ->first();

            if ($forDoctor) {
                return $forDoctor;
            }
        }

        return static::query()
            ->active()
            ->whereNull('doctor_id')
            ->first();
    }
}
