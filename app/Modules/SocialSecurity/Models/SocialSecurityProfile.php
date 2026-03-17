<?php

namespace App\Modules\SocialSecurity\Models;

use App\Modules\SocialSecurity\Enums\ArpRiskClass;
use App\Modules\SocialSecurity\Enums\PaymentPeriodicity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialSecurityProfile extends Model
{
    protected $table = 'social_security_profiles';

    protected $fillable = [
        'affiliate_id',
        'client_type_id',
        'contributor_type_id',
        'ibc',
        'eps_id',
        'afp_id',
        'arp_id',
        'arp_risk_class',
        'ccf_id',
        'payer_id',
        'payment_operator_id',
        'payment_day',
        'payment_periodicity',
        'has_parafiscales',
        'accounting_registry_id',
        'observations',
    ];

    protected function casts(): array
    {
        return [
            'ibc' => 'decimal:2',
            'has_parafiscales' => 'boolean',
            'arp_risk_class' => ArpRiskClass::class,
            'payment_periodicity' => PaymentPeriodicity::class,
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

    public function afp(): BelongsTo
    {
        return $this->belongsTo(Afp::class);
    }

    public function arp(): BelongsTo
    {
        return $this->belongsTo(Arp::class);
    }

    public function ccf(): BelongsTo
    {
        return $this->belongsTo(Ccf::class);
    }

    public function paymentOperator(): BelongsTo
    {
        return $this->belongsTo(PaymentOperator::class);
    }

    public function clientType(): BelongsTo
    {
        return $this->belongsTo(ClientType::class);
    }

    public function contributorType(): BelongsTo
    {
        return $this->belongsTo(ContributorType::class);
    }

    public function accountingRegistry(): BelongsTo
    {
        return $this->belongsTo(AccountingRegistry::class);
    }
}
