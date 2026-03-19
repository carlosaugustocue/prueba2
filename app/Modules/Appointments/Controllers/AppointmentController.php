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
use App\Modules\Appointments\Enums\PhoneCommunicationCategory;
use App\Modules\AppointmentRequests\Models\AppointmentRequest;
use App\Modules\AppointmentRequests\Resources\AppointmentRequestResource;
use App\Modules\Affiliates\Models\Eps;
use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\Affiliates\Resources\AffiliateResource;
use App\Modules\Affiliates\Enums\DocumentType;
use App\Modules\Affiliates\Enums\PatientType;
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
            'inProgressRequests' => AppointmentRequestResource::collection($this->appointmentService->getInProgressRequests()),
        ]);
    }

    public function index(Request $request): Response
    {
        $appointments = $this->appointmentService->search(
            $request->only(['search', 'status', 'priority', 'type', 'affiliate_id', 'today', 'active']),
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
        $preselectedAffiliate = null;
        if ($request->filled('affiliate_id')) {
            $affiliate = Affiliate::with('socialSecurityProfile.eps')->find($request->integer('affiliate_id'));
            if ($affiliate) {
                $preselectedAffiliate = new AffiliateResource($affiliate);
            }
        }

        return Inertia::render('Appointments/Create', [
            'statuses' => AppointmentStatus::toArray(),
            'types' => AppointmentType::toArray(),
            'priorities' => Priority::toArray(),
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name', 'code']),
            'documentTypes' => DocumentType::toArray(),
            'patientTypes' => PatientType::toArray(),
            'preselectedAffiliate' => $preselectedAffiliate,
        ]);
    }

    public function store(CreateAppointmentRequest $request): RedirectResponse
    {
        $appointment = $this->appointmentService->create($request->validated());
        $message = 'Cita creada correctamente.';
        if ($request->boolean('send_confirmation')) {
            $message .= ' La confirmación será enviada al afiliado.';
        }

        $redirect = redirect()->route('appointments.show', $appointment)->with('success', $message);

        // RF-AUT-14: advertir si la IPS/lugar no coincide con la IPS autorizada
        $appointment->load('authorization');
        if ($appointment->authorization
            && $appointment->authorization->authorized_ips_name
            && $appointment->location_name
        ) {
            $ips = mb_strtolower(trim($appointment->authorization->authorized_ips_name));
            $loc = mb_strtolower(trim($appointment->location_name));
            if ($ips !== '' && $loc !== '' && ! str_contains($loc, $ips) && ! str_contains($ips, $loc)) {
                $redirect->with(
                    'warning',
                    'La IPS o lugar indicado no coincide con la IPS autorizada por la EPS: ' . $appointment->authorization->authorized_ips_name . '. Verifique si la cita debe agendarse en la IPS autorizada.'
                );
            }
        }

        return $redirect;
    }

    public function show(Appointment $appointment): Response
    {
        $appointmentWithDetails = $this->appointmentService->getWithDetails($appointment);
        
        // Debug: ver datos crudos
        // dd($appointmentWithDetails->toArray());
        
        return Inertia::render('Appointments/Show', [
            'appointment' => new AppointmentResource($appointmentWithDetails),
            'statuses' => AppointmentStatus::toArray(),
            'phoneCategories' => PhoneCommunicationCategory::toArray(),
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
        $appointmentId = $appointment->id;

        // Cancelar envíos WhatsApp pendientes (confirmación y recordatorio 24h) antes de eliminar
        $this->appointmentService->cancelPendingReminders($appointment);

        $appointment->delete();

        // Dejar la solicitud sin cita para que no quede enlace roto y se pueda crear otra cita
        AppointmentRequest::where('appointment_id', $appointmentId)->update(['appointment_id' => null]);

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
        return back()->with('success', 'Confirmación encolada para envío por WhatsApp.');
    }

    public function logPhoneCommunication(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        $request->validate([
            'category' => ['required', 'string'],
            'note' => ['required', 'string', 'max:500'],
        ]);

        $category = PhoneCommunicationCategory::from($request->input('category'));

        $appointment->communications()->create([
            'user_id' => auth()->id(),
            'channel' => 'phone',
            'category' => $category->value,
            'note' => trim((string) $request->input('note')),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Comunicación registrada.']);
        }
        return back()->with('success', 'Comunicación telefónica registrada.');
    }
}
