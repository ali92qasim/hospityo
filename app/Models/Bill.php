<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    protected $fillable = [
        'patient_id',
        'visit_id',
        'bill_number',
        'bill_date',
        'bill_type',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'due_amount',
        'status',
        'payment_method',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'bill_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2'
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function billItems(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->billItems->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        $this->subtotal = $subtotal;
        $this->total_amount = $subtotal + $this->tax_amount - $this->discount_amount;
        $this->due_amount = $this->total_amount - $this->paid_amount;
        $this->save();
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'paid' => 'green',
            'partial' => 'yellow',
            'pending' => 'red',
            default => 'gray'
        };
    }
}
