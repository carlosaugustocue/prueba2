<?php

namespace App\Modules\SocialSecurity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class OperatorCredential extends Model
{
    protected $table = 'operator_credentials';

    protected $fillable = [
        'affiliate_id',
        'provider_type',
        'credentials',
    ];

    protected $hidden = [
        'encrypted_credentials',
    ];

    public static function providerTypes(): array
    {
        return config('services.social_security.provider_types', []);
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Patients\Models\Affiliate::class);
    }

    public function getProviderTypeLabelAttribute(): string
    {
        return __('social_security.provider_type.' . $this->provider_type);
    }

    public function setCredentialsAttribute(array $value): void
    {
        $this->attributes['encrypted_credentials'] = Crypt::encryptString(json_encode($value));
    }

    public function getCredentialsAttribute(): ?array
    {
        if (empty($this->attributes['encrypted_credentials'])) {
            return null;
        }
        try {
            return json_decode(Crypt::decryptString($this->attributes['encrypted_credentials']), true);
        } catch (\Throwable) {
            return null;
        }
    }
}
