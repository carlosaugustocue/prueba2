<?php

namespace App\Modules\PilaManagement\Services;

use App\Modules\PilaManagement\Enums\CredentialAction;
use App\Modules\PilaManagement\Enums\CredentialKind;
use App\Modules\PilaManagement\Models\CredentialAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class CredentialService
{
    public function encrypt(string $plain): string
    {
        return Crypt::encryptString($plain);
    }

    /**
     * Registra auditoría obligatoria para mutaciones/lecturas de credenciales.
     *
     * Nota: se registra antes/después del cifrado según el flujo del controlador;
     * este método solo encapsula la creación del log.
     */
    public function audit(
        CredentialKind $kind,
        int $credentialId,
        CredentialAction $action,
        ?int $userId = null,
        ?Request $request = null,
        array $metadata = []
    ): void {
        CredentialAuditLog::create([
            'user_id' => $userId,
            'credential_kind' => $kind,
            'credential_id' => $credentialId,
            'action' => $action,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    /**
     * Desencripta y registra auditoría obligatoria antes de devolver el valor.
     */
    public function decryptAndAudit(
        CredentialKind $kind,
        int $credentialId,
        string $encrypted,
        CredentialAction $action = CredentialAction::VIEWED,
        ?int $userId = null,
        ?Request $request = null,
        array $metadata = []
    ): string {
        $this->audit($kind, $credentialId, $action, $userId, $request, $metadata);

        return Crypt::decryptString($encrypted);
    }

    public function encryptJson(array $plain): string
    {
        return $this->encrypt(json_encode($plain, JSON_UNESCAPED_UNICODE));
    }
}

