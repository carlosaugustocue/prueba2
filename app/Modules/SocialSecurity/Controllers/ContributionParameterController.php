<?php

namespace App\Modules\SocialSecurity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SocialSecurity\Models\ContributionParameter;
use App\Modules\SocialSecurity\Requests\StoreContributionParameterRequest;
use App\Modules\SocialSecurity\Requests\UpdateContributionParameterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContributionParameterController extends Controller
{
    public const TYPES = [
        'HEALTH' => 'Salud',
        'PENSION' => 'Pensión',
        'ARL' => 'ARL',
        'CCF' => 'CCF',
        'SENA' => 'SENA',
        'ICBF' => 'ICBF',
        'FSP' => 'Fondo Solidaridad Pensional',
        'SYSTEM' => 'Sistema (SMLMV, UVT, etc.)',
    ];

    public const VALUE_TYPES = [
        'PERCENTAGE' => 'Porcentaje',
        'AMOUNT' => 'Monto (pesos)',
        'MULTIPLIER' => 'Multiplicador',
    ];

    public function index(Request $request): Response
    {
        $query = ContributionParameter::query()
            ->orderBy('type')
            ->orderBy('subtype')
            ->orderByDesc('valid_from');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('subtype', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('legal_reference', 'like', "%{$term}%");
            });
        }

        $parameters = $query->paginate($request->integer('per_page', 25))->withQueryString();

        return Inertia::render('Admin/Config/ContributionParameters/Index', [
            'parameters' => $parameters,
            'filters' => $request->only(['type', 'search']),
            'types' => self::TYPES,
            'valueTypes' => self::VALUE_TYPES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Config/ContributionParameters/Form', [
            'parameter' => null,
            'types' => self::TYPES,
            'valueTypes' => self::VALUE_TYPES,
        ]);
    }

    public function store(StoreContributionParameterRequest $request): RedirectResponse
    {
        ContributionParameter::create($request->validated());

        return redirect()->route('admin.configuracion.contribution-parameters.index')
            ->with('success', 'Parámetro de aportes creado correctamente.');
    }

    public function edit(ContributionParameter $contributionParameter): Response|RedirectResponse
    {
        return Inertia::render('Admin/Config/ContributionParameters/Form', [
            'parameter' => $contributionParameter->only([
                'id', 'type', 'subtype', 'value', 'value_type',
                'valid_from', 'valid_to', 'description', 'legal_reference',
            ]),
            'types' => self::TYPES,
            'valueTypes' => self::VALUE_TYPES,
        ]);
    }

    public function update(UpdateContributionParameterRequest $request, ContributionParameter $contributionParameter): RedirectResponse
    {
        $contributionParameter->update($request->validated());

        return redirect()->route('admin.configuracion.contribution-parameters.index')
            ->with('success', 'Parámetro actualizado correctamente.');
    }

    public function destroy(ContributionParameter $contributionParameter): RedirectResponse
    {
        $contributionParameter->delete();

        return redirect()->route('admin.configuracion.contribution-parameters.index')
            ->with('success', 'Parámetro eliminado.');
    }
}
