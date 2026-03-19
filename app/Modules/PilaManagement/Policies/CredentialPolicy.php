<?php

namespace App\Modules\PilaManagement\Policies;

use App\Modules\Auth\Models\User;
use App\Modules\Patients\Models\Affiliate;
use App\Modules\PilaManagement\Enums\CredentialAction;
use App\Modules\PilaManagement\Models\PilaAffiliation;
use App\Modules\PilaManagement\Models\PilaCredential;
use App\Modules\PilaManagement\Models\PortalCredential;
use Illuminate\Support\Facades\Schema;
use App\Modules\PilaManagement\Enums\CredentialKind;

class CredentialPolicy
{
    /**
     * Matriz (sección 12 doc):
     * - admin: true
     * - supervisor: true
     * - agent: true
     * - viewer: false (y por defecto cualquier otro rol)
     */
    public function viewUsername(User $user, $credential): bool
    {
        return in_array($user->role?->name, ['admin', 'supervisor', 'agent'], true);
    }

    /**
     * Ver contraseñas:
     * - admin/supervisor: true
     * - agent: solo credenciales cuyos afiliados (asignación) coinciden
     * - viewer: false
     */
    public function viewPassword(User $user, $credential): bool
    {
        $role = $user->role?->name;

        if (in_array($role, ['admin', 'supervisor'], true)) {
            return true;
        }

        if ($role !== 'agent') {
            return false;
        }

        return $this->agentCanViewCredentialPassword($user, $credential);
    }

    /**
     * Editar contraseñas:
     * - admin/supervisor: true
     * - agent/viewer: false
     */
    public function updateCredential(User $user, $credential): bool
    {
        return in_array($user->role?->name, ['admin', 'supervisor'], true);
    }

    /**
     * Ver log auditoría:
     * - admin/supervisor: true
     * - agent/viewer: false
     */
    public function viewAuditLog(User $user): bool
    {
        return in_array($user->role?->name, ['admin', 'supervisor'], true);
    }

    private function agentCanViewCredentialPassword(User $user, $credential): bool
    {
        // Agentes solo pueden ver contraseñas de "sus clientes".
        // En este proyecto la asignación se modela como created_by en `affiliates`.
        // Si existiera user_id (p.ej. por migraciones futuras), lo preferimos.
        $assignmentColumn = $this->affiliateAssignmentUserColumn();
        $agentUserId = $user->id;

        if ($credential instanceof PortalCredential) {
            // EPS/AFP (affiliate_id existe) y ARL/CCF (puede ser employer_id compartido).
            if (! empty($credential->affiliate_id)) {
                return Affiliate::query()
                    ->whereKey($credential->affiliate_id)
                    ->where($assignmentColumn, $agentUserId)
                    ->exists();
            }

            if (! empty($credential->employer_id)) {
                return PilaAffiliation::query()
                    ->where('employer_id', $credential->employer_id)
                    ->whereHas('affiliate', fn ($q) => $q->where($assignmentColumn, $agentUserId))
                    ->exists();
            }

            return false;
        }

        if ($credential instanceof PilaCredential) {
            return PilaAffiliation::query()
                ->where('employer_id', $credential->employer_id)
                ->whereHas('affiliate', fn ($q) => $q->where($assignmentColumn, $agentUserId))
                ->exists();
        }

        return false;
    }

    private function affiliateAssignmentUserColumn(): string
    {
        static $column = null;
        if ($column !== null) {
            return $column;
        }

        try {
            $column = Schema::hasColumn('affiliates', 'user_id') ? 'user_id' : 'created_by';
        } catch (\Throwable) {
            $column = 'created_by';
        }

        return $column;
    }
}

