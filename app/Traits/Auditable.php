<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Fields that should never be logged.
     * Models can override this by defining their own $auditExclude property.
     */
    protected array $auditExclude = [
        'password',
        'remember_token',
        'updated_at',
    ];

    public static function bootAuditable()
    {
        static::created(function ($model) {
            $model->recordAudit('created');
        });

        static::updated(function ($model) {
            $model->recordAudit('updated');
        });

        static::deleted(function ($model) {
            $model->recordAudit('deleted');
        });

        // If using SoftDeletes
        if (in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses_recursive(static::class))) {
            static::restored(function ($model) {
                $model->recordAudit('restored');
            });
        }
    }

    protected function recordAudit(string $event): void
    {
        if (!Auth::check()) {
            return;
        }

        $oldValues = null;
        $newValues = null;

        if ($event === 'updated') {
            $changes = $this->getDirty();

            if (empty($changes)) {
                return; // Nothing actually changed
            }

            $changes = $this->filterExcludedFields($changes);

            if (empty($changes)) {
                return; // Only excluded fields changed
            }

            $oldValues = [];
            $newValues = [];

            foreach ($changes as $field => $newValue) {
                $oldValues[$field] = $this->getOriginal($field);
                $newValues[$field] = $newValue;
            }
        }

        if ($event === 'created') {
            $newValues = $this->filterExcludedFields($this->getAttributes());
        }

        if ($event === 'deleted') {
            $oldValues = $this->filterExcludedFields($this->getOriginal());
        }

        AuditLog::create([
            'user_id'        => Auth::id(),
            'hospital_id'    => Auth::user()->hospital_id ?? null, // Future SaaS safety
            'event'          => $event,
            'auditable_type' => get_class($this),
            'auditable_id'   => $this->getKey(),
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);
    }

    protected function filterExcludedFields(array $data): array
    {
        $excluded = $this->auditExclude ?? [];

        return array_diff_key($data, array_flip($excluded));
    }
}
