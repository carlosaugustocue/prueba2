<?php

namespace App\Modules\SocialSecurity\Services;

/**
 * Reglas de liquidación por tipo de cotizante PILA.
 * Ref: docs/NORMATIVA_Y_COMPLEJIDAD_SEGURIDAD_SOCIAL.md sección 3.
 */
final class ContributorTypeRules
{
    /** Códigos que liquidan como dependientes (split empleador/empleado en salud y pensión). */
    private const DEPENDENT_CODES = ['01', '02'];

    /** Código 02: servicio doméstico — parafiscales siempre exentos. */
    private const PARAFISCALES_EXEMPT_CODES = ['02'];

    /** Solo salud (no pensión, no ARL, no CCF). */
    private const HEALTH_ONLY_CODES = ['12', '40'];

    /** Salud + ARL, sin pensión (aprendiz productiva). */
    private const HEALTH_AND_ARL_ONLY_CODES = ['19'];

    /** Sin aportes obligatorios (madre sustituta o régimen especial). */
    private const NO_CONTRIBUTION_CODES = ['04'];

    /** Independiente voluntario: no salud obligatoria, pensión voluntaria (no liquidamos obligatorio). */
    private const VOLUNTARY_ONLY_CODES = ['57'];

    /** Independiente flexible (51): aportes proporcionales por días/semanas. */
    private const PROPORTIONAL_CODES = ['51'];

    /**
     * Obtiene las reglas aplicables para un código de tipo cotizante.
     *
     * @return array{
     *   is_dependent: bool,
     *   parafiscales_allowed: bool,
     *   health_applies: bool,
     *   pension_applies: bool,
     *   arl_applies: bool,
     *   ccf_applies: bool,
     *   is_proportional: bool,
     *   description: string
     * }
     */
    public static function forCode(string $code): array
    {
        $code = trim($code);
        $isDependent = in_array($code, self::DEPENDENT_CODES, true);
        $parafiscalesAllowed = $isDependent && ! in_array($code, self::PARAFISCALES_EXEMPT_CODES, true);

        if (in_array($code, self::NO_CONTRIBUTION_CODES, true)) {
            return [
                'is_dependent' => false,
                'parafiscales_allowed' => false,
                'health_applies' => false,
                'pension_applies' => false,
                'arl_applies' => false,
                'ccf_applies' => false,
                'is_proportional' => false,
                'description' => 'Madre sustituta: sin aportes obligatorios.',
            ];
        }

        if (in_array($code, self::VOLUNTARY_ONLY_CODES, true)) {
            return [
                'is_dependent' => false,
                'parafiscales_allowed' => false,
                'health_applies' => false,
                'pension_applies' => false,
                'arl_applies' => false,
                'ccf_applies' => false,
                'is_proportional' => false,
                'description' => 'Independiente voluntario: solo aportes voluntarios (no se liquidan aquí).',
            ];
        }

        if (in_array($code, self::HEALTH_ONLY_CODES, true)) {
            return [
                'is_dependent' => false,
                'parafiscales_allowed' => false,
                'health_applies' => true,
                'pension_applies' => false,
                'arl_applies' => false,
                'ccf_applies' => false,
                'is_proportional' => false,
                'description' => $code === '12' ? 'Aprendiz SENA lectiva: solo salud.' : 'Beneficiario UPC adicional: solo salud.',
            ];
        }

        if (in_array($code, self::HEALTH_AND_ARL_ONLY_CODES, true)) {
            return [
                'is_dependent' => false,
                'parafiscales_allowed' => false,
                'health_applies' => true,
                'pension_applies' => false,
                'arl_applies' => true,
                'ccf_applies' => false,
                'is_proportional' => false,
                'description' => 'Aprendiz SENA productiva: salud y ARL.',
            ];
        }

        $isProportional = in_array($code, self::PROPORTIONAL_CODES, true);
        $ccfApplies = $isDependent;
        $arlApplies = ! in_array($code, self::HEALTH_ONLY_CODES, true);

        return [
            'is_dependent' => $isDependent,
            'parafiscales_allowed' => $parafiscalesAllowed,
            'health_applies' => true,
            'pension_applies' => true,
            'arl_applies' => $arlApplies,
            'ccf_applies' => $ccfApplies,
            'is_proportional' => $isProportional,
            'description' => $isProportional
                ? 'Independiente flexible: aportes proporcionales por días/semanas.'
                : ($isDependent ? 'Dependiente: split empleador/empleado.' : 'Independiente o contratista: cotizante/empresa asume 100%.'),
        ];
    }

    /**
     * Códigos de tipo cotizante para los que el sistema puede liquidar aportes.
     */
    public static function supportedCodes(): array
    {
        return ['01', '02', '03', '04', '12', '19', '23', '40', '51', '57', '59'];
    }

    /**
     * Indica si el código está soportado para liquidación.
     */
    public static function isSupported(string $code): bool
    {
        return in_array(trim($code), self::supportedCodes(), true);
    }
}
