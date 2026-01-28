<?php

namespace App\Modules\Appointments\Enums;

enum AppointmentType: string
{
    case GENERAL = 'general';
    case SPECIALIST = 'specialist';
    case LABORATORY = 'laboratory';
    case IMAGING = 'imaging';
    case AUTHORIZATION = 'authorization';
    case FORMULA_RENEWAL = 'formula_renewal';
    case EPS_CHANGE = 'eps_change';
    case IPS_CHANGE = 'ips_change';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::GENERAL => 'Médico General',
            self::SPECIALIST => 'Especialista',
            self::LABORATORY => 'Laboratorios',
            self::IMAGING => 'Imágenes Diagnósticas',
            self::AUTHORIZATION => 'Autorización',
            self::FORMULA_RENEWAL => 'Renovación de Fórmula',
            self::EPS_CHANGE => 'Cambio de EPS',
            self::IPS_CHANGE => 'Cambio de IPS',
            self::OTHER => 'Otro',
        };
    }

    public function shortDescription(): string
    {
        return match($this) {
            self::GENERAL => 'cita de medicina general',
            self::SPECIALIST => 'cita con especialista',
            self::LABORATORY => 'laboratorios',
            self::IMAGING => 'imágenes diagnósticas',
            self::AUTHORIZATION => 'gestión de autorización',
            self::FORMULA_RENEWAL => 'renovación de fórmula médica',
            self::EPS_CHANGE => 'cambio de EPS',
            self::IPS_CHANGE => 'cambio de IPS',
            self::OTHER => 'gestión médica',
        };
    }

    public function requiresAppointmentDetails(): bool
    {
        return in_array($this, [self::GENERAL, self::SPECIALIST, self::LABORATORY, self::IMAGING]);
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'requiresDetails' => $case->requiresAppointmentDetails(),
        ], self::cases());
    }
}
