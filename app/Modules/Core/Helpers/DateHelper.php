<?php

namespace App\Modules\Core\Helpers;

use Carbon\Carbon;

class DateHelper
{
    protected static array $days = [
        'Sunday' => 'DOMINGO',
        'Monday' => 'LUNES',
        'Tuesday' => 'MARTES',
        'Wednesday' => 'MIÉRCOLES',
        'Thursday' => 'JUEVES',
        'Friday' => 'VIERNES',
        'Saturday' => 'SÁBADO',
    ];

    protected static array $months = [
        1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
        5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
        9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
    ];

    public static function formatForConfirmation(Carbon $date): array
    {
        return [
            'day_name' => self::$days[$date->format('l')],
            'day' => $date->format('d'),
            'month' => self::$months[$date->month],
            'year' => $date->format('Y'),
            'formatted' => sprintf(
                '%s %s de %s del %s',
                self::$days[$date->format('l')],
                $date->format('d'),
                self::$months[$date->month],
                $date->format('Y')
            ),
        ];
    }

    public static function formatLong(Carbon $date): string
    {
        return sprintf('%d de %s de %d', $date->day, strtolower(self::$months[$date->month]), $date->year);
    }
}
