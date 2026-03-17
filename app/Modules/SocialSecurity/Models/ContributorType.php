<?php

namespace App\Modules\SocialSecurity\Models;

use Illuminate\Database\Eloquent\Model;

class ContributorType extends Model
{
    protected $table = 'contributor_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_dependent',
        'parafiscales_allowed',
        'health_applies',
        'pension_applies',
        'arl_applies',
        'ccf_applies',
        'is_proportional',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_dependent' => 'boolean',
            'parafiscales_allowed' => 'boolean',
            'health_applies' => 'boolean',
            'pension_applies' => 'boolean',
            'arl_applies' => 'boolean',
            'ccf_applies' => 'boolean',
            'is_proportional' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
