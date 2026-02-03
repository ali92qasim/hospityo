<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

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

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'supplier', 'name');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}