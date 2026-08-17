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

class ImagingOrder extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'imaging_orders';

    protected $fillable = [
        'order_number', 'share_token', 'patient_id', 'visit_id', 'doctor_id',
        'priority', 'status', 'ordered_at', 'completed_at',
        'clinical_notes', 'special_instructions',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ImagingOrder $order) {
            if (empty($order->order_number)) {
                $lastId = static::query()->max('id') ?? 0;
                $order->order_number = 'IMG'.str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
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
        return $this->hasMany(ImagingOrderItem::class);
    }

    public function report(): HasOne
    {
        return $this->hasOne(ImagingReport::class);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function allowsSampleCollection(): bool
    {
        return false;
    }
}
