<?php

namespace App\Modules\SocialSecurity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Patients\Models\Affiliate;
use App\Modules\SocialSecurity\Models\IndependentContract;
use App\Modules\SocialSecurity\Models\Payer;
use App\Modules\SocialSecurity\Requests\StoreIndependentContractRequest;
use App\Modules\SocialSecurity\Requests\UpdateIndependentContractRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class IndependentContractController extends Controller
{
    public function index(Affiliate $affiliate): Response|RedirectResponse
    {
        $affiliate->loadMissing('socialSecurityProfile.contributorType');
        $code = $affiliate->socialSecurityProfile?->contributorType?->code;
        if (! in_array($code, ['03', '51', '59'], true)) {
            return redirect()->route('affiliates.show', $affiliate)
                ->with('error', 'Los contratos múltiples aplican a cotizantes independientes (tipos 03, 51 o 59).');
        }

        $contracts = IndependentContract::query()
            ->with('payer:id,name,document_number')
            ->where('affiliate_id', $affiliate->id)
            ->orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (IndependentContract $c) => [
                'id' => $c->id,
                'payer_id' => $c->payer_id,
                'payer_name' => $c->payer?->name,
                'contract_reference' => $c->contract_reference,
                'contract_type' => $c->contract_type,
                'start_date' => $c->start_date?->format('Y-m-d'),
                'end_date' => $c->end_date?->format('Y-m-d'),
                'monthly_income' => (float) $c->monthly_income,
                'risk_class' => $c->risk_class,
                'is_active' => (bool) $c->is_active,
                'notes' => $c->notes,
            ]);

        $payers = Payer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'document_number']);

        return Inertia::render('Affiliates/Contracts', [
            'affiliate' => [
                'id' => $affiliate->id,
                'full_name' => $affiliate->full_name,
                'document_number' => $affiliate->document_number,
                'contributor_type_code' => $affiliate->socialSecurityProfile?->contributorType?->code,
                'contributor_type_name' => $affiliate->socialSecurityProfile?->contributorType?->name,
            ],
            'contracts' => $contracts->values(),
            'payers' => $payers,
            'ibcSuggestion' => null,
            'currentPeriod' => [
                'year' => (int) now()->format('Y'),
                'month' => (int) now()->format('n'),
            ],
        ]);
    }

    public function store(StoreIndependentContractRequest $request, Affiliate $affiliate): RedirectResponse
    {
        $affiliate->loadMissing('socialSecurityProfile.contributorType');
        $this->assertAffiliateSupportsMultipleContracts($affiliate);

        IndependentContract::create(array_merge(
            $request->validated(),
            ['affiliate_id' => $affiliate->id]
        ));

        return redirect()
            ->route('affiliates.contracts.index', $affiliate)
            ->with('success', 'Contrato independiente registrado correctamente.');
    }

    public function update(UpdateIndependentContractRequest $request, Affiliate $affiliate, IndependentContract $contract): RedirectResponse
    {
        $affiliate->loadMissing('socialSecurityProfile.contributorType');
        $this->assertAffiliateSupportsMultipleContracts($affiliate);

        if ((int) $contract->affiliate_id !== (int) $affiliate->id) {
            abort(404);
        }

        $contract->update($request->validated());

        return redirect()
            ->route('affiliates.contracts.index', $affiliate)
            ->with('success', 'Contrato independiente actualizado correctamente.');
    }

    public function destroy(Affiliate $affiliate, IndependentContract $contract): RedirectResponse
    {
        $affiliate->loadMissing('socialSecurityProfile.contributorType');
        $this->assertAffiliateSupportsMultipleContracts($affiliate);

        if ((int) $contract->affiliate_id !== (int) $affiliate->id) {
            abort(404);
        }

        $contract->delete();

        return redirect()
            ->route('affiliates.contracts.index', $affiliate)
            ->with('success', 'Contrato independiente eliminado correctamente.');
    }

    private function assertAffiliateSupportsMultipleContracts(Affiliate $affiliate): void
    {
        $code = $affiliate->socialSecurityProfile?->contributorType?->code;
        if (! in_array($code, ['03', '51', '59'], true)) {
            throw ValidationException::withMessages([
                'affiliate' => 'Los contratos múltiples aplican a cotizantes independientes (tipos 03, 51 o 59).',
            ]);
        }
    }
}

