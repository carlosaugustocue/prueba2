<?php

namespace App\Modules\Appointments\Enums;

/**
 * Estados simplificados para citas.
 * 
 * La cita se crea cuando la EPS/IPS la confirma, por lo que
 * solo necesitamos saber si está confirmada o fue cancelada.
 */
enum AppointmentStatus: string
{
    case CONFIRMED = 'confirmed';   // Cita programada/confirmada por la EPS/IPS
    case CANCELLED = 'cancelled';   // Cancelada (por la EPS/IPS o el paciente)

    public function label(): string
    {
        return match($this) {
            self::CONFIRMED => 'Confirmada',
            self::CANCELLED => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::CONFIRMED => 'green',
            self::CANCELLED => 'red',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::CONFIRMED => 'bg-green-100 text-green-800',
            self::CANCELLED => 'bg-red-100 text-red-800',
        };
    }

    public function allowedTransitions(): array
    {
        return match($this) {
            self::CONFIRMED => [self::CANCELLED],
            self::CANCELLED => [self::CONFIRMED], // Por si se reactiva
        };
    }

    public function canTransitionTo(AppointmentStatus $status): bool
    {
        return in_array($status, $this->allowedTransitions());
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

    public static function activeStatuses(): array
    {
        return [self::CONFIRMED];
    }
}
