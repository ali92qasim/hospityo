<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class LabOrder extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'lab_orders';

    protected $fillable = [
        'order_number', 'share_token', 'patient_id', 'visit_id', 'doctor_id',
        'priority', 'status', 'ordered_at', 'sample_collected_at', 'completed_at',
        'clinical_notes', 'special_instructions',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'sample_collected_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (LabOrder $order) {
            if (empty($order->order_number)) {
                $lastId = static::query()->max('id') ?? 0;
                $order->order_number = 'LAB'.str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
            }

            if (empty($order->share_token)) {
                $order->share_token = static::generateShareToken();
            }
        });
    }

    public static function generateShareToken(): string
    {
        do {
            $token = Str::random(40);
        } while (static::query()->where('share_token', $token)->exists());

        return $token;
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabOrderItem::class);
    }

    public function sample(): HasOne
    {
        return $this->hasOne(LabSample::class, 'lab_order_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(LabResult::class, 'lab_order_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(LabResult::class, 'lab_order_id');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function allowsSampleCollection(): bool
    {
        return true;
    }

    public function ensureShareToken(): string
    {
        if (empty($this->share_token)) {
            $this->forceFill(['share_token' => static::generateShareToken()])->save();
        }

        return $this->share_token;
    }

    public function publicReportUrl(): string
    {
        return route('lab-report.show', $this->ensureShareToken());
    }
}
