<?php

namespace App\Modules\HistoriaClinica\Enums;

enum TipoAntecedente: string
{
    case PATOLOGICO = 'PATOLOGICO';
    case QUIRURGICO = 'QUIRURGICO';
    case FARMACOLOGICO = 'FARMACOLOGICO';
    case ALERGICO = 'ALERGICO';
    case FAMILIAR = 'FAMILIAR';
    case TOXICO = 'TOXICO';
    case GINECO_OBSTETRICO = 'GINECO_OBSTETRICO';

    public function label(): string
    {
        return match ($this) {
            self::PATOLOGICO => 'Patológico',
            self::QUIRURGICO => 'Quirúrgico',
            self::FARMACOLOGICO => 'Farmacológico',
            self::ALERGICO => 'Alérgico',
            self::FAMILIAR => 'Familiar',
            self::TOXICO => 'Tóxico',
            self::GINECO_OBSTETRICO => 'Gineco-obstétrico',
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
