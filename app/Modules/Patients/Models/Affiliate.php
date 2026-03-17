<?php

namespace App\Modules\Patients\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Core\Traits\Searchable;
use App\Modules\Patients\Enums\AffiliateStatus;
use App\Modules\Patients\Enums\DocumentType;
use App\Modules\Patients\Enums\PatientType;
use App\Modules\Patients\Enums\RelationshipType;

class Affiliate extends Model
{
    use SoftDeletes, HasUuid, Searchable;

    protected $table = 'affiliates';

    protected $fillable = [
        'uuid',
        'document_type',
        'document_number',
        'document_issue_date',
        'first_name',
        'second_name',
        'last_name',
        'second_last_name',
        'phone',
        'phone_2',
        'whatsapp',
        'email',
        'address',
        'neighborhood',
        'city',
        'department',
        'patient_type',
        'holder_id',
        'relationship_type',
        'birth_date',
        'notes',
        'gender',
        'status',
        'created_by',
        'updated_by',
    ];

    protected array $searchable = [
        'first_name',
        'second_name',
        'last_name',
        'second_last_name',
        'document_number',
        'phone',
        'phone_2',
        'whatsapp',
        'neighborhood',
    ];

    public function scopeSearchByWords(Builder $query, string $search): Builder
    {
        $words = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return $query;
        }

        $columns = $this->getSearchableColumns();

        foreach ($words as $word) {
            $term = $word;
            $query->where(function (Builder $q) use ($columns, $term) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $term . '%');
                }
            });
        }

        return $query;
    }

    protected function casts(): array
    {
        return [
            'status' => AffiliateStatus::class,
            'document_type' => DocumentType::class,
            'patient_type' => PatientType::class,
            'relationship_type' => RelationshipType::class,
            'birth_date' => 'date',
            'document_issue_date' => 'date',
        ];
    }

    public function getFullNameAttribute(): string
    {
        $parts = [
            $this->first_name,
            $this->second_name,
            $this->last_name,
            $this->second_last_name,
        ];

        return trim(collect($parts)->filter()->join(' '));
    }

    public function socialSecurityProfile(): HasOne
    {
        return $this->hasOne(\App\Modules\SocialSecurity\Models\SocialSecurityProfile::class);
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class, 'holder_id');
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Affiliate::class, 'holder_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(\App\Modules\Appointments\Models\Appointment::class);
    }

    public function novelties(): HasMany
    {
        return $this->hasMany(\App\Modules\SocialSecurity\Models\Novelty::class);
    }

    public function operatorCredentials(): HasMany
    {
        return $this->hasMany(\App\Modules\SocialSecurity\Models\OperatorCredential::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(\App\Modules\SocialSecurity\Models\Payroll::class);
    }

    public function supportDocuments(): HasMany
    {
        return $this->hasMany(\App\Modules\SocialSecurity\Models\SupportDocument::class);
    }

    public function communicationLogs(): HasMany
    {
        return $this->hasMany(\App\Modules\SocialSecurity\Models\CommunicationLog::class);
    }

    public function getWhatsAppNumber(): ?string
    {
        return $this->whatsapp ?? $this->phone;
    }

    /**
     * Solo afiliados cuyo pagador directo es Serviconli (citas y autorizaciones se gestionan solo para estos).
     */
    public function scopeWhereServiconliAsPayer(Builder $query): Builder
    {
        return $query->whereHas('socialSecurityProfile', function (Builder $q) {
            $q->whereHas('payer', fn (Builder $p) => $p->where('is_serviconli', true));
        });
    }

    /**
     * Afiliados gestionados por Serviconli: con pagador Serviconli O con tipo de cliente SERVICONLI.
     * Usado en búsqueda de autorizaciones para incluir a quienes tienen "Tipo de cliente: SERVICONLI" en la ficha.
     */
    public function scopeWhereServiconliManaged(Builder $query): Builder
    {
        return $query->whereHas('socialSecurityProfile', function (Builder $q) {
            $q->where(function (Builder $inner) {
                $inner->whereHas('payer', fn (Builder $p) => $p->where('is_serviconli', true))
                    ->orWhereHas('clientType', fn (Builder $ct) => $ct->where('code', 'SERVICONLI'));
            });
        });
    }

    public function authorizations(): HasMany
    {
        return $this->hasMany(\App\Modules\Authorizations\Models\Authorization::class);
    }

    public function historiaClinica(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Modules\HistoriaClinica\Models\HistoriaClinica::class, 'affiliate_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(\App\Modules\Patients\Models\AffiliatePayment::class);
    }

    public function independentContracts(): HasMany
    {
        return $this->hasMany(\App\Modules\SocialSecurity\Models\IndependentContract::class);
    }
}
