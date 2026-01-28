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
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Patients/Create', [
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name', 'code']),
            'documentTypes' => DocumentType::toArray(),
            'patientTypes' => PatientType::toArray(),
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
            'patient' => new PatientResource($patient->load(['eps', 'holder'])),
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name', 'code']),
            'documentTypes' => DocumentType::toArray(),
            'patientTypes' => PatientType::toArray(),
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
}
