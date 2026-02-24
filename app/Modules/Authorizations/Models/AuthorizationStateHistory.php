<?php

namespace App\Modules\Authorizations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorizationStateHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'authorization_id',
        'from_status',
        'to_status',
        'user_id',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function authorization(): BelongsTo
    {
        return $this->belongsTo(Authorization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class);
    }
}
