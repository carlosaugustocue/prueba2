<?php

namespace App\Modules\PilaManagement\Services;

use Carbon\Carbon;

class DeadlineService
{
    /**
     * Calcula la fecha límite de pago para un período dado.
     *
     * Nota: en este feature dejamos el esqueleto; la implementación completa
     * se hará con calendario de festivos colombianos + regla de día hábil.
     */
    public function calculateForPeriod(int $paymentBusinessDay, int $year, int $month): Carbon
    {
        // Placeholder: día calendario. En el feature de DeadlineService se implementará día hábil real.
        return Carbon::create($year, $month, min(28, max(1, $paymentBusinessDay)))->startOfDay();
    }
}

