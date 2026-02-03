<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabSample extends Model
{
    protected $fillable = [
        'sample_id', 'lab_order_id', 'sample_type', 'status',
        'collected_at', 'received_at', 'collected_by', 'received_by',
        'collection_notes', 'rejection_reason', 'storage_conditions'
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'received_at' => 'datetime',
        'storage_conditions' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($sample) {
            $sample->sample_id = 'S' . date('Ymd') . str_pad(
                (LabSample::whereDate('created_at', today())->count() + 1),
                4,
                '0',
                STR_PAD_LEFT
            );
        });
    }

    public function labOrder()
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function collectedBy()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}