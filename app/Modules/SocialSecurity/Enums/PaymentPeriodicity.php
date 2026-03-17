<?php

namespace App\Modules\SocialSecurity\Enums;

enum PaymentPeriodicity: string
{
    case CURRENT = 'CURRENT';
    case OVERDUE = 'OVERDUE';

    public function label(): string
    {
        return __('social_security.payment_periodicity.' . $this->value);
    }

    public static function toArray(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
