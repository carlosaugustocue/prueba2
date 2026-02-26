<?php

namespace App\Modules\HistoriaClinica\Services;

use App\Modules\HistoriaClinica\Enums\HistoriaClinicaEstado;
use App\Modules\HistoriaClinica\Models\HistoriaClinica;
use App\Modules\Patients\Models\Affiliate;
use Illuminate\Support\Facades\DB;

class HistoriaClinicaService
{
    /**
     * Obtiene o crea la historia clínica del afiliado (una por afiliado, estado ACTIVA).
     */
    public function getOrCreateForAffiliate(Affiliate $affiliate): HistoriaClinica
    {
        $hc = HistoriaClinica::where('affiliate_id', $affiliate->id)->first();

        if ($hc) {
            return $hc;
        }

        return DB::transaction(function () use ($affiliate) {
            $numero = $this->generarNumeroHistoria();
            $hc = HistoriaClinica::create([
                'numero_historia' => $numero,
                'affiliate_id' => $affiliate->id,
                'fecha_apertura' => now()->toDateString(),
                'estado' => HistoriaClinicaEstado::ACTIVA,
                'created_by' => auth()->id(),
            ]);
            AuditoriaHcService::logCreate('historia_clinica', (string) $hc->id, $hc->toArray());
            return $hc;
        });
    }

    private function generarNumeroHistoria(): string
    {
        $year = now()->year;
        $ultimo = HistoriaClinica::where('numero_historia', 'like', "HC-{$year}-%")
            ->orderByDesc('id')
            ->value('numero_historia');

        if (! $ultimo) {
            $sec = 1;
        } else {
            $parts = explode('-', $ultimo);
            $sec = (int) end($parts) + 1;
        }

        return sprintf('HC-%s-%05d', $year, $sec);
    }
}
