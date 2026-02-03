<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'tax_number',
        'payment_terms',
        'status',
        'notes'
    ];

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'supplier', 'name');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}