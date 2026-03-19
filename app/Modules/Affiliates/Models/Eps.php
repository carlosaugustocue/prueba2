<?php

namespace App\Modules\Affiliates\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Eps extends Model
{
    protected $table = 'eps';

    protected $fillable = [
        'name',
        'code',
        'phone',
        'email',
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
        return $this->hasMany(\App\Modules\SocialSecurity\Models\SocialSecurityProfile::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
