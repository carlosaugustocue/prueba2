<?php

namespace App\Modules\PilaManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PilaManagement\Enums\CredentialAction;
use App\Modules\PilaManagement\Enums\CredentialKind;
use App\Modules\PilaManagement\Models\CredentialAuditLog;
use App\Modules\PilaManagement\Models\PilaAffiliation;
use App\Modules\PilaManagement\Models\PilaCredential;
use App\Modules\PilaManagement\Models\PortalCredential;
use App\Modules\PilaManagement\Requests\StorePilaCredentialRequest;
use App\Modules\PilaManagement\Requests\StorePortalCredentialRequest;
use App\Modules\PilaManagement\Requests\UpdateCredentialRequest;
use App\Modules\PilaManagement\Services\CredentialService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PilaCredentialController extends Controller
{
    /**
     * Lista credenciales relacionadas a una afiliación:
     * - PILA: por empleador
     * - Portales: por afiliado (y por empleador si aplica)
     */
    public function index(PilaAffiliation $affiliation): JsonResponse
    {
        $affiliation->load(['affiliate', 'employer']);

        $pila = null;
        if ($affiliation->employer_id) {
            $pila = PilaCredential::query()
                ->where('employer_id', $affiliation->employer_id)
                ->where('is_active', true)
                ->orderBy('operator')
                ->get([
                    'id',
                    'operator',
                    'username',
                    'is_active',
                    'password_updated_at',
                    'created_at',
                    'updated_at',
                    DB::raw('password_encrypted IS NOT NULL as has_password'),
                ]);
        }

        $portals = PortalCredential::query()
            ->where(function ($q) use ($affiliation) {
                $q->where('affiliate_id', $affiliation->affiliate_id);
                if ($affiliation->employer_id) {
                    $q->orWhere('employer_id', $affiliation->employer_id);
                }
            })
            ->where('is_active', true)
            ->orderBy('entity_type')
            ->get([
                'id',
                'entity_type',
                'username',
                'is_active',
                'is_not_applicable',
                'password_updated_at',
                'created_at',
                'updated_at',
                DB::raw('password_encrypted IS NOT NULL as has_password'),
            ]);

        // Enforce via Policy: roles no autorizados no deben ver "usuarios" de credenciales.
        if ($pila) {
            $pila->each(fn ($cred) => $this->authorize('viewUsername', $cred));
        }
        $portals->each(fn ($cred) => $this->authorize('viewUsername', $cred));

        // Re-mapea resultados para exponer solo "hasPassword" y nunca el secreto.
        $pila = $pila?->map(function ($cred) {
            return [
                'id' => $cred->id,
                'operator' => $cred->operator,
                'username' => $cred->username,
                'is_active' => $cred->is_active,
                'password_updated_at' => $cred->password_updated_at,
                'created_at' => $cred->created_at,
                'updated_at' => $cred->updated_at,
                'hasPassword' => (bool) $cred->has_password,
            ];
        });

        $portals = $portals->map(function ($cred) {
            return [
                'id' => $cred->id,
                'entity_type' => $cred->entity_type,
                'username' => $cred->username,
                'is_active' => $cred->is_active,
                'is_not_applicable' => $cred->is_not_applicable,
                'password_updated_at' => $cred->password_updated_at,
                'created_at' => $cred->created_at,
                'updated_at' => $cred->updated_at,
                'hasPassword' => (bool) $cred->has_password,
            ];
        });

        return response()->json([
            'affiliation_id' => $affiliation->id,
            'pila' => $pila,
            'portals' => $portals,
        ]);
    }

    /**
     * Revela la contraseña (texto plano) de una credencial específica.
     * SIEMPRE registra auditoría antes de devolver el valor.
     */
    public function reveal(Request $request, string $kind, int $credentialId, CredentialService $svc): JsonResponse
    {
        $userId = $request->user()?->id;

        if ($kind === CredentialKind::PILA->value) {
            $cred = PilaCredential::query()->findOrFail($credentialId);
            $this->authorize('viewPassword', $cred);
            $plain = $svc->decryptAndAudit(
                CredentialKind::PILA,
                $cred->id,
                $cred->getRawOriginal('password_encrypted'),
                CredentialAction::VIEWED,
                $userId,
                $request,
                ['operator' => $cred->operator, 'employer_id' => $cred->employer_id]
            );

            return response()->json(['password' => $plain]);
        }

        if ($kind === CredentialKind::PORTAL->value) {
            $cred = PortalCredential::query()->findOrFail($credentialId);
            $this->authorize('viewPassword', $cred);
            if ($cred->is_not_applicable) {
                return response()->json(['password' => null]);
            }
            $encrypted = $cred->getRawOriginal('password_encrypted');
            $plain = $encrypted
                ? $svc->decryptAndAudit(
                    CredentialKind::PORTAL,
                    $cred->id,
                    $encrypted,
                    CredentialAction::VIEWED,
                    $userId,
                    $request,
                    ['entity_type' => $cred->entity_type, 'employer_id' => $cred->employer_id, 'affiliate_id' => $cred->affiliate_id]
                )
                : null;

            return response()->json(['password' => $plain]);
        }

        abort(404);
    }

    /**
     * Crea o actualiza credenciales PILA para el empleador de la afiliación.
     * (Upsert por unique: employer_id + operator).
     */
    public function upsertPila(
        PilaAffiliation $affiliation,
        StorePilaCredentialRequest $request,
        CredentialService $svc
    ): JsonResponse {
        $validated = $request->validated();

        if (! $affiliation->employer_id) {
            abort(422, 'La afiliación no tiene empleador asignado.');
        }

        $userId = $request->user()?->id;
        $employerId = $affiliation->employer_id;
        $operator = $validated['operator'];

        $cred = PilaCredential::query()
            ->where('employer_id', $employerId)
            ->where('operator', $operator)
            ->first();

        $authCredential = $cred ?: new PilaCredential([
            'employer_id' => $employerId,
            'operator' => $operator,
        ]);
        $this->authorize('updateCredential', $authCredential);

        $action = $cred ? CredentialAction::UPDATED : CredentialAction::CREATED;

        $encrypted = $svc->encrypt($validated['password']);
        $now = now();

        $attributes = [
            'username' => $validated['username'],
            'password_encrypted' => $encrypted,
            'is_active' => $request->boolean('is_active', true),
            'password_updated_at' => $now,
        ];

        if ($cred) {
            $cred->update($attributes);
        } else {
            $cred = PilaCredential::query()->create([
                'employer_id' => $employerId,
                'operator' => $operator,
                ...$attributes,
            ]);
        }

        $svc->audit(
            CredentialKind::PILA,
            $cred->id,
            $action,
            $userId,
            $request,
            ['operator' => $operator, 'employer_id' => $employerId]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Crea o actualiza credenciales de portales (ARL/EPS/AFP/CCF).
     * Upsert por (affiliate_id, entity_type, fk catalog del tipo).
     */
    public function upsertPortal(
        PilaAffiliation $affiliation,
        StorePortalCredentialRequest $request,
        CredentialService $svc
    ): JsonResponse {
        $validated = $request->validated();
        $userId = $request->user()?->id;

        $entityType = $validated['entity_type'];
        $isNotApplicable = $validated['is_not_applicable'];

        $affiliateId = $affiliation->affiliate_id;
        $employerId = $affiliation->employer_id;

        $fkMap = [
            'EPS' => 'eps_id',
            'AFP' => 'afp_id',
            'ARL' => 'arp_id',
            'CCF' => 'ccf_id',
        ];

        $fkColumn = $fkMap[$entityType] ?? null;
        if (! $fkColumn) {
            abort(422, 'entity_type inválido.');
        }

        $catalogId = $affiliation->{$fkColumn};

        if (! $isNotApplicable && ! $catalogId) {
            abort(422, sprintf('La afiliación no tiene %s asignado.', $fkColumn));
        }

        $search = [
            'affiliate_id' => $affiliateId,
            'entity_type' => $entityType,
        ];
        if ($catalogId) {
            $search[$fkColumn] = $catalogId;
        }

        $cred = PortalCredential::query()->where($search)->first();
        $authCredential = $cred ?: new PortalCredential([
            'employer_id' => $employerId,
            'affiliate_id' => $affiliateId,
            'entity_type' => $entityType,
        ]);
        $this->authorize('updateCredential', $authCredential);

        $action = $cred ? CredentialAction::UPDATED : CredentialAction::CREATED;

        $now = now();

        $baseAttributes = [
            'employer_id' => $employerId,
            'affiliate_id' => $affiliateId,
            'entity_type' => $entityType,
            'is_active' => $request->boolean('is_active', true),
            'is_not_applicable' => $isNotApplicable,
        ];

        // Asegura "un solo source of truth": solo se asigna el fk que corresponde al tipo.
        $entityFkAttributes = [
            'eps_id' => $entityType === 'EPS' ? $catalogId : null,
            'afp_id' => $entityType === 'AFP' ? $catalogId : null,
            'arp_id' => $entityType === 'ARL' ? $catalogId : null,
            'ccf_id' => $entityType === 'CCF' ? $catalogId : null,
        ];

        if ($isNotApplicable) {
            $passwordEncrypted = null;
            $username = null;
            $passwordUpdatedAt = null;
        } else {
            $passwordEncrypted = $svc->encrypt($validated['password']);
            $username = $validated['username'];
            $passwordUpdatedAt = $now;
        }

        $attributes = [
            ...$baseAttributes,
            ...$entityFkAttributes,
            'username' => $username,
            'password_encrypted' => $passwordEncrypted,
            'password_updated_at' => $passwordUpdatedAt,
        ];

        if ($cred) {
            $cred->update($attributes);
        } else {
            $cred = PortalCredential::query()->create($attributes);
        }

        $svc->audit(
            CredentialKind::PORTAL,
            $cred->id,
            $action,
            $userId,
            $request,
            ['entity_type' => $entityType, 'employer_id' => $employerId, 'affiliate_id' => $affiliateId]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Actualiza username/password para una credencial existente.
     */
    public function updateCredential(
        Request $request,
        string $kind,
        int $credentialId,
        UpdateCredentialRequest $validatedRequest,
        CredentialService $svc
    ): JsonResponse {
        $userId = $request->user()?->id;

        if ($kind === CredentialKind::PILA->value) {
            $cred = PilaCredential::query()->findOrFail($credentialId);
            $this->authorize('updateCredential', $cred);

            $cred->fill([
                'username' => $validatedRequest->input('username', $cred->username),
                'password_encrypted' => $svc->encrypt($validatedRequest->validated()['password']),
                'password_updated_at' => now(),
            ]);

            if ($validatedRequest->has('is_active')) {
                $cred->is_active = $validatedRequest->boolean('is_active');
            }

            $cred->save();

            $svc->audit(
                CredentialKind::PILA,
                $cred->id,
                CredentialAction::UPDATED,
                $userId,
                $request,
                ['operator' => $cred->operator, 'employer_id' => $cred->employer_id]
            );

            return response()->json(['ok' => true]);
        }

        if ($kind === CredentialKind::PORTAL->value) {
            $cred = PortalCredential::query()->findOrFail($credentialId);
            $this->authorize('updateCredential', $cred);

            if ($cred->is_not_applicable) {
                abort(422, 'Esta credencial está marcada como "No aplica".');
            }

            $cred->fill([
                'username' => $validatedRequest->input('username', $cred->username),
                'password_encrypted' => $svc->encrypt($validatedRequest->validated()['password']),
                'password_updated_at' => now(),
            ]);

            if ($validatedRequest->has('is_active')) {
                $cred->is_active = $validatedRequest->boolean('is_active');
            }

            $cred->save();

            $svc->audit(
                CredentialKind::PORTAL,
                $cred->id,
                CredentialAction::UPDATED,
                $userId,
                $request,
                ['entity_type' => $cred->entity_type, 'employer_id' => $cred->employer_id, 'affiliate_id' => $cred->affiliate_id]
            );

            return response()->json(['ok' => true]);
        }

        abort(404);
    }

    /**
     * Historial de auditoría de credenciales para una afiliación.
     * Solo admins/supervisores.
     */
    public function auditLogs(Request $request, PilaAffiliation $affiliation): JsonResponse
    {
        $this->authorize('viewAuditLog');

        $employerId = $affiliation->employer_id;
        $affiliateId = $affiliation->affiliate_id;

        $pilaCredentialIds = $employerId
            ? PilaCredential::query()->where('employer_id', $employerId)->pluck('id')
            : collect();

        $portalCredentialIds = PortalCredential::query()
            ->where('affiliate_id', $affiliateId)
            ->when($employerId, fn ($q) => $q->orWhere('employer_id', $employerId))
            ->pluck('id');

        if ($pilaCredentialIds->isEmpty() && $portalCredentialIds->isEmpty()) {
            return response()->json(['logs' => []]);
        }

        $logs = CredentialAuditLog::query()
            ->with('user')
            ->where(function ($q) use ($pilaCredentialIds, $portalCredentialIds) {
                $hasAny = false;

                if ($pilaCredentialIds->isNotEmpty()) {
                    $q->where('credential_kind', CredentialKind::PILA->value)
                        ->whereIn('credential_id', $pilaCredentialIds);
                    $hasAny = true;
                }

                if ($portalCredentialIds->isNotEmpty()) {
                    if ($hasAny) {
                        $q->orWhere(function ($q2) use ($portalCredentialIds) {
                            $q2->where('credential_kind', CredentialKind::PORTAL->value)
                                ->whereIn('credential_id', $portalCredentialIds);
                        });
                    } else {
                        $q->where('credential_kind', CredentialKind::PORTAL->value)
                            ->whereIn('credential_id', $portalCredentialIds);
                    }
                }
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'logs' => $logs->map(function (CredentialAuditLog $log) {
                return [
                    'id' => $log->id,
                    'created_at' => $log->created_at?->toDateTimeString(),
                    'user' => $log->user?->name,
                    'action' => $log->action?->value,
                    'credential_kind' => $log->credential_kind?->value,
                    'credential_id' => $log->credential_id,
                    'ip_address' => $log->ip_address,
                    'metadata' => $log->metadata,
                ];
            }),
        ]);
    }

    /**
     * Revela contraseña bajo clic explícito (GET dedicado).
     *
     * types soportados: pila, eps, arl, afp, ccf
     */
    public function revealCredentialByType(
        Request $request,
        PilaAffiliation $affiliation,
        string $type,
        CredentialService $svc
    ): JsonResponse {
        $type = strtolower(trim($type));
        $userId = $request->user()?->id;

        if ($type === CredentialKind::PILA->value) {
            if (! $affiliation->employer_id) {
                return response()->json(['password' => null]);
            }

            $operator = $affiliation->pila_operator;
            $cred = PilaCredential::query()
                ->where('employer_id', $affiliation->employer_id)
                ->where('operator', $operator)
                ->first();

            if (! $cred) {
                return response()->json(['password' => null]);
            }

            $this->authorize('viewPassword', $cred);

            $encrypted = $cred->getRawOriginal('password_encrypted');
            if (! $encrypted) {
                return response()->json(['password' => null]);
            }

            $plain = $svc->decryptAndAudit(
                CredentialKind::PILA,
                $cred->id,
                $encrypted,
                CredentialAction::VIEWED,
                $userId,
                $request,
                ['operator' => $cred->operator, 'employer_id' => $cred->employer_id]
            );

            return response()->json(['password' => $plain]);
        }

        $portalTypeToEntity = [
            'eps' => 'EPS',
            'arl' => 'ARL',
            'afp' => 'AFP',
            'ccf' => 'CCF',
        ];

        $entityType = $portalTypeToEntity[$type] ?? null;
        if (! $entityType) {
            abort(404);
        }

        $cred = PortalCredential::query()
            ->where('affiliate_id', $affiliation->affiliate_id)
            ->where('entity_type', $entityType)
            ->first();

        if (! $cred && $affiliation->employer_id) {
            $cred = PortalCredential::query()
                ->where('employer_id', $affiliation->employer_id)
                ->where('entity_type', $entityType)
                ->first();
        }

        if (! $cred) {
            return response()->json(['password' => null]);
        }

        $this->authorize('viewPassword', $cred);

        if ($cred->is_not_applicable) {
            return response()->json(['password' => null]);
        }

        $encrypted = $cred->getRawOriginal('password_encrypted');
        if (! $encrypted) {
            return response()->json(['password' => null]);
        }

        $plain = $svc->decryptAndAudit(
            CredentialKind::PORTAL,
            $cred->id,
            $encrypted,
            CredentialAction::VIEWED,
            $userId,
            $request,
            ['entity_type' => $cred->entity_type, 'employer_id' => $cred->employer_id, 'affiliate_id' => $cred->affiliate_id]
        );

        return response()->json(['password' => $plain]);
    }
}

