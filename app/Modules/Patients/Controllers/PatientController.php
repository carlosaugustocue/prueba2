<?php

namespace App\Modules\Patients\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Patients\Models\Patient;
use App\Modules\Patients\Models\Eps;
use App\Modules\Patients\Services\PatientService;
use App\Modules\Patients\Requests\CreatePatientRequest;
use App\Modules\Patients\Requests\UpdatePatientRequest;
use App\Modules\Patients\Resources\PatientResource;
use App\Modules\Patients\Enums\DocumentType;
use App\Modules\Patients\Enums\PatientType;
use App\Modules\Patients\Enums\RelationshipType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function __construct(protected PatientService $patientService) {}

    public function index(Request $request): Response
    {
        $patients = $this->patientService->search(
            $request->only(['search', 'eps_id', 'patient_type']),
            $request->integer('per_page', 15)
        );

        return Inertia::render('Patients/Index', [
            'patients' => PatientResource::collection($patients),
            'filters' => $request->only(['search', 'eps_id', 'patient_type']),
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name', 'code']),
            'documentTypes' => DocumentType::toArray(),
            'patientTypes' => PatientType::toArray(),
            'relationshipTypes' => RelationshipType::toArray(),
        ]);
    }

    public function create(Request $request): Response
    {
        // Si viene un holder_id preseleccionado (para agregar beneficiario desde el perfil del cotizante)
        $preselectedHolder = null;
        if ($request->has('holder_id')) {
            $preselectedHolder = Patient::where('id', $request->holder_id)
                ->where('patient_type', 'cotizante')
                ->first(['id', 'first_name', 'last_name', 'document_number', 'document_type']);
        }

        return Inertia::render('Patients/Create', [
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name', 'code']),
            'documentTypes' => DocumentType::toArray(),
            'patientTypes' => PatientType::toArray(),
            'relationshipTypes' => RelationshipType::toArray(),
            'preselectedHolder' => $preselectedHolder ? [
                'id' => $preselectedHolder->id,
                'full_name' => $preselectedHolder->full_name,
                'document_number' => $preselectedHolder->document_number,
                'document_type_abbreviation' => $preselectedHolder->document_type?->abbreviation(),
            ] : null,
        ]);
    }

    public function store(CreatePatientRequest $request): RedirectResponse
    {
        $patient = $this->patientService->create($request->validated());
        return redirect()->route('patients.show', $patient)->with('success', 'Paciente registrado correctamente.');
    }

    public function show(Patient $patient): Response
    {
        return Inertia::render('Patients/Show', [
            'patient' => new PatientResource($patient->load(['eps', 'holder', 'beneficiaries', 'appointments'])),
        ]);
    }

    public function edit(Patient $patient): Response
    {
        return Inertia::render('Patients/Edit', [
            'patient' => new PatientResource($patient->load(['eps', 'holder', 'beneficiaries'])),
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name', 'code']),
            'documentTypes' => DocumentType::toArray(),
            'patientTypes' => PatientType::toArray(),
            'relationshipTypes' => RelationshipType::toArray(),
        ]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $this->patientService->update($patient, $request->validated());
        return redirect()->route('patients.show', $patient)->with('success', 'Paciente actualizado correctamente.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $patient->delete();
        return redirect()->route('patients.index')->with('success', 'Paciente eliminado correctamente.');
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['term' => 'required|string|min:2']);
        $patients = $this->patientService->searchForAutocomplete($request->input('term'));
        return response()->json(PatientResource::collection($patients));
    }

    /**
     * Crear paciente vía API (AJAX)
     */
    public function storeApi(CreatePatientRequest $request): JsonResponse
    {
        $patient = $this->patientService->create($request->validated());
        $patient->load('eps');
        
        return response()->json([
            'success' => true,
            'message' => 'Paciente creado correctamente.',
            'data' => new PatientResource($patient),
        ], 201);
    }

    /**
     * Buscar cotizantes para seleccionar como titular
     */
    public function searchHolders(Request $request): JsonResponse
    {
        $request->validate(['term' => 'required|string|min:2']);
        
        $holders = Patient::where('patient_type', 'cotizante')
            ->where(function ($query) use ($request) {
                $term = $request->input('term');
                $query->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('document_number', 'like', "%{$term}%");
            })
            ->with('eps:id,name')
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'document_type', 'document_number', 'eps_id']);

        return response()->json([
            'data' => $holders->map(fn($holder) => [
                'id' => $holder->id,
                'full_name' => $holder->full_name,
                'document_type_abbreviation' => $holder->document_type?->abbreviation(),
                'document_number' => $holder->document_number,
                'eps' => $holder->eps ? ['name' => $holder->eps->name] : null,
            ]),
        ]);
    }

    /**
     * Obtener beneficiarios de un cotizante
     */
    public function getBeneficiaries(Patient $patient): JsonResponse
    {
        if ($patient->patient_type->value !== 'cotizante') {
            return response()->json(['error' => 'Este paciente no es cotizante.'], 400);
        }

        $beneficiaries = $patient->beneficiaries()
            ->with('eps:id,name')
            ->get();

        return response()->json([
            'data' => PatientResource::collection($beneficiaries),
        ]);
    }
}
