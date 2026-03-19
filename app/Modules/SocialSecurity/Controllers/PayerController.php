<?php

namespace App\Modules\SocialSecurity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SocialSecurity\Models\Payer;
use App\Modules\SocialSecurity\Requests\StorePayerRequest;
use App\Modules\SocialSecurity\Requests\UpdatePayerRequest;
use App\Modules\SocialSecurity\Resources\PayerResource;
use App\Modules\Affiliates\Enums\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PayerController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Payer::query()->orderBy('name');

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('document_number', 'like', "%{$term}%")
                    ->orWhere('contact_person', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if ($request->has('is_active')) {
            if ($request->input('is_active') === '1') {
                $query->where('is_active', true);
            } elseif ($request->input('is_active') === '0') {
                $query->where('is_active', false);
            }
        }

        $payers = $query->withCount('socialSecurityProfiles')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return Inertia::render('Payers/Index', [
            'payers' => PayerResource::collection($payers),
            'filters' => $request->only(['search', 'is_active']),
            'documentTypes' => DocumentType::toArray(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Payers/Create', [
            'documentTypes' => DocumentType::toArray(),
        ]);
    }

    public function store(StorePayerRequest $request): RedirectResponse
    {
        $payer = Payer::create($request->validated());
        return redirect()->route('payers.show', $payer)->with('success', 'Pagador registrado correctamente.');
    }

    public function show(Payer $payer): Response
    {
        $payer->loadCount('socialSecurityProfiles')
            ->load(['socialSecurityProfiles.affiliate:id,first_name,second_name,last_name,second_last_name,document_number,document_type']);

        return Inertia::render('Payers/Show', [
            'payer' => new PayerResource($payer),
        ]);
    }

    public function edit(Payer $payer): Response
    {
        return Inertia::render('Payers/Edit', [
            'payer' => new PayerResource($payer),
            'documentTypes' => DocumentType::toArray(),
        ]);
    }

    public function update(UpdatePayerRequest $request, Payer $payer): RedirectResponse
    {
        $payer->update($request->validated());
        return redirect()->route('payers.show', $payer)->with('success', 'Pagador actualizado correctamente.');
    }

    public function destroy(Payer $payer): RedirectResponse
    {
        if ($payer->socialSecurityProfiles()->exists()) {
            return redirect()->back()->with('error', 'No se puede eliminar: tiene afiliados asociados.');
        }
        $payer->delete();
        return redirect()->route('payers.index')->with('success', 'Pagador eliminado correctamente.');
    }
}
