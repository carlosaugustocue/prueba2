<?php

namespace App\Modules\PilaManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilaAffiliation extends Model
{
    protected $table = 'pila_affiliations';

    protected $fillable = [
        'affiliate_id',
        'employer_id',
        'cotizante_type_id',
        'pila_operator',
        'last_novelty_type',
        'last_novelty_date',
        'ibc',
        'pays_parafiscales',
        'self_employed',
        'risk_class_id',
        'eps_id',
        'afp_id',
        'arp_id',
        'ccf_id',
        'payment_periodicity',
        'billing_type',
        'last_document_number',
        'last_payment_period',
        'payment_status',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'last_novelty_date' => 'date',
            'ibc' => 'decimal:2',
            'pays_parafiscales' => 'boolean',
            'self_employed' => 'boolean',
            'is_current' => 'boolean',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Patients\Models\Affiliate::class);
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(PilaEmployer::class, 'employer_id');
    }

    public function cotizanteType(): BelongsTo
    {
        return $this->belongsTo(PilaCotizanteType::class, 'cotizante_type_id');
    }

    public function riskClass(): BelongsTo
    {
        return $this->belongsTo(PilaRiskClass::class, 'risk_class_id');
    }

    public function eps(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Patients\Models\Eps::class, 'eps_id');
    }

    public function afp(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\SocialSecurity\Models\Afp::class, 'afp_id');
    }

    public function arp(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\SocialSecurity\Models\Arp::class, 'arp_id');
    }

    public function ccf(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\SocialSecurity\Models\Ccf::class, 'ccf_id');
    }
}

