<?php

namespace App\Modules\PilaManagement\Models;

use App\Modules\PilaManagement\Enums\CredentialAction;
use App\Modules\PilaManagement\Enums\CredentialKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredentialAuditLog extends Model
{
    protected $table = 'credential_audit_logs';

    protected $fillable = [
        'user_id',
        'credential_kind',
        'credential_id',
        'action',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'credential_kind' => CredentialKind::class,
            'action' => CredentialAction::class,
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class);
    }
}

