<?php

namespace App\Modules\PilaManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilaCredential extends Model
{
    protected $table = 'pila_credentials';

    protected $fillable = [
        'employer_id',
        'operator',
        'username',
        'password_encrypted',
        'is_active',
        'password_updated_at',
    ];

    protected $hidden = [
        'password_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'password_updated_at' => 'datetime',
        ];
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(PilaEmployer::class, 'employer_id');
    }
}

