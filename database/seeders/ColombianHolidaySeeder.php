<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Festivos oficiales de Colombia para cálculo de día hábil (PILA).
 * Fuente: calendario oficial (Ley Emiliani, festivos móviles por Pascua).
 * Actualizar anualmente según decreto del gobierno.
 */
class ColombianHolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = array_merge(
            $this->holidays2025(),
            $this->holidays2026()
        );

        $now = now();
        $rows = [];
        foreach ($holidays as $date => $name) {
            $rows[] = [
                'date' => $date,
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows !== []) {
            DB::table('colombian_holidays')->insertOrIgnore($rows);
        }
    }

    /** Festivos 2025 (calendario oficial Colombia) */
    private function holidays2025(): array
    {
        return [
            '2025-01-01' => 'Año Nuevo',
            '2025-01-06' => 'Día de los Reyes Magos',
            '2025-03-24' => 'Día de San José',
            '2025-04-17' => 'Jueves Santo',
            '2025-04-18' => 'Viernes Santo',
            '2025-05-01' => 'Día del Trabajo',
            '2025-06-02' => 'Corpus Christi',
            '2025-06-23' => 'Sagrado Corazón de Jesús',
            '2025-06-30' => 'San Pedro y San Pablo',
            '2025-07-20' => 'Día de la Independencia',
            '2025-08-07' => 'Batalla de Boyacá',
            '2025-08-18' => 'Asunción de la Virgen',
            '2025-10-13' => 'Día de la Raza',
            '2025-11-03' => 'Día de Todos los Santos',
            '2025-11-17' => 'Independencia de Cartagena',
            '2025-12-08' => 'Inmaculada Concepción',
            '2025-12-25' => 'Navidad',
        ];
    }

    /** Festivos 2026 (calendario oficial Colombia) */
    private function holidays2026(): array
    {
        return [
            '2026-01-01' => 'Año Nuevo',
            '2026-01-12' => 'Día de los Reyes Magos',
            '2026-03-23' => 'Día de San José',
            '2026-04-02' => 'Jueves Santo',
            '2026-04-03' => 'Viernes Santo',
            '2026-05-01' => 'Día del Trabajo',
            '2026-05-18' => 'Ascensión del Señor',
            '2026-06-08' => 'Corpus Christi',
            '2026-06-15' => 'Sagrado Corazón de Jesús',
            '2026-06-29' => 'San Pedro y San Pablo',
            '2026-07-20' => 'Día de la Independencia',
            '2026-08-07' => 'Batalla de Boyacá',
            '2026-08-17' => 'Asunción de la Virgen',
            '2026-10-12' => 'Día de la Raza',
            '2026-11-02' => 'Día de Todos los Santos',
            '2026-11-16' => 'Independencia de Cartagena',
            '2026-12-08' => 'Inmaculada Concepción',
            '2026-12-25' => 'Navidad',
        ];
    }
}
