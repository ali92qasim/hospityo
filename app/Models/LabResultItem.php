<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabResultItem extends Model
{
    protected $fillable = [
        'lab_order_id', 'lab_test_parameter_id', 'value', 'unit',
        'flag', 'comment', 'entered_by', 'entered_at', 'verified_by', 'verified_at'
    ];

    protected $casts = [
        'entered_at' => 'datetime',
        'verified_at' => 'datetime'
    ];

    public function labOrder()
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function parameter()
    {
        return $this->belongsTo(LabTestParameter::class, 'lab_test_parameter_id');
    }

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isAbnormal()
    {
        return in_array($this->flag, ['H', 'L', 'HH', 'LL', 'A']);
    }

    public function isCritical()
    {
        return in_array($this->flag, ['HH', 'LL']);
    }

    public function getFlagColorClass()
    {
        return match($this->flag) {
            'HH', 'LL' => 'text-red-600 font-bold',
            'H', 'L' => 'text-orange-600',
            'A' => 'text-yellow-600',
            default => 'text-gray-600'
        };
    }
}