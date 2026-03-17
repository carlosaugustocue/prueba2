<?php

namespace App\Modules\SocialSecurity\Services;

use App\Modules\SocialSecurity\Models\ContributorType;
use Illuminate\Support\Facades\Cache;

/**
 * Reglas de liquidación por tipo de cotizante PILA.
 * Lee las reglas desde la tabla contributor_types (BD); no hay constantes de negocio en código.
 * Ref: docs/NORMATIVA_Y_COMPLEJIDAD_SEGURIDAD_SOCIAL.md sección 3.
 */
final class ContributorTypeRules
{
    private const CACHE_KEY = 'contributor_type_rules';
    private const CACHE_TTL_SECONDS = 3600;

    /**
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
        $all = self::allRules();

        if (isset($all[$code])) {
            return $all[$code];
        }

        return self::defaultRules($code);
    }

    public static function supportedCodes(): array
    {
        return array_keys(self::allRules());
    }

    public static function isSupported(string $code): bool
    {
        return isset(self::allRules()[trim($code)]);
    }

    /**
     * Fuerza recarga desde BD (útil después de ejecutar seeders o actualizar catálogo).
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function allRules(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            return ContributorType::where('is_active', true)
                ->get()
                ->mapWithKeys(fn (ContributorType $ct) => [
                    $ct->code => [
                        'is_dependent' => (bool) $ct->is_dependent,
                        'parafiscales_allowed' => (bool) $ct->parafiscales_allowed,
                        'health_applies' => (bool) $ct->health_applies,
                        'pension_applies' => (bool) $ct->pension_applies,
                        'arl_applies' => (bool) $ct->arl_applies,
                        'ccf_applies' => (bool) $ct->ccf_applies,
                        'is_proportional' => (bool) $ct->is_proportional,
                        'description' => $ct->description ?? $ct->name,
                    ],
                ])
                ->all();
        });
    }

    private static function defaultRules(string $code): array
    {
        return [
            'is_dependent' => false,
            'parafiscales_allowed' => false,
            'health_applies' => true,
            'pension_applies' => true,
            'arl_applies' => true,
            'ccf_applies' => false,
            'is_proportional' => false,
            'description' => "Tipo de cotizante {$code}: reglas no configuradas en BD.",
        ];
    }
}
