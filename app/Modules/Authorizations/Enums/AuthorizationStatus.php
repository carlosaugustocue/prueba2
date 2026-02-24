<?php

namespace App\Modules\Authorizations\Enums;

/**
 * Estados del ciclo de vida de una autorización médica (RF-AUT-03).
 */
enum AuthorizationStatus: string
{
    case PENDING_RADICATION = 'pending_radication';  // Pendiente de radicación
    case RADICATED = 'radicated';                    // Radicada ante EPS
    case APPROVED = 'approved';                      // Aprobada
    case DENIED = 'denied';                          // Negada
    case IN_APPEAL = 'in_appeal';                    // En apelación
    case EXPIRED = 'expired';                        // Vencida

    public function label(): string
    {
        return match ($this) {
            self::PENDING_RADICATION => 'Pendiente de radicación',
            self::RADICATED => 'Radicada',
            self::APPROVED => 'Aprobada',
            self::DENIED => 'Negada',
            self::IN_APPEAL => 'En apelación',
            self::EXPIRED => 'Vencida',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING_RADICATION => 'bg-yellow-100 text-yellow-800',
            self::RADICATED => 'bg-blue-100 text-blue-800',
            self::APPROVED => 'bg-green-100 text-green-800',
            self::DENIED => 'bg-red-100 text-red-800',
            self::IN_APPEAL => 'bg-amber-100 text-amber-800',
            self::EXPIRED => 'bg-gray-100 text-gray-800',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'badge_class' => $case->badgeClass(),
        ], self::cases());
    }

    /** Transiciones permitidas desde este estado */
    public function canTransitionTo(AuthorizationStatus $to): bool
    {
        return match ($this) {
            self::PENDING_RADICATION => $to === self::RADICATED,
            self::RADICATED => in_array($to, [self::APPROVED, self::DENIED], true),
            self::APPROVED => $to === self::EXPIRED,
            self::DENIED => $to === self::IN_APPEAL,
            self::IN_APPEAL => in_array($to, [self::APPROVED, self::DENIED], true),
            self::EXPIRED => false,
        };
    }
}
