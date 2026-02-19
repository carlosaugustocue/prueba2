<?php

namespace App\Modules\SocialSecurity\Enums;

enum PayrollStatus: string
{
    case PENDING = 'PENDING';
    case SETTLED = 'SETTLED';
    case SENT_TO_CLIENT = 'SENT_TO_CLIENT';
    case PAID = 'PAID';
    case OVERDUE = 'OVERDUE';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::SETTLED => 'Liquidada',
            self::SENT_TO_CLIENT => 'Enviada al cliente',
            self::PAID => 'Pagada',
            self::OVERDUE => 'En mora',
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
