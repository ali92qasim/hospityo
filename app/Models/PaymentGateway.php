<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class PaymentGateway extends Model
{
    protected $connection = 'landlord';

    protected $fillable = [
        'slug', 'name', 'logo', 'description',
        'is_enabled', 'mode', 'credentials', 'config_fields', 'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'config_fields' => 'array',
    ];

    /**
     * Encrypt credentials before saving.
     */
    public function setCredentialsAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['credentials'] = Crypt::encryptString(json_encode($value));
        } elseif (is_string($value) && !empty($value)) {
            // Already a string (possibly already encrypted) — store as-is
            $this->attributes['credentials'] = $value;
        } else {
            $this->attributes['credentials'] = null;
        }
    }

    /**
     * Decrypt credentials when reading.
     */
    public function getCredentialsAttribute($value): array
    {
        if (empty($value)) return [];

        try {
            $decrypted = Crypt::decryptString($value);
            return json_decode($decrypted, true) ?? [];
        } catch (\Exception $e) {
            // Fallback: try reading as plain JSON (unencrypted legacy data)
            try {
                $decoded = json_decode($value, true);
                return is_array($decoded) ? $decoded : [];
            } catch (\Exception $e2) {
                Log::warning('[PaymentGateway] Failed to decode credentials', ['id' => $this->id]);
                return [];
            }
        }
    }

    /**
     * Get a specific credential value.
     */
    public function getCredential(string $key, string $default = ''): string
    {
        return $this->credentials[$key] ?? $default;
    }

    /**
     * Scope to enabled gateways only.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Check if gateway is in sandbox mode.
     */
    public function isSandbox(): bool
    {
        return $this->mode === 'sandbox';
    }
}
