<?php

namespace App\Modules\PilaManagement\Models;

use Illuminate\Database\Eloquent\Model;

class PilaCotizanteType extends Model
{
    protected $table = 'pila_cotizante_types';

    protected $fillable = [
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
}

