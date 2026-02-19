<?php

namespace App\Modules\Patients\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Patients\Models\Affiliate;
use App\Modules\Patients\Models\Eps;
use App\Modules\SocialSecurity\Models\Afp;
use App\Modules\SocialSecurity\Models\Arp;
use App\Modules\SocialSecurity\Models\Ccf;
use App\Modules\SocialSecurity\Models\ClientType;
use App\Modules\SocialSecurity\Models\ContributorType;
use App\Modules\SocialSecurity\Models\AccountingRegistry;
use App\Modules\SocialSecurity\Models\PaymentOperator;
use App\Modules\Patients\Services\AffiliateService;
use App\Modules\Patients\Requests\CreateAffiliateRequest;
use App\Modules\Patients\Requests\UpdateAffiliateRequest;
use App\Modules\Patients\Resources\AffiliateResource;
use App\Modules\Patients\Enums\DocumentType;
use App\Modules\Patients\Enums\PatientType;
use App\Modules\Patients\Enums\RelationshipType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AffiliateController extends Controller
{
    public function __construct(protected AffiliateService $affiliateService) {}

    public function index(Request $request): Response
    {
        $affiliates = $this->affiliateService->search(
            $request->only(['search', 'eps_id', 'patient_type']),
            $request->integer('per_page', 15)
        );

        return Inertia::render('Affiliates/Index', [
            'affiliates' => AffiliateResource::collection($affiliates),
            'filters' => $request->only(['search', 'eps_id', 'patient_type']),
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name', 'code']),
            'documentTypes' => DocumentType::toArray(),
            'patientTypes' => PatientType::toArray(),
            'relationshipTypes' => RelationshipType::toArray(),
        ]);
    }

    public function create(Request $request): Response
    {
        $preselectedHolder = null;
        if ($request->has('holder_id')) {
            $preselectedHolder = Affiliate::where('id', $request->holder_id)
                ->where('patient_type', 'cotizante')
                ->with(['socialSecurityProfile.eps', 'socialSecurityProfile.clientType', 'socialSecurityProfile.contributorType', 'socialSecurityProfile.afp', 'socialSecurityProfile.arp', 'socialSecurityProfile.ccf', 'socialSecurityProfile.paymentOperator', 'socialSecurityProfile.accountingRegistry'])
                ->first(['id', 'first_name', 'second_name', 'last_name', 'second_last_name', 'document_number', 'document_type', 'phone', 'whatsapp', 'address']);
        }

        return Inertia::render('Affiliates/Create', [
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name', 'code']),
            'clientTypes' => ClientType::active()->orderBy('name')->get(['id', 'name', 'code']),
            'contributorTypes' => ContributorType::active()->orderBy('code')->get(['id', 'code', 'name']),
            'afpList' => Afp::active()->orderBy('name')->get(['id', 'name', 'code']),
            'arpList' => Arp::active()->orderBy('name')->get(['id', 'name', 'code']),
            'ccfList' => Ccf::active()->orderBy('name')->get(['id', 'name', 'code']),
            'paymentOperatorList' => PaymentOperator::active()->orderBy('name')->get(['id', 'name', 'code']),
            'accountingRegistries' => AccountingRegistry::active()->orderBy('name')->get(['id', 'name', 'code']),
            'documentTypes' => DocumentType::toArray(),
            'patientTypes' => PatientType::toArray(),
            'relationshipTypes' => RelationshipType::toArray(),
            'preselectedHolder' => $preselectedHolder ? [
                'id' => $preselectedHolder->id,
                'full_name' => $preselectedHolder->full_name,
                'document_number' => $preselectedHolder->document_number,
                'document_type_abbreviation' => $preselectedHolder->document_type?->abbreviation(),
                'eps_id' => $preselectedHolder->socialSecurityProfile?->eps_id,
                'eps' => $preselectedHolder->socialSecurityProfile?->eps ? [
                    'id' => $preselectedHolder->socialSecurityProfile->eps->id,
                    'name' => $preselectedHolder->socialSecurityProfile->eps->name,
                    'code' => $preselectedHolder->socialSecurityProfile->eps->code,
                ] : null,
                'phone' => $preselectedHolder->phone,
                'whatsapp' => $preselectedHolder->whatsapp,
                'address' => $preselectedHolder->address,
            ] : null,
        ]);
    }

    public function store(CreateAffiliateRequest $request): RedirectResponse
    {
        $affiliate = $this->affiliateService->create($request->validated());
        return redirect()->route('affiliates.show', $affiliate)->with('success', 'Afiliado registrado correctamente.');
    }

    public function show(Affiliate $affiliate): Response
    {
        return Inertia::render('Affiliates/Show', [
            'affiliate' => new AffiliateResource($affiliate->load([
                'socialSecurityProfile.eps',
                'socialSecurityProfile.clientType',
                'socialSecurityProfile.contributorType',
                'socialSecurityProfile.afp',
                'socialSecurityProfile.arp',
                'socialSecurityProfile.ccf',
                'socialSecurityProfile.paymentOperator',
                'socialSecurityProfile.accountingRegistry',
                'holder',
                'beneficiaries',
                'appointments',
            ])),
        ]);
    }

    public function edit(Affiliate $affiliate): Response
    {
        return Inertia::render('Affiliates/Edit', [
            'affiliate' => new AffiliateResource($affiliate->load([
                'socialSecurityProfile.eps',
                'socialSecurityProfile.clientType',
                'socialSecurityProfile.contributorType',
                'socialSecurityProfile.afp',
                'socialSecurityProfile.arp',
                'socialSecurityProfile.ccf',
                'socialSecurityProfile.paymentOperator',
                'socialSecurityProfile.accountingRegistry',
                'holder',
                'beneficiaries',
            ])),
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name', 'code']),
            'clientTypes' => ClientType::active()->orderBy('name')->get(['id', 'name', 'code']),
            'contributorTypes' => ContributorType::active()->orderBy('code')->get(['id', 'code', 'name']),
            'afpList' => Afp::active()->orderBy('name')->get(['id', 'name', 'code']),
            'arpList' => Arp::active()->orderBy('name')->get(['id', 'name', 'code']),
            'ccfList' => Ccf::active()->orderBy('name')->get(['id', 'name', 'code']),
            'paymentOperatorList' => PaymentOperator::active()->orderBy('name')->get(['id', 'name', 'code']),
            'accountingRegistries' => AccountingRegistry::active()->orderBy('name')->get(['id', 'name', 'code']),
            'documentTypes' => DocumentType::toArray(),
            'patientTypes' => PatientType::toArray(),
            'relationshipTypes' => RelationshipType::toArray(),
        ]);
    }

    public function update(UpdateAffiliateRequest $request, Affiliate $affiliate): RedirectResponse
    {
        $this->affiliateService->update($affiliate, $request->validated());
        return redirect()->route('affiliates.show', $affiliate)->with('success', 'Afiliado actualizado correctamente.');
    }

    public function destroy(Affiliate $affiliate): RedirectResponse
    {
        $affiliate->delete();
        return redirect()->route('affiliates.index')->with('success', 'Afiliado eliminado correctamente.');
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['term' => 'required|string|min:2']);
        $affiliates = $this->affiliateService->searchForAutocomplete($request->input('term'));
        return response()->json(AffiliateResource::collection($affiliates));
    }

    public function storeApi(CreateAffiliateRequest $request): JsonResponse
    {
        $affiliate = $this->affiliateService->create($request->validated());
        $affiliate->load([
            'socialSecurityProfile.eps',
            'socialSecurityProfile.afp',
            'socialSecurityProfile.arp',
            'socialSecurityProfile.ccf',
            'socialSecurityProfile.paymentOperator',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Afiliado creado correctamente.',
            'data' => new AffiliateResource($affiliate),
        ], 201);
    }

    public function searchHolders(Request $request): JsonResponse
    {
        $request->validate(['term' => 'required|string|min:2']);

        $holders = Affiliate::where('patient_type', 'cotizante')
            ->where(function ($query) use ($request) {
                $term = $request->input('term');
                $query->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('document_number', 'like', "%{$term}%");
            })
            ->with(['socialSecurityProfile.eps', 'socialSecurityProfile.clientType', 'socialSecurityProfile.contributorType', 'socialSecurityProfile.afp', 'socialSecurityProfile.arp', 'socialSecurityProfile.ccf', 'socialSecurityProfile.paymentOperator', 'socialSecurityProfile.accountingRegistry'])
            ->limit(10)
            ->get([
                'id', 'first_name', 'second_name', 'last_name', 'second_last_name',
                'document_type', 'document_number', 'phone', 'whatsapp', 'address', 'neighborhood',
            ]);

        return response()->json([
            'data' => $holders->map(fn ($holder) => [
                'id' => $holder->id,
                'full_name' => $holder->full_name,
                'document_type_abbreviation' => $holder->document_type?->abbreviation(),
                'document_number' => $holder->document_number,
                'eps_id' => $holder->socialSecurityProfile?->eps_id,
                'eps' => $holder->socialSecurityProfile?->eps ? ['id' => $holder->socialSecurityProfile->eps->id, 'name' => $holder->socialSecurityProfile->eps->name] : null,
                'phone' => $holder->phone,
                'whatsapp' => $holder->whatsapp,
                'address' => $holder->address,
            ]),
        ]);
    }

    public function getBeneficiaries(Affiliate $affiliate): JsonResponse
    {
        if ($affiliate->patient_type->value !== 'cotizante') {
            return response()->json(['error' => 'Este afiliado no es cotizante.'], 400);
        }

        $beneficiaries = $affiliate->beneficiaries()->with([
            'socialSecurityProfile.eps',
            'socialSecurityProfile.afp',
            'socialSecurityProfile.arp',
            'socialSecurityProfile.ccf',
            'socialSecurityProfile.paymentOperator',
        ])->get();

        return response()->json([
            'data' => AffiliateResource::collection($beneficiaries),
        ]);
    }
}
