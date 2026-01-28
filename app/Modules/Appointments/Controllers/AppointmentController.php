<?php

namespace App\Modules\Appointments\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\Appointments\Services\AppointmentService;
use App\Modules\Appointments\Requests\CreateAppointmentRequest;
use App\Modules\Appointments\Requests\UpdateAppointmentRequest;
use App\Modules\Appointments\Resources\AppointmentResource;
use App\Modules\Appointments\Enums\AppointmentStatus;
use App\Modules\Appointments\Enums\AppointmentType;
use App\Modules\Appointments\Enums\Priority;
use App\Modules\Patients\Models\Eps;
use App\Modules\Patients\Enums\DocumentType;
use App\Modules\Patients\Enums\PatientType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(protected AppointmentService $appointmentService) {}

    public function dashboard(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => $this->appointmentService->getDashboardStats(),
            'todayAppointments' => AppointmentResource::collection($this->appointmentService->getTodayAppointments()),
        ]);
    }

    public function index(Request $request): Response
    {
        $appointments = $this->appointmentService->search(
            $request->only(['search', 'status', 'priority', 'type', 'patient_id', 'today', 'active']),
            $request->integer('per_page', 15)
        );

        return Inertia::render('Appointments/Index', [
            'appointments' => AppointmentResource::collection($appointments),
            'filters' => $request->only(['search', 'status', 'priority', 'type', 'today', 'active']),
            'statuses' => AppointmentStatus::toArray(),
            'types' => AppointmentType::toArray(),
            'priorities' => Priority::toArray(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Appointments/Create', [
            'statuses' => AppointmentStatus::toArray(),
            'types' => AppointmentType::toArray(),
            'priorities' => Priority::toArray(),
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name', 'code']),
            'documentTypes' => DocumentType::toArray(),
            'patientTypes' => PatientType::toArray(),
        ]);
    }

    public function store(CreateAppointmentRequest $request): RedirectResponse
    {
        $appointment = $this->appointmentService->create($request->validated());
        $message = 'Cita creada correctamente.';
        if ($request->boolean('send_confirmation')) {
            $message .= ' La confirmación será enviada al paciente.';
        }
        return redirect()->route('appointments.show', $appointment)->with('success', $message);
    }

    public function show(Appointment $appointment): Response
    {
        $appointmentWithDetails = $this->appointmentService->getWithDetails($appointment);
        
        // Debug: ver datos crudos
        // dd($appointmentWithDetails->toArray());
        
        return Inertia::render('Appointments/Show', [
            'appointment' => new AppointmentResource($appointmentWithDetails),
            'statuses' => AppointmentStatus::toArray(),
        ]);
    }

    public function edit(Appointment $appointment): Response
    {
        $appointmentWithDetails = $this->appointmentService->getWithDetails($appointment);
        
        // Debug desactivado
        
        return Inertia::render('Appointments/Edit', [
            'appointment' => new AppointmentResource($appointmentWithDetails),
            'statuses' => AppointmentStatus::toArray(),
            'types' => AppointmentType::toArray(),
            'priorities' => Priority::toArray(),
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $this->appointmentService->update($appointment, $request->validated());
        return redirect()->route('appointments.show', $appointment)->with('success', 'Cita actualizada correctamente.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $appointment->delete();
        return redirect()->route('appointments.index')->with('success', 'Cita eliminada correctamente.');
    }

    public function changeStatus(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        $request->validate(['status' => 'required|string']);
        $newStatus = AppointmentStatus::from($request->input('status'));

        if (! $this->appointmentService->changeStatus($appointment, $newStatus)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'No se puede cambiar a ese estado.'], 422);
            }
            return back()->with('error', 'No se puede cambiar a ese estado.');
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Estado actualizado.']);
        }
        return back()->with('success', 'Estado actualizado correctamente.');
    }

    public function sendConfirmation(Appointment $appointment): RedirectResponse|JsonResponse
    {
        if (! $this->appointmentService->sendConfirmation($appointment)) {
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'No se puede enviar confirmación.'], 422);
            }
            return back()->with('error', 'No se puede enviar confirmación.');
        }

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Confirmación enviada.']);
        }
        return back()->with('success', 'Confirmación enviada correctamente.');
    }
}
