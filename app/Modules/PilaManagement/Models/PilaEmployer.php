<?php

namespace App\Modules\PilaManagement\Models;

use App\Modules\Patients\Models\Affiliate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PilaEmployer extends Model
{
    protected $table = 'pila_employers';

    protected $fillable = [
        'document_type',
        'document_number',
        'check_digit',
        'name',
        'address',
        'city',
        'department',
        'phone',
        'email',
        'payment_business_day',
        'is_active',
        'is_self_employed',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_business_day' => 'integer',
            'is_active' => 'boolean',
            'is_self_employed' => 'boolean',
        ];
    }

    public function pilaCredentials(): HasMany
    {
        return $this->hasMany(PilaCredential::class, 'employer_id');
    }

    public function portalCredentials(): HasMany
    {
        return $this->hasMany(PortalCredential::class, 'employer_id');
    }

    public function affiliations(): HasMany
    {
        return $this->hasMany(PilaAffiliation::class, 'employer_id');
    }

    public function affiliates(): HasManyThrough
    {
        return $this->hasManyThrough(
            Affiliate::class,
            PilaAffiliation::class,
            'employer_id',   // Foreign key on pila_affiliations...
            'id',            // Foreign key on affiliates...
            'id',            // Local key on pila_employers...
            'affiliate_id'   // Local key on pila_affiliations...
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

