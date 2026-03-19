<?php

namespace App\Modules\PilaManagement\Models;

use Illuminate\Database\Eloquent\Model;

class PilaRiskClass extends Model
{
    protected $table = 'pila_risk_classes';

    protected $fillable = [
        'level',
        'class_name',
        'description',
        'rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'rate' => 'decimal:5',
            'is_active' => 'boolean',
        ];
    }
}

