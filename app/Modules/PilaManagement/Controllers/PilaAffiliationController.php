<?php

namespace App\Modules\PilaManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Patients\Models\Affiliate;
use App\Modules\Patients\Models\Eps;
use App\Modules\PilaManagement\Models\PilaAffiliation;
use App\Modules\PilaManagement\Models\PilaCotizanteType;
use App\Modules\PilaManagement\Models\PilaEmployer;
use App\Modules\PilaManagement\Models\PilaRiskClass;
use App\Modules\SocialSecurity\Models\Afp;
use App\Modules\SocialSecurity\Models\Arp;
use App\Modules\SocialSecurity\Models\Ccf;
use App\Modules\PilaManagement\Requests\StorePilaAffiliationRequest;
use App\Modules\PilaManagement\Requests\UpdatePilaAffiliationRequest;
use App\Modules\PilaManagement\Resources\PilaAffiliationResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PilaAffiliationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = PilaAffiliation::query()
            ->with(['affiliate', 'employer', 'cotizanteType'])
            ->orderByDesc('updated_at');

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->whereHas('affiliate', fn ($q) => $q->where('document_number', 'like', "%{$term}%")
                ->orWhere('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%"));
        }

        $affiliations = $query->paginate($request->integer('per_page', 15))->withQueryString();

        return Inertia::render('PilaAffiliations/Index', [
            'affiliations' => PilaAffiliationResource::collection($affiliations),
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('PilaAffiliations/Create', $this->formData());
    }

    public function store(StorePilaAffiliationRequest $request): RedirectResponse
    {
        $affiliation = PilaAffiliation::create($request->validated());

        return redirect()->route('affiliations.show', $affiliation)
            ->with('success', 'Afiliación PILA registrada correctamente.');
    }

    public function show(PilaAffiliation $affiliation): Response
    {
        $affiliation->load(['affiliate', 'employer', 'cotizanteType', 'riskClass', 'eps', 'afp', 'arp', 'ccf']);

        return Inertia::render('PilaAffiliations/Show', [
            'affiliation' => new PilaAffiliationResource($affiliation),
        ]);
    }

    public function edit(PilaAffiliation $affiliation): Response
    {
        $affiliation->load(['affiliate', 'employer', 'cotizanteType']);

        return Inertia::render('PilaAffiliations/Edit', [
            'affiliation' => new PilaAffiliationResource($affiliation),
            ...$this->formData(),
        ]);
    }

    public function update(UpdatePilaAffiliationRequest $request, PilaAffiliation $affiliation): RedirectResponse
    {
        $affiliation->update($request->validated());

        return redirect()->route('affiliations.show', $affiliation)
            ->with('success', 'Afiliación PILA actualizada correctamente.');
    }

    public function destroy(PilaAffiliation $affiliation): RedirectResponse
    {
        $affiliation->delete();

        return redirect()->route('affiliations.index')->with('success', 'Afiliación eliminada correctamente.');
    }

    private function formData(): array
    {
        $affiliates = Affiliate::query()
            ->orderBy('first_name')
            ->limit(500)
            ->get(['id', 'first_name', 'second_name', 'last_name', 'second_last_name', 'document_number'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'label' => $a->full_name . ' — ' . $a->document_number,
            ]);

        $employers = PilaEmployer::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'document_type', 'document_number', 'check_digit'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'label' => $e->name,
                'description' => trim($e->document_type . ' ' . $e->document_number . ($e->check_digit ? '-' . $e->check_digit : '')),
            ]);

        $cotizanteTypes = PilaCotizanteType::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn ($t) => [
                'id' => $t->id,
                'label' => "{$t->code} — {$t->name}",
            ]);

        $riskClasses = PilaRiskClass::query()
            ->where('is_active', true)
            ->orderBy('level')
            ->get(['id', 'level', 'class_name', 'description', 'rate'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'label' => $r->level === 0 ? '0 — No aplica' : "{$r->level} ({$r->class_name}) — {$r->description}",
                'description' => $r->rate ? ('Tarifa: ' . $r->rate) : '',
            ]);

        $epsOptions = Eps::active()->orderBy('name')->get(['id', 'name', 'code'])->map(fn ($e) => [
            'id' => $e->id,
            'label' => $e->name,
            'description' => $e->code,
        ]);
        $afpOptions = Afp::active()->orderBy('name')->get(['id', 'name', 'code'])->map(fn ($e) => [
            'id' => $e->id,
            'label' => $e->name,
            'description' => $e->code,
        ]);
        $arpOptions = Arp::active()->orderBy('name')->get(['id', 'name', 'code'])->map(fn ($e) => [
            'id' => $e->id,
            'label' => $e->name,
            'description' => $e->code,
        ]);
        $ccfOptions = Ccf::active()->orderBy('name')->get(['id', 'name', 'code'])->map(fn ($e) => [
            'id' => $e->id,
            'label' => $e->name,
            'description' => $e->code,
        ]);

        return [
            'affiliateOptions' => $affiliates,
            'employerOptions' => $employers,
            'cotizanteTypeOptions' => $cotizanteTypes,
            'riskClassOptions' => $riskClasses,
            'epsOptions' => $epsOptions,
            'afpOptions' => $afpOptions,
            'arpOptions' => $arpOptions,
            'ccfOptions' => $ccfOptions,
        ];
    }
}

