<?php

namespace App\Modules\SocialSecurity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payer extends Model
{
    protected $fillable = [
        'name',
        'document_type',
        'document_number',
        'address',
        'phone',
        'email',
        'contact_person',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function socialSecurityProfiles(): HasMany
    {
        return $this->hasMany(SocialSecurityProfile::class, 'payer_id');
    }
}
