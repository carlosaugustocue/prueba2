<?php

namespace App\Modules\Patients\Enums;

/**
 * Tipos de parentesco para beneficiarios según normativa colombiana
 * (Ley 100 de 1993 y Decreto 806 de 1998)
 */
enum RelationshipType: string
{
    case CONYUGE = 'conyuge';
    case COMPANERO_PERMANENTE = 'companero_permanente';
    case HIJO_MENOR = 'hijo_menor';
    case HIJO_ESTUDIANTE = 'hijo_estudiante';
    case HIJO_DISCAPACIDAD = 'hijo_discapacidad';
    case PADRE = 'padre';
    case MADRE = 'madre';
    case HERMANO_DISCAPACIDAD = 'hermano_discapacidad';

    public function label(): string
    {
        return match($this) {
            self::CONYUGE => 'Cónyuge',
            self::COMPANERO_PERMANENTE => 'Compañero(a) Permanente',
            self::HIJO_MENOR => 'Hijo(a) menor de 18 años',
            self::HIJO_ESTUDIANTE => 'Hijo(a) estudiante (18-25 años)',
            self::HIJO_DISCAPACIDAD => 'Hijo(a) con discapacidad',
            self::PADRE => 'Padre',
            self::MADRE => 'Madre',
            self::HERMANO_DISCAPACIDAD => 'Hermano(a) con discapacidad',
        };
    }

    public function shortLabel(): string
    {
        return match($this) {
            self::CONYUGE => 'Cónyuge',
            self::COMPANERO_PERMANENTE => 'Compañero(a)',
            self::HIJO_MENOR => 'Hijo(a)',
            self::HIJO_ESTUDIANTE => 'Hijo(a) estudiante',
            self::HIJO_DISCAPACIDAD => 'Hijo(a) discapacidad',
            self::PADRE => 'Padre',
            self::MADRE => 'Madre',
            self::HERMANO_DISCAPACIDAD => 'Hermano(a)',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::CONYUGE => 'Persona unida legalmente en matrimonio',
            self::COMPANERO_PERMANENTE => 'Persona con unión marital de hecho (mínimo 2 años)',
            self::HIJO_MENOR => 'Hijo(a) menor de 18 años de edad',
            self::HIJO_ESTUDIANTE => 'Hijo(a) entre 18 y 25 años que estudie tiempo completo y dependa económicamente',
            self::HIJO_DISCAPACIDAD => 'Hijo(a) de cualquier edad con discapacidad permanente',
            self::PADRE => 'Padre que depende económicamente del cotizante',
            self::MADRE => 'Madre que depende económicamente del cotizante',
            self::HERMANO_DISCAPACIDAD => 'Hermano(a) con discapacidad permanente que depende económicamente',
        };
    }

    public function requiresEconomicDependency(): bool
    {
        return match($this) {
            self::PADRE, self::MADRE, self::HERMANO_DISCAPACIDAD, self::HIJO_ESTUDIANTE => true,
            default => false,
        };
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'short_label' => $case->shortLabel(),
            'description' => $case->description(),
        ], self::cases());
    }
}
