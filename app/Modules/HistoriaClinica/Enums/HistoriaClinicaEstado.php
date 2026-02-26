<?php

namespace App\Modules\HistoriaClinica\Enums;

enum HistoriaClinicaEstado: string
{
    case ACTIVA = 'ACTIVA';
    case INACTIVA = 'INACTIVA';
    case ARCHIVADA = 'ARCHIVADA';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVA => 'Activa',
            self::INACTIVA => 'Inactiva',
            self::ARCHIVADA => 'Archivada',
        };
    }
}
