<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Release extends Model
{
    protected $connection = 'landlord';

    protected $fillable = ['version', 'summary', 'released_at'];

    protected $casts = ['released_at' => 'datetime'];

    public function changelogEntries(): HasMany
    {
        return $this->hasMany(ChangelogEntry::class);
    }
}
