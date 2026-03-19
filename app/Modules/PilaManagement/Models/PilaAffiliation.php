<?php

namespace App\Modules\PilaManagement\Models;

use App\Modules\PilaManagement\Services\DeadlineService;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('payment_status', 'overdue');
    }

    public function scopeByOperator(Builder $query, ?string $operator): Builder
    {
        $operator = $operator !== null ? trim($operator) : null;
        if ($operator === null || $operator === '') {
            return $query;
        }

        return $query->where('pila_operator', $operator);
    }

    /**
     * Fecha límite de pago calculada (RN-04): se deriva, no se almacena.
     *
     * Usa el período registrado en `last_payment_period` si existe (AAAAMM),
     * si no, usa el período actual (año/mes de hoy).
     */
    public function getDeadlineAttribute(): ?\Carbon\CarbonInterface
    {
        $year = (int) now()->format('Y');
        $month = (int) now()->format('n');

        $period = (string) ($this->last_payment_period ?? '');
        if (preg_match('/^\d{6}$/', $period) === 1) {
            $year = (int) substr($period, 0, 4);
            $month = (int) substr($period, 4, 2);
        }

        $svc = app(DeadlineService::class);

        $paymentDay = $this->employer?->payment_business_day;
        if (is_int($paymentDay) && $paymentDay >= 2 && $paymentDay <= 16) {
            return $svc->dueDateForPeriodByPaymentDay($year, $month, $paymentDay);
        }

        $doc = (string) ($this->employer?->document_number ?? '');
        if ($doc !== '') {
            return $svc->dueDateForPeriod($year, $month, $doc);
        }

        return null;
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

    /**
     * Query de auditoría de credenciales para esta afiliación (solo PILA/PORTAL relacionadas).
     */
    public function credentialLogs()
    {
        $employerId = $this->employer_id;
        $affiliateId = $this->affiliate_id;

        $pilaCredentialIds = $employerId
            ? PilaCredential::query()->where('employer_id', $employerId)->pluck('id')
            : collect([]);

        $portalCredentialIds = PortalCredential::query()
            ->where('affiliate_id', $affiliateId)
            ->when($employerId, fn ($q) => $q->orWhere('employer_id', $employerId))
            ->pluck('id');

        $query = CredentialAuditLog::query()->with('user');

        if ($pilaCredentialIds->isEmpty() && $portalCredentialIds->isEmpty()) {
            return $query->whereRaw('1=0');
        }

        $query->where(function ($q) use ($pilaCredentialIds, $portalCredentialIds) {
            $hasAny = false;

            if ($pilaCredentialIds->isNotEmpty()) {
                $q->where(function ($q2) use ($pilaCredentialIds) {
                    $q2->where('credential_kind', \App\Modules\PilaManagement\Enums\CredentialKind::PILA->value)
                        ->whereIn('credential_id', $pilaCredentialIds);
                });
                $hasAny = true;
            }

            if ($portalCredentialIds->isNotEmpty()) {
                if ($hasAny) {
                    $q->orWhere(function ($q2) use ($portalCredentialIds) {
                        $q2->where('credential_kind', \App\Modules\PilaManagement\Enums\CredentialKind::PORTAL->value)
                            ->whereIn('credential_id', $portalCredentialIds);
                    });
                } else {
                    $q->where(function ($q2) use ($portalCredentialIds) {
                        $q2->where('credential_kind', \App\Modules\PilaManagement\Enums\CredentialKind::PORTAL->value)
                            ->whereIn('credential_id', $portalCredentialIds);
                    });
                }
            }
        });

        return $query;
    }
}

