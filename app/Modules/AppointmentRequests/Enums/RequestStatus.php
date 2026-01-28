<?php

namespace App\Modules\AppointmentRequests\Enums;

/**
 * Estados de las solicitudes de citas
 */
enum RequestStatus: string
{
    case PENDING = 'pending';           // Pendiente - recién creada
    case IN_PROGRESS = 'in_progress';   // En proceso - operadora trabajando
    case COMPLETED = 'completed';       // Completada - cita obtenida
    case CANCELLED = 'cancelled';       // Cancelada por el cliente
    case FAILED = 'failed';             // No se pudo obtener la cita

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::IN_PROGRESS => 'En Proceso',
            self::COMPLETED => 'Completada',
            self::CANCELLED => 'Cancelada',
            self::FAILED => 'No Obtenida',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::IN_PROGRESS => 'blue',
            self::COMPLETED => 'green',
            self::CANCELLED => 'gray',
            self::FAILED => 'red',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::PENDING => 'bg-yellow-100 text-yellow-800',
            self::IN_PROGRESS => 'bg-blue-100 text-blue-800',
            self::COMPLETED => 'bg-green-100 text-green-800',
            self::CANCELLED => 'bg-gray-100 text-gray-800',
            self::FAILED => 'bg-red-100 text-red-800',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::PENDING => '⏳',
            self::IN_PROGRESS => '🔄',
            self::COMPLETED => '✅',
            self::CANCELLED => '❌',
            self::FAILED => '⚠️',
        };
    }

    /**
     * Estados activos (no finalizados)
     */
    public static function activeStatuses(): array
    {
        return [self::PENDING, self::IN_PROGRESS];
    }

    /**
     * Estados finales
     */
    public static function finalStatuses(): array
    {
        return [self::COMPLETED, self::CANCELLED, self::FAILED];
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
            'badge_class' => $case->badgeClass(),
        ], self::cases());
    }
}
