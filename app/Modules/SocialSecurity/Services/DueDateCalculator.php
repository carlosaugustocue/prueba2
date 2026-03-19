<?php

namespace App\Modules\SocialSecurity\Services;

use App\Modules\PilaManagement\Services\DeadlineService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Calcula la fecha de vencimiento de pago PILA según normativa colombiana.
 *
 * Normativa:
 * - Decreto 780 de 2016 (libro 3, parte 2), modificado por Decreto 1990 de 2016 y 923 de 2017, art. 3.2.2.1.
 * - El vencimiento es el N-ésimo día hábil del mes siguiente al período liquidado (N = 2 a 16).
 * - N se determina por los dos últimos dígitos del NIT (empresas) o del documento de identidad (personas naturales).
 * - Día hábil: no sábado, no domingo, no festivo (calendario oficial Colombia).
 */
class DueDateCalculator
{
    public function __construct(
        private readonly ?DeadlineService $deadlineService = null
    ) {
    }

    /**
     * Obtiene el número de día hábil de vencimiento (2-16) según los dos últimos dígitos del documento.
     * Aplica para NIT o documento de identidad (cédula).
     *
     * @param string $documentNumber NIT o número de documento (solo dígitos se consideran)
     * @return int Valor entre 2 y 16; 2 si no se puede determinar
     */
    public function paymentDayFromDocument(string $documentNumber): int
    {
        return $this->svc()->paymentBusinessDayFromDocument($documentNumber);
    }

    /**
     * Fecha de vencimiento de pago para un período dado.
     * El período (year, month) es el mes que se liquida; el pago vence en el mes siguiente.
     *
     * @param int    $year          Año del período liquidado (ej. 2026)
     * @param int    $month         Mes del período liquidado (1-12) (ej. 1 = enero)
     * @param string $documentNumber NIT o documento del aportante (para obtener día hábil 2-16)
     * @return CarbonInterface Fecha de vencimiento (día hábil del mes siguiente)
     */
    public function dueDateForPeriod(int $year, int $month, string $documentNumber): CarbonInterface
    {
        return $this->svc()->dueDateForPeriod($year, $month, $documentNumber);
    }

    /**
     * Fecha de vencimiento dado ya el número de día hábil (2-16).
     * Útil cuando el perfil SS ya tiene guardado payment_day.
     *
     * @param int $year        Año del período liquidado
     * @param int $month       Mes del período liquidado (1-12)
     * @param int $paymentDay  Número de día hábil (2 a 16)
     * @return CarbonInterface Fecha de vencimiento
     */
    public function dueDateForPeriodByPaymentDay(int $year, int $month, int $paymentDay): CarbonInterface
    {
        return $this->svc()->dueDateForPeriodByPaymentDay($year, $month, $paymentDay);
    }

    /**
     * Obtiene el N-ésimo día hábil de un mes (N = 2 a 16).
     * No se cuentan sábado, domingo ni festivos de Colombia.
     *
     * @param int $year  Año
     * @param int $month Mes (1-12)
     * @param int $n     Ordinal del día hábil (2 = segundo día hábil, 16 = decimosexto)
     * @return CarbonInterface
     */
    public function nthBusinessDayOfMonth(int $year, int $month, int $n): CarbonInterface
    {
        return $this->svc()->nthBusinessDayOfMonth($year, $month, $n);
    }

    /**
     * Indica si una fecha es día hábil en Colombia (no fin de semana ni festivo).
     */
    public function isBusinessDay(CarbonInterface $date): bool
    {
        if ($date->isWeekend()) {
            return false;
        }

        $dates = DB::table('colombian_holidays')
            ->whereDate('date', $date->toDateString())
            ->exists();

        return ! $dates;
    }

    private function svc(): DeadlineService
    {
        return $this->deadlineService ?? app(DeadlineService::class);
    }
}
