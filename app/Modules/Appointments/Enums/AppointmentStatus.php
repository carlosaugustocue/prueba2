<?php

namespace App\Modules\Appointments\Enums;

enum AppointmentStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case CONFIRMED = 'confirmed';
    case SENT = 'sent';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::IN_PROGRESS => 'En Progreso',
            self::CONFIRMED => 'Confirmada',
            self::SENT => 'Enviada',
            self::COMPLETED => 'Completada',
            self::CANCELLED => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::IN_PROGRESS => 'blue',
            self::CONFIRMED => 'indigo',
            self::SENT => 'purple',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::PENDING => 'bg-yellow-100 text-yellow-800',
            self::IN_PROGRESS => 'bg-blue-100 text-blue-800',
            self::CONFIRMED => 'bg-indigo-100 text-indigo-800',
            self::SENT => 'bg-purple-100 text-purple-800',
            self::COMPLETED => 'bg-green-100 text-green-800',
            self::CANCELLED => 'bg-red-100 text-red-800',
        };
    }

    public function allowedTransitions(): array
    {
        return match($this) {
            self::PENDING => [self::IN_PROGRESS, self::CANCELLED],
            self::IN_PROGRESS => [self::PENDING, self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::PENDING, self::SENT, self::CANCELLED],
            self::SENT => [self::PENDING, self::COMPLETED, self::CANCELLED],
            self::COMPLETED => [],
            self::CANCELLED => [self::PENDING],
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
        return [self::PENDING, self::IN_PROGRESS, self::CONFIRMED, self::SENT];
    }
}
