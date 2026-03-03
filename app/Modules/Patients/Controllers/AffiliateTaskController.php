<?php

namespace App\Modules\Patients\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Patients\Models\AffiliateTask;
use Illuminate\Http\JsonResponse;

class AffiliateTaskController extends Controller
{
    /**
     * Devuelve las tareas pendientes para el usuario actual, según su rol (cartera, seguridad_social, admin/supervisor).
     */
    public function myPending(): JsonResponse
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json([], 401);
        }

        $roleName = $user->role?->name ?? null;
        $areas = [];

        if (in_array($roleName, ['cartera', 'admin', 'supervisor'], true)) {
            $areas[] = AffiliateTask::AREA_CARTERA;
        }
        if (in_array($roleName, ['seguridad_social', 'admin', 'supervisor'], true)) {
            $areas[] = AffiliateTask::AREA_SEGURIDAD_SOCIAL;
        }

        if ($areas === []) {
            return response()->json([]);
        }

        $tasks = AffiliateTask::query()
            ->with(['affiliate:id,first_name,second_name,last_name,second_last_name,document_number'])
            ->whereIn('area', $areas)
            ->where('is_completed', false)
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get()
            ->map(function (AffiliateTask $task) {
                return [
                    'id' => $task->id,
                    'affiliate_id' => $task->affiliate_id,
                    'affiliate_name' => $task->affiliate?->full_name,
                    'affiliate_document' => $task->affiliate?->document_number,
                    'area' => $task->area,
                    'description' => $task->description,
                    'created_at' => $task->created_at?->toIso8601String(),
                ];
            });

        return response()->json($tasks);
    }

    /**
     * Marca una tarea como completada por el usuario actual.
     */
    public function complete(AffiliateTask $task): JsonResponse
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json([], 401);
        }

        $roleName = $user->role?->name ?? null;

        // Verificar que el usuario tenga permiso sobre el área de la tarea.
        if (
            ($task->area === AffiliateTask::AREA_CARTERA && ! in_array($roleName, ['cartera', 'admin', 'supervisor'], true)) ||
            ($task->area === AffiliateTask::AREA_SEGURIDAD_SOCIAL && ! in_array($roleName, ['seguridad_social', 'admin', 'supervisor'], true))
        ) {
            return response()->json(['message' => 'No autorizado para completar esta tarea.'], 403);
        }

        if (! $task->is_completed) {
            $task->is_completed = true;
            $task->completed_by = $user->id;
            $task->completed_at = now();
            $task->save();
        }

        return response()->json([
            'id' => $task->id,
            'is_completed' => $task->is_completed,
            'completed_at' => $task->completed_at?->toIso8601String(),
        ]);
    }
}

