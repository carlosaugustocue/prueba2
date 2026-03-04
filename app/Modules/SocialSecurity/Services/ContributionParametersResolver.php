<?php

namespace App\Modules\SocialSecurity\Services;

use App\Modules\SocialSecurity\Models\ContributionParameter;
use Carbon\CarbonInterface;

/**
 * Punto único de lectura de parámetros de aportes y sistema.
 * Todo el código que necesite porcentajes, SMLMV, topes IBC o rangos de día de pago
 * debe usar este servicio; no se permiten magic numbers ni constantes hardcodeadas.
 */
class ContributionParametersResolver
{
    private const TYPE_SYSTEM = 'SYSTEM';
    private const SUBTYPE_SMLMV = 'SMLMV';
    private const SUBTYPE_TRANSPORT_AID = 'TRANSPORT_AID';
    private const SUBTYPE_UVT = 'UVT';
    private const SUBTYPE_IBC_MIN_SMLMV = 'IBC_MIN_SMLMV';
    private const SUBTYPE_IBC_MAX_SMLMV = 'IBC_MAX_SMLMV';
    private const SUBTYPE_PAYMENT_DAY_MIN = 'PAYMENT_DAY_MIN';
    private const SUBTYPE_PAYMENT_DAY_MAX = 'PAYMENT_DAY_MAX';
    private const SUBTYPE_PENSION_PILLAR_THRESHOLD_SMLMV = 'PENSION_PILLAR_THRESHOLD_SMLMV';
    private const SUBTYPE_INDEPENDENT_IBC_PERCENT = 'INDEPENDENT_IBC_PERCENT';

    /**
     * Obtiene el valor de un parámetro vigente para la fecha dada.
     */
    public function getValue(string $type, string $subtype, CarbonInterface|string $date): ?float
    {
        return ContributionParameter::getValueForDate($type, $subtype, $this->normalizeDate($date));
    }

    /**
     * Obtiene todos los parámetros vigentes para la fecha, agrupados por type => [subtype => value].
     */
    public function getParametersForDate(CarbonInterface|string $date): array
    {
        return ContributionParameter::getAllValidForDate($this->normalizeDate($date));
    }

    /**
     * Salario Mínimo Legal Mensual Vigente (pesos) para la fecha.
     */
    public function getSmlmv(CarbonInterface|string $date): ?float
    {
        return $this->getValue(self::TYPE_SYSTEM, self::SUBTYPE_SMLMV, $date);
    }

    /**
     * Auxilio de transporte (pesos) para la fecha.
     */
    public function getTransportAid(CarbonInterface|string $date): ?float
    {
        return $this->getValue(self::TYPE_SYSTEM, self::SUBTYPE_TRANSPORT_AID, $date);
    }

    /**
     * UVT (pesos) para la fecha.
     */
    public function getUvt(CarbonInterface|string $date): ?float
    {
        return $this->getValue(self::TYPE_SYSTEM, self::SUBTYPE_UVT, $date);
    }

    /**
     * IBC mínimo en pesos (SMLMV * IBC_MIN_SMLMV) para la fecha.
     */
    public function getIbcMin(CarbonInterface|string $date): ?float
    {
        $smlmv = $this->getSmlmv($date);
        $mult = $this->getValue(self::TYPE_SYSTEM, self::SUBTYPE_IBC_MIN_SMLMV, $date);

        if ($smlmv === null || $mult === null) {
            return null;
        }

        return round($smlmv * $mult, 0);
    }

    /**
     * IBC máximo en pesos (SMLMV * IBC_MAX_SMLMV) para la fecha.
     */
    public function getIbcMax(CarbonInterface|string $date): ?float
    {
        $smlmv = $this->getSmlmv($date);
        $mult = $this->getValue(self::TYPE_SYSTEM, self::SUBTYPE_IBC_MAX_SMLMV, $date);

        if ($smlmv === null || $mult === null) {
            return null;
        }

        return round($smlmv * $mult, 0);
    }

    /**
     * Rango válido de día hábil de pago PILA (mínimo y máximo, ej. 2 y 16).
     * Para validaciones de payment_day.
     */
    public function getPaymentDayBounds(CarbonInterface|string $date): array
    {
        $min = $this->getValue(self::TYPE_SYSTEM, self::SUBTYPE_PAYMENT_DAY_MIN, $date);
        $max = $this->getValue(self::TYPE_SYSTEM, self::SUBTYPE_PAYMENT_DAY_MAX, $date);

        return [
            'min' => $min !== null ? (int) $min : 2,
            'max' => $max !== null ? (int) $max : 16,
        ];
    }

    /**
     * Umbral en SMLMV para distribución pensional (ej. 2.3) — Ley 2381/2024.
     */
    public function getPensionPillarThresholdSmlmv(CarbonInterface|string $date): ?float
    {
        return $this->getValue(self::TYPE_SYSTEM, self::SUBTYPE_PENSION_PILLAR_THRESHOLD_SMLMV, $date);
    }

    /**
     * Porcentaje de ingreso mensualizado para calcular IBC de independientes con contratos.
     * Referencia base en Colombia: 40% (si no hay parametrización vigente).
     */
    public function getIndependentIbcPercent(CarbonInterface|string $date): ?float
    {
        return $this->getValue(self::TYPE_SYSTEM, self::SUBTYPE_INDEPENDENT_IBC_PERCENT, $date);
    }

    /**
     * Porcentaje de salud total vigente para la fecha.
     */
    public function getHealthTotalPercent(CarbonInterface|string $date): ?float
    {
        return $this->getValue('HEALTH', 'TOTAL', $date);
    }

    /**
     * Porcentaje de ARL para una clase de riesgo (1-5) vigente para la fecha.
     */
    public function getArlPercentForClass(CarbonInterface|string $date, int $riskClass): ?float
    {
        if ($riskClass < 1 || $riskClass > 5) {
            return null;
        }

        return $this->getValue('ARL', 'RISK_' . $riskClass, $date);
    }

    private function normalizeDate(CarbonInterface|string $date): string
    {
        if ($date instanceof CarbonInterface) {
            return $date->format('Y-m-d');
        }

        return \Carbon\Carbon::parse($date)->format('Y-m-d');
    }
}
