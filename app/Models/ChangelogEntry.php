<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangelogEntry extends Model
{
    protected $connection = 'landlord';

    protected $fillable = ['release_id', 'category', 'description', 'sort_order'];

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }
}
