<?php

namespace App\Modules\Patients\Enums;

enum DocumentType: string
{
    case CC = 'cc';
    case TI = 'ti';
    case CE = 'ce';
    case PA = 'pa';
    case RC = 'rc';
    case NIT = 'nit';

    public function label(): string
    {
        return match($this) {
            self::CC => 'Cédula de Ciudadanía',
            self::TI => 'Tarjeta de Identidad',
            self::CE => 'Cédula de Extranjería',
            self::PA => 'Pasaporte',
            self::RC => 'Registro Civil',
            self::NIT => 'NIT',
        };
    }

    public function abbreviation(): string
    {
        return strtoupper($this->value);
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'abbreviation' => $case->abbreviation(),
        ], self::cases());
    }
}
