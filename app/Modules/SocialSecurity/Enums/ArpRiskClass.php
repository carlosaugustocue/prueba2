<?php

namespace App\Modules\SocialSecurity\Enums;

enum ArpRiskClass: string
{
    case NOT_APPLICABLE = '0';
    case RISK_1 = '1';
    case RISK_2 = '2';
    case RISK_3 = '3';
    case RISK_4 = '4';
    case RISK_5 = '5';

    public function label(): string
    {
        return __('social_security.arp_risk_class.' . $this->value);
    }

    public static function toArray(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
