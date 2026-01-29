<?php

namespace App\Modules\AppointmentRequests\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AppointmentRequests\Models\AppointmentRequest;
use App\Modules\AppointmentRequests\Resources\AppointmentRequestResource;
use App\Modules\AppointmentRequests\Requests\CreateAppointmentRequestRequest;
use App\Modules\AppointmentRequests\Enums\RequestStatus;
use App\Modules\Appointments\Enums\AppointmentType;
use App\Modules\Appointments\Enums\Priority;
use App\Modules\Patients\Models\Eps;
use App\Modules\Patients\Enums\DocumentType;
use App\Modules\Patients\Enums\PatientType;
use App\Modules\Auth\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AppointmentRequest::with(['patient.eps', 'assignee', 'appointment'])
            ->orderByRaw("CASE 
                WHEN status = 'pending' THEN 1 
                WHEN status = 'in_progress' THEN 2 
                ELSE 3 
            END")
            ->orderByDesc('requested_at');

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate($request->integer('per_page', 20))
            ->withQueryString();

        // Estadísticas rápidas
        $stats = [
            'pending' => AppointmentRequest::pending()->count(),
            'in_progress' => AppointmentRequest::inProgress()->count(),
            'completed_today' => AppointmentRequest::completed()
                ->whereDate('completed_at', today())
                ->count(),
        ];

        return Inertia::render('AppointmentRequests/Index', [
            'requests' => AppointmentRequestResource::collection($requests),
            'filters' => $request->only(['status', 'priority', 'assigned_to', 'search']),
            'statuses' => RequestStatus::toArray(),
            'priorities' => Priority::toArray(),
            'operators' => User::whereHas('role', fn($q) => $q->where('name', 'operator'))
                ->orWhereHas('role', fn($q) => $q->where('name', 'admin'))
                ->get(['id', 'name']),
            'stats' => $stats,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('AppointmentRequests/Create', [
            'types' => AppointmentType::toArray(),
            'priorities' => Priority::toArray(),
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name', 'code']),
            'documentTypes' => DocumentType::toArray(),
            'patientTypes' => PatientType::toArray(),
        ]);
    }

    public function store(CreateAppointmentRequestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        $data['status'] = RequestStatus::PENDING->value;
        // Tracking oculto: el timestamp de solicitud se registra automáticamente al crear la solicitud
        $data['requested_at'] = now();

        $appointmentRequest = AppointmentRequest::create($data);

        return redirect()
            ->route('appointment-requests.show', $appointmentRequest)
            ->with('success', 'Solicitud registrada correctamente.');
    }

    public function show(AppointmentRequest $appointmentRequest): Response
    {
        $appointmentRequest->load(['patient.eps', 'creator', 'assignee', 'appointment']);

        return Inertia::render('AppointmentRequests/Show', [
            'appointmentRequest' => new AppointmentRequestResource($appointmentRequest),
            'statuses' => RequestStatus::toArray(),
            'types' => AppointmentType::toArray(),
            'priorities' => Priority::toArray(),
        ]);
    }

    /**
     * Iniciar el trámite de una solicitud
     */
    public function start(AppointmentRequest $appointmentRequest): RedirectResponse
    {
        if ($appointmentRequest->startProcessing(auth()->id())) {
            return back()->with('success', 'Has tomado esta solicitud. ¡Comienza a tramitar!');
        }

        return back()->with('error', 'No se puede iniciar esta solicitud.');
    }

    /**
     * Crear cita desde la solicitud
     */
    public function createAppointment(AppointmentRequest $appointmentRequest): Response
    {
        $appointmentRequest->load(['patient.eps']);

        // Si no está en progreso, iniciarla
        if ($appointmentRequest->status === RequestStatus::PENDING) {
            $appointmentRequest->startProcessing(auth()->id());
        }

        return Inertia::render('Appointments/Create', [
            'types' => AppointmentType::toArray(),
            'priorities' => Priority::toArray(),
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name', 'code']),
            'documentTypes' => DocumentType::toArray(),
            'patientTypes' => PatientType::toArray(),
            // Datos precargados de la solicitud
            'fromRequest' => [
                'id' => $appointmentRequest->id,
                'patient' => [
                    'id' => $appointmentRequest->patient->id,
                    'full_name' => $appointmentRequest->patient->full_name,
                    'document_type_abbreviation' => $appointmentRequest->patient->document_type?->abbreviation(),
                    'document_number' => $appointmentRequest->patient->document_number,
                    'phone' => $appointmentRequest->patient->phone,
                    'whatsapp' => $appointmentRequest->patient->whatsapp,
                    'whatsapp_number' => $appointmentRequest->patient->getWhatsAppNumber(),
                    'eps' => $appointmentRequest->patient->eps ? [
                        'id' => $appointmentRequest->patient->eps->id,
                        'name' => $appointmentRequest->patient->eps->name,
                    ] : null,
                ],
                'type' => $appointmentRequest->type->value,
                'priority' => $appointmentRequest->priority->value,
                'specialty' => $appointmentRequest->specialty,
                'client_notes' => $appointmentRequest->client_notes,
            ],
        ]);
    }

    /**
     * Marcar solicitud como fallida
     */
    public function markFailed(Request $request, AppointmentRequest $appointmentRequest): RedirectResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        if ($appointmentRequest->markAsFailed($request->reason)) {
            return redirect()
                ->route('appointment-requests.index')
                ->with('success', 'Solicitud marcada como no obtenida.');
        }

        return back()->with('error', 'No se pudo actualizar la solicitud.');
    }

    /**
     * Cancelar solicitud
     */
    public function cancel(Request $request, AppointmentRequest $appointmentRequest): RedirectResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        if ($appointmentRequest->cancel($request->reason)) {
            return redirect()
                ->route('appointment-requests.index')
                ->with('success', 'Solicitud cancelada.');
        }

        return back()->with('error', 'No se pudo cancelar la solicitud.');
    }

    /**
     * Guardar anotaciones internas (operadora)
     */
    public function saveNotes(Request $request, AppointmentRequest $appointmentRequest): RedirectResponse
    {
        $request->validate([
            'operator_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $user = $request->user();
        $isAdmin = $user?->role?->name === 'admin';

        if (! $isAdmin) {
            // Solo la operadora asignada puede editar notas
            if ($appointmentRequest->assigned_to && $appointmentRequest->assigned_to !== $user->id) {
                abort(403, 'No tienes permiso para editar esta solicitud.');
            }

            // Solo mientras esté activa (pendiente o en proceso)
            if (! in_array($appointmentRequest->status, RequestStatus::activeStatuses(), true)) {
                return back()->with('error', 'No se pueden modificar anotaciones en una solicitud cerrada.');
            }
        }

        $appointmentRequest->operator_notes = $request->input('operator_notes');
        $appointmentRequest->save();

        return back()->with('success', 'Anotaciones internas guardadas.');
    }

    public function destroy(AppointmentRequest $appointmentRequest): RedirectResponse
    {
        $appointmentRequest->delete();

        return redirect()
            ->route('appointment-requests.index')
            ->with('success', 'Solicitud eliminada.');
    }
}
