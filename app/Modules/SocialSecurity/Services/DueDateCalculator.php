<?php

namespace App\Modules\SocialSecurity\Services;

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
    /**
     * Mapeo últimos 2 dígitos del NIT/documento → número de día hábil (2 a 16).
     * Tabla oficial PILA - Decreto 1990 de 2016.
     * Cada fila: [min, max, día hábil].
     */
    private const DIGITS_TO_BUSINESS_DAY = [
        [0, 7, 2],
        [8, 14, 3],
        [15, 21, 4],
        [22, 28, 5],
        [29, 35, 6],
        [36, 42, 7],
        [43, 49, 8],
        [50, 56, 9],
        [57, 63, 10],
        [64, 69, 11],
        [70, 75, 12],
        [76, 81, 13],
        [82, 87, 14],
        [88, 93, 15],
        [94, 99, 16],
    ];

    /**
     * Obtiene el número de día hábil de vencimiento (2-16) según los dos últimos dígitos del documento.
     * Aplica para NIT o documento de identidad (cédula).
     *
     * @param string $documentNumber NIT o número de documento (solo dígitos se consideran)
     * @return int Valor entre 2 y 16; 2 si no se puede determinar
     */
    public function paymentDayFromDocument(string $documentNumber): int
    {
        $digits = $this->lastTwoDigits($documentNumber);
        if ($digits === null) {
            return 2; // valor por defecto conservador
        }

        foreach (self::DIGITS_TO_BUSINESS_DAY as [$min, $max, $day]) {
            if ($digits >= $min && $digits <= $max) {
                return $day;
            }
        }

        return 2;
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
        $paymentDay = $this->paymentDayFromDocument($documentNumber);

        return $this->dueDateForPeriodByPaymentDay($year, $month, $paymentDay);
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
        $paymentDay = max(2, min(16, $paymentDay));
        $nextMonth = Carbon::createFromDate($year, $month, 1)->addMonth();
        $yearNext = (int) $nextMonth->format('Y');
        $monthNext = (int) $nextMonth->format('m');

        return $this->nthBusinessDayOfMonth($yearNext, $monthNext, $paymentDay);
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
        $n = max(1, min(16, $n));
        $date = Carbon::createFromDate($year, $month, 1);
        $count = 0;

        while ($count < $n) {
            if ($this->isBusinessDay($date)) {
                $count++;
                if ($count === $n) {
                    return $date->copy();
                }
            }
            $date->addDay();
        }

        return $date->copy();
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

    /**
     * Extrae los dos últimos dígitos del documento (0-99).
     * Se consideran solo caracteres numéricos; si el documento tiene menos de 2 dígitos, se usa lo disponible.
     *
     * @return int|null Valor 0-99, o null si no hay dígitos
     */
    private function lastTwoDigits(string $documentNumber): ?int
    {
        $digitsOnly = preg_replace('/\D/', '', $documentNumber);
        if ($digitsOnly === '') {
            return null;
        }
        $len = strlen($digitsOnly);
        if ($len >= 2) {
            return (int) substr($digitsOnly, -2);
        }

        return (int) $digitsOnly;
    }
}
