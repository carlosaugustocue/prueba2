<?php

namespace App\Modules\SocialSecurity\Models;

use App\Modules\Patients\Enums\DocumentType;
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
        'is_serviconli',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'is_active' => 'boolean',
            'is_serviconli' => 'boolean',
        ];
    }

    public function scopeServiconli($query)
    {
        return $query->where('is_serviconli', true);
    }

    public function socialSecurityProfiles(): HasMany
    {
        return $this->hasMany(SocialSecurityProfile::class, 'payer_id');
    }
}
