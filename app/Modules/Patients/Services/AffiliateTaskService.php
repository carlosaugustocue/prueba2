<?php

namespace App\Modules\Patients\Services;

use App\Modules\Patients\Models\Affiliate;
use App\Modules\Patients\Models\AffiliateTask;

/**
 * Crea tareas internas asociadas a un afiliado (cartera, seguridad social, etc.).
 */
class AffiliateTaskService
{
    /**
     * Tareas iniciales al crear un afiliado.
     *
     * - Cartera: generar recibo de caja inicial y registrar número.
     * - Seguridad social: revisar perfil SS (tipo de cotizante, operador, IBC, etc.).
     */
    public function createForNewAffiliate(Affiliate $affiliate): void
    {
        // Solo tiene sentido para cotizantes; beneficiarios suelen depender del titular.
        if ($affiliate->patient_type?->value !== 'cotizante') {
            return;
        }

        $tasks = [
            [
                'area' => AffiliateTask::AREA_CARTERA,
                'description' => 'Generar recibo de caja inicial y registrar número del recibo.',
            ],
            [
                'area' => AffiliateTask::AREA_SEGURIDAD_SOCIAL,
                'description' => 'Revisar perfil de seguridad social del afiliado (tipo de cotizante, operador, IBC, etc.).',
            ],
        ];

        foreach ($tasks as $task) {
            AffiliateTask::create([
                'affiliate_id' => $affiliate->id,
                'area' => $task['area'],
                'description' => $task['description'],
                'is_completed' => false,
            ]);
        }
    }
}

