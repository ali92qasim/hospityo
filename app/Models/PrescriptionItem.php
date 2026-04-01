<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class PrescriptionItem extends Model
{
    use HasFactory, Auditable, UsesTenantConnection;

    protected $fillable = [
        'prescription_id',
        'medicine_id',
        'prescription_instruction_id',
        'quantity',
        'dosage',
        'frequency',
        'duration',
        'instructions',
        'unit_price',
        'total_price'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }

    public function prescriptionInstruction()
    {
        return $this->belongsTo(PrescriptionInstruction::class);
    }
}
