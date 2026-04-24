<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class TaxMapping extends Model
{
    use UsesTenantConnection;

    protected $fillable = ['tax_id', 'applicable_on', 'applicable_value'];

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }
}
