<?php

namespace App\Modules\PilaManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PilaSocialEntity extends Model
{
    protected $table = 'pila_social_entities';

    protected $fillable = [
        'type',
        'code',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function portalCredentials(): HasMany
    {
        return $this->hasMany(PortalCredential::class, 'entity_id');
    }
}

