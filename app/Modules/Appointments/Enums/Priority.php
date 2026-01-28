<?php

namespace App\Modules\Appointments\Enums;

enum Priority: string
{
    case URGENT = 'urgent';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';

    public function label(): string
    {
        return match($this) {
            self::URGENT => 'Urgente',
            self::HIGH => 'Alta',
            self::MEDIUM => 'Media',
            self::LOW => 'Baja',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::URGENT => 'red',
            self::HIGH => 'orange',
            self::MEDIUM => 'yellow',
            self::LOW => 'gray',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::URGENT => 'bg-red-100 text-red-800',
            self::HIGH => 'bg-orange-100 text-orange-800',
            self::MEDIUM => 'bg-yellow-100 text-yellow-800',
            self::LOW => 'bg-gray-100 text-gray-800',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
            'badgeClass' => $case->badgeClass(),
        ], self::cases());
    }
}
