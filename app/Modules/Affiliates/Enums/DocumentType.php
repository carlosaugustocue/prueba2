<?php

namespace App\Modules\Affiliates\Enums;

enum DocumentType: string
{
    case CC = 'cc';
    case TI = 'ti';
    case CE = 'ce';
    case PA = 'pa';
    case RC = 'rc';
    case NIT = 'nit';
    case PPT = 'ppt';
    case PTT = 'ptt';

    public function label(): string
    {
        return __('social_security.document_type.' . $this->value);
    }

    public function abbreviation(): string
    {
        return strtoupper($this->value);
    }

    public static function toArray(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'abbreviation' => $case->abbreviation(),
        ], self::cases());
    }
}
