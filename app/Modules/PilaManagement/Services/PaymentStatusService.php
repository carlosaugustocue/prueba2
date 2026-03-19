<?php

namespace App\Modules\PilaManagement\Services;

class PaymentStatusService
{
    /**
     * Sprint futuro:
     * - Transiciones masivas al cambio de mes (RN-12 del documento)
     * - Reglas para current/overdue/anticipated basadas en último período pagado y fecha límite
     */
    public function runMonthlyTransitions(string $period): void
    {
        // Implementación en feature dedicado.
    }
}

