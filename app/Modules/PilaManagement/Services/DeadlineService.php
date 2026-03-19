<?php

namespace App\Modules\PilaManagement\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class DeadlineService
{
    /**
     * Tabla oficial PILA (Decreto 1990 de 2016): últimos 2 dígitos del documento → N (2..16),
     * donde N es el ordinal del día hábil en el mes siguiente.
     *
     * Para evitar "magic numbers", la tabla se lee de config (`config/pila.php`).
     */
    public function paymentBusinessDayFromDocument(string $documentNumber): int
    {
        $digits = $this->lastTwoDigits($documentNumber);
        $rows = config('pila.deadline.last_two_digits_to_business_day', []);

        if (! is_int($digits) || $rows === []) {
            return (int) config('pila.deadline.default_business_day', 2);
        }

        foreach ($rows as $row) {
            $min = $row['min'] ?? null;
            $max = $row['max'] ?? null;
            $day = $row['day'] ?? null;
            if (! is_int($min) || ! is_int($max) || ! is_int($day)) {
                continue;
            }
            if ($digits >= $min && $digits <= $max) {
                return $day;
            }
        }

        return (int) config('pila.deadline.default_business_day', 2);
    }

    /**
     * Fecha límite de pago para un período liquidado (year, month) según documento del aportante.
     */
    public function dueDateForPeriod(int $year, int $month, string $documentNumber): CarbonInterface
    {
        $paymentDay = $this->paymentBusinessDayFromDocument($documentNumber);

        return $this->dueDateForPeriodByPaymentDay($year, $month, $paymentDay);
    }

    /**
     * Fecha límite dado ya el ordinal del día hábil (2..16).
     * El vencimiento cae en el mes siguiente al período.
     */
    public function dueDateForPeriodByPaymentDay(int $year, int $month, int $paymentDay): CarbonInterface
    {
        $min = (int) config('pila.deadline.min_business_day', 2);
        $max = (int) config('pila.deadline.max_business_day', 16);
        $paymentDay = max($min, min($max, $paymentDay));

        $nextMonth = Carbon::createFromDate($year, $month, 1)->addMonth();

        return $this->nthBusinessDayOfMonth(
            (int) $nextMonth->format('Y'),
            (int) $nextMonth->format('n'),
            $paymentDay
        );
    }

    /**
     * Obtiene el N-ésimo día hábil del mes.
     * Día hábil: no sábado, no domingo, no festivo (tabla `colombian_holidays`).
     */
    public function nthBusinessDayOfMonth(int $year, int $month, int $n): CarbonInterface
    {
        $n = max(1, min((int) config('pila.deadline.max_business_day', 16), $n));
        $date = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $date->copy()->endOfMonth();

        $holidaySet = $this->holidaySetForMonth($date);

        $count = 0;
        while ($date->lte($end)) {
            if ($this->isBusinessDay($date, $holidaySet)) {
                $count++;
                if ($count === $n) {
                    return $date->copy();
                }
            }
            $date->addDay();
        }

        // Fallback defensivo (no debería ocurrir).
        return $end;
    }

    /**
     * @param array<string,true> $holidaySet
     */
    private function isBusinessDay(CarbonInterface $date, array $holidaySet): bool
    {
        if ($date->isWeekend()) {
            return false;
        }

        return ! isset($holidaySet[$date->toDateString()]);
    }

    /**
     * @return array<string,true> set de fechas YYYY-MM-DD
     */
    private function holidaySetForMonth(CarbonInterface $anyDateInMonth): array
    {
        $start = Carbon::createFromDate(
            (int) $anyDateInMonth->format('Y'),
            (int) $anyDateInMonth->format('n'),
            1
        )->startOfDay();
        $end = $start->copy()->endOfMonth();

        $dates = DB::table('colombian_holidays')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => is_string($d) ? $d : (string) $d)
            ->all();

        $set = [];
        foreach ($dates as $d) {
            $set[$d] = true;
        }

        return $set;
    }

    private function lastTwoDigits(string $documentNumber): ?int
    {
        // Si viene con DV (ej: 901776975-4), para la tabla PILA se usan los dígitos del documento SIN el DV.
        $base = $documentNumber;
        if (str_contains($base, '-')) {
            $base = explode('-', $base, 2)[0];
        }

        $digitsOnly = preg_replace('/\D/', '', $base);
        if ($digitsOnly === '') {
            return null;
        }

        return (int) substr($digitsOnly, -2);
    }
}

