<?php

namespace App\Modules\SocialSecurity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialSecurityProfile extends Model
{
    protected $table = 'social_security_profiles';

    protected $fillable = [
        'affiliate_id',
        'client_type',
        'contributor_type',
        'ibc',
        'eps_id',
        'afp_name',
        'arp_name',
        'arp_risk_class',
        'ccf_name',
        'payer_id',
        'payment_operator',
        'payment_day',
        'payment_periodicity',
        'has_parafiscales',
        'accounting_registry',
        'observations',
    ];

    protected function casts(): array
    {
        return [
            'ibc' => 'decimal:2',
            'has_parafiscales' => 'boolean',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Patients\Models\Affiliate::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(Payer::class);
    }

    public function eps(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Patients\Models\Eps::class);
    }
}
