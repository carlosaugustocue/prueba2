<?php

namespace App\Modules\AppointmentRequests\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AppointmentRequests\Models\AppointmentRequest;
use App\Modules\AppointmentRequests\Resources\AppointmentRequestResource;
use App\Modules\AppointmentRequests\Requests\CreateAppointmentRequestRequest;
use App\Modules\AppointmentRequests\Enums\RequestStatus;
use App\Modules\Appointments\Enums\AppointmentType;
use App\Modules\Appointments\Enums\Priority;
use App\Modules\Affiliates\Models\Eps;
use App\Modules\Affiliates\Enums\DocumentType;
use App\Modules\Authorizations\Models\Authorization;
use App\Modules\Authorizations\Enums\AuthorizationStatus;
use App\Modules\Affiliates\Enums\AffiliateType;
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
        $query = AppointmentRequest::with(['affiliate.socialSecurityProfile.eps', 'assignee', 'appointment'])
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
            $query->whereHas('affiliate', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('second_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('second_last_name', 'like', "%{$search}%")
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
            'operators' => User::whereHas('role', fn($q) => $q->whereIn('name', ['agent', 'supervisor', 'admin']))
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
            'patientTypes' => AffiliateType::toArray(),
        ]);
    }

    public function store(CreateAppointmentRequestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        $data['status'] = RequestStatus::PENDING->value;
        $data['requires_authorization'] = $request->boolean('requires_authorization');
        // Tracking oculto: el timestamp de solicitud se registra automáticamente al crear la solicitud
        $data['requested_at'] = now();

        $appointmentRequest = AppointmentRequest::create($data);

        return redirect()
            ->route('appointment-requests.show', $appointmentRequest)
            ->with('success', 'Solicitud registrada correctamente.');
    }

    public function show(AppointmentRequest $appointmentRequest): Response
    {
        $appointmentRequest->load(['affiliate.socialSecurityProfile.eps', 'creator', 'assignee', 'appointment', 'notes.author', 'authorization']);

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
        $appointmentRequest->load(['affiliate.socialSecurityProfile.eps', 'authorization']);

        // Si no está en progreso, iniciarla
        if ($appointmentRequest->status === RequestStatus::PENDING) {
            $appointmentRequest->startProcessing(auth()->id());
        }

        $fromRequest = [
            'id' => $appointmentRequest->id,
            'affiliate' => [
                'id' => $appointmentRequest->affiliate->id,
                'first_name' => $appointmentRequest->affiliate->first_name,
                'last_name' => $appointmentRequest->affiliate->last_name,
                'full_name' => $appointmentRequest->affiliate->full_name,
                'document_type_abbreviation' => $appointmentRequest->affiliate->document_type?->abbreviation(),
                'document_number' => $appointmentRequest->affiliate->document_number,
                'birth_date' => $appointmentRequest->affiliate->birth_date?->format('Y-m-d'),
                'phone' => $appointmentRequest->affiliate->phone,
                'whatsapp' => $appointmentRequest->affiliate->whatsapp,
                'whatsapp_number' => $appointmentRequest->affiliate->getWhatsAppNumber(),
                'eps' => $appointmentRequest->affiliate->socialSecurityProfile?->eps ? [
                    'id' => $appointmentRequest->affiliate->socialSecurityProfile->eps->id,
                    'name' => $appointmentRequest->affiliate->socialSecurityProfile->eps->name,
                ] : null,
            ],
            'type' => $appointmentRequest->type->value,
            'priority' => $appointmentRequest->priority->value,
            'specialty' => $appointmentRequest->specialty,
            'client_notes' => $appointmentRequest->client_notes,
        ];

        if ($appointmentRequest->authorization) {
            $auth = $appointmentRequest->authorization;
            $fromRequest['authorization'] = [
                'id' => $auth->id,
                'authorization_number' => $auth->authorization_number,
                'radicado_number' => $auth->radicado_number,
                'valid_until_formatted' => $auth->valid_until?->format('d/m/Y'),
                'valid_until' => $auth->valid_until?->format('Y-m-d'),
                'service_type' => $auth->service_type,
                'authorized_ips_name' => $auth->authorized_ips_name,
            ];
        }

        return Inertia::render('Appointments/Create', [
            'types' => AppointmentType::toArray(),
            'priorities' => Priority::toArray(),
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name', 'code']),
            'documentTypes' => DocumentType::toArray(),
            'patientTypes' => AffiliateType::toArray(),
            'fromRequest' => $fromRequest,
        ]);
    }

    /**
     * Marcar solicitud como fallida
     */
    public function markFailed(Request $request, AppointmentRequest $appointmentRequest): RedirectResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        if ($appointmentRequest->markAsFailed($request->reason)) {
            $reason = trim((string) ($request->reason ?? ''));
            if ($reason !== '') {
                $appointmentRequest->notes()->create([
                    'user_id' => auth()->id(),
                    'note' => "No obtenida: {$reason}",
                ]);
                $appointmentRequest->operator_notes = "No obtenida: {$reason}";
                $appointmentRequest->save();
            }

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
            $reason = trim((string) ($request->reason ?? ''));
            if ($reason !== '') {
                $appointmentRequest->notes()->create([
                    'user_id' => auth()->id(),
                    'note' => "Cancelación: {$reason}",
                ]);
                $appointmentRequest->operator_notes = "Cancelación: {$reason}";
                $appointmentRequest->save();
            }

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
            'note' => ['required_without:operator_notes', 'nullable', 'string', 'max:5000'],
            'operator_notes' => ['required_without:note', 'nullable', 'string', 'max:5000'],
        ], [
            'note.required_without' => 'La anotación no puede estar vacía.',
            'operator_notes.required_without' => 'La anotación no puede estar vacía.',
        ]);

        $user = $request->user();
        $role = (string) ($user?->role?->name ?? '');
        $isAdmin = $role === 'admin';
        $isAgentOrSupervisor = in_array($role, ['agent', 'supervisor'], true);

        if (! $isAdmin) {
            // Solo mientras esté activa (pendiente o en proceso)
            if (! in_array($appointmentRequest->status, RequestStatus::activeStatuses(), true)) {
                return back()->with('error', 'No se pueden modificar anotaciones en una solicitud cerrada.');
            }

            // Agentes/supervisores pueden agregar anotaciones aunque no estén asignados.
            // Otros roles: solo el asignado.
            if (! $isAgentOrSupervisor) {
                if ($appointmentRequest->assigned_to && $appointmentRequest->assigned_to !== $user->id) {
                    abort(403, 'No tienes permiso para editar esta solicitud.');
                }
            }
        }

        $noteText = trim((string) ($request->input('note') ?? $request->input('operator_notes') ?? ''));
        if ($noteText === '') {
            return back()->with('error', 'La anotación no puede estar vacía.');
        }

        // Siempre usar el usuario autenticado actual (evita que se guarde otro usuario por sesión/request)
        $appointmentRequest->notes()->create([
            'user_id' => auth()->id(),
            'note' => $noteText,
        ]);

        // Mantener un "resumen" en la solicitud para compatibilidad (última anotación)
        $appointmentRequest->operator_notes = $noteText;
        $appointmentRequest->save();

        return back()->with('success', 'Anotaciones internas guardadas.');
    }

    /**
     * Vincular una autorización ya aprobada del afiliado a esta solicitud.
     */
    public function attachAuthorization(Request $request, AppointmentRequest $appointmentRequest): RedirectResponse
    {
        $request->validate([
            'authorization_id' => ['required', 'integer', 'exists:authorizations,id'],
        ]);

        $authorization = Authorization::find($request->integer('authorization_id'));

        if ($authorization->affiliate_id !== $appointmentRequest->affiliate_id) {
            return back()->with('error', 'La autorización no corresponde al afiliado de esta solicitud.');
        }

        if ($authorization->status !== AuthorizationStatus::APPROVED) {
            return back()->with('error', 'Solo se pueden vincular autorizaciones aprobadas.');
        }

        if ($authorization->valid_until && $authorization->valid_until->isPast()) {
            return back()->with('error', 'La autorización está vencida.');
        }

        $authorization->update(['appointment_request_id' => $appointmentRequest->id]);
        $appointmentRequest->update(['status' => RequestStatus::IN_PROGRESS]);

        return back()->with('success', 'Autorización vinculada. Ya puede crear la cita.');
    }

    public function destroy(AppointmentRequest $appointmentRequest): RedirectResponse
    {
        $appointmentRequest->delete();

        return redirect()
            ->route('appointment-requests.index')
            ->with('success', 'Solicitud eliminada.');
    }
}
