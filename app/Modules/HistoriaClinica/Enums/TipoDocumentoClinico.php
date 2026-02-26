<?php

namespace App\Modules\HistoriaClinica\Enums;

enum TipoDocumentoClinico: string
{
    case LABORATORIO = 'LABORATORIO';
    case IMAGEN = 'IMAGEN';
    case CONSENTIMIENTO = 'CONSENTIMIENTO';
    case EXTERNO = 'EXTERNO';

    public function label(): string
    {
        return match ($this) {
            self::LABORATORIO => 'Laboratorio',
            self::IMAGEN => 'Imagen',
            self::CONSENTIMIENTO => 'Consentimiento',
            self::EXTERNO => 'Externo',
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
