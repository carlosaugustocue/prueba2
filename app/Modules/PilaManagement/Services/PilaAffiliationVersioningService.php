<?php

namespace App\Modules\PilaManagement\Services;

use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\PilaManagement\Models\PilaAffiliation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PilaAffiliationVersioningService
{
    public function createInitial(Affiliate $affiliate, array $data): PilaAffiliation
    {
        return DB::transaction(function () use ($affiliate, $data) {
            $existingCurrent = PilaAffiliation::where('affiliate_id', $affiliate->id)
                ->where('is_current', true)
                ->first();

            if ($existingCurrent) {
                throw new RuntimeException('El afiliado ya tiene una afiliación PILA vigente.');
            }

            return PilaAffiliation::create([
                'affiliate_id' => $affiliate->id,
                ...$this->normalizedPayload($data, true),
            ]);
        });
    }

    public function changeAffiliation(Affiliate $affiliate, array $data): PilaAffiliation
    {
        return DB::transaction(function () use ($affiliate, $data) {
            $current = PilaAffiliation::where('affiliate_id', $affiliate->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            if (! $current) {
                throw new RuntimeException('El afiliado no tiene una afiliación PILA vigente para versionar.');
            }

            // Con el esquema actual (affiliate_id único) solo podemos versionar "lógicamente":
            // se actualiza la afiliación vigente manteniendo is_current = true.
            $current->update($this->normalizedPayload($data, true));

            return $current->refresh();
        });
    }

    private function normalizedPayload(array $data, bool $isCurrent): array
    {
        $clean = $data;

        unset($clean['affiliate_id'], $clean['is_current']);

        $clean['is_current'] = $isCurrent;

        return $clean;
    }
}

