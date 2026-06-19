<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class SurgeryTeamMember extends Model
{
    use UsesTenantConnection;

    protected $fillable = ['surgery_id', 'user_id', 'role'];

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(Surgery::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
