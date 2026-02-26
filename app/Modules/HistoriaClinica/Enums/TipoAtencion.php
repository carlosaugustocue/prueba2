<?php

namespace App\Modules\HistoriaClinica\Enums;

enum TipoAtencion: string
{
    case CONSULTA = 'CONSULTA';
    case URGENCIA = 'URGENCIA';
    case HOSPITALIZACION = 'HOSPITALIZACION';
    case TELECONSULTA = 'TELECONSULTA';

    public function label(): string
    {
        return match ($this) {
            self::CONSULTA => 'Consulta',
            self::URGENCIA => 'Urgencia',
            self::HOSPITALIZACION => 'Hospitalización',
            self::TELECONSULTA => 'Teleconsulta',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
