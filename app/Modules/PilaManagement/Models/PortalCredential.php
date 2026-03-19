<?php

namespace App\Modules\PilaManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalCredential extends Model
{
    protected $table = 'portal_credentials';

    protected $fillable = [
        'employer_id',
        'affiliate_id',
        'entity_type',
        'eps_id',
        'afp_id',
        'arp_id',
        'ccf_id',
        'username',
        'password_encrypted',
        'is_active',
        'is_not_applicable',
        'password_updated_at',
    ];

    protected $hidden = [
        'password_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_not_applicable' => 'boolean',
            'password_updated_at' => 'datetime',
        ];
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(PilaEmployer::class, 'employer_id');
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Patients\Models\Affiliate::class, 'affiliate_id');
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

