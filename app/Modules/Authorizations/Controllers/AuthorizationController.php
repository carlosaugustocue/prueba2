<?php

namespace App\Modules\Authorizations\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Authorizations\Models\Authorization;
use App\Modules\Authorizations\Models\AuthorizationDocument;
use App\Modules\Authorizations\Requests\StoreAuthorizationRequest;
use App\Modules\Authorizations\Requests\UpdateAuthorizationRequest;
use App\Modules\Authorizations\Requests\StoreAuthorizationDocumentRequest;
use App\Modules\Authorizations\Resources\AuthorizationResource;
use App\Modules\Authorizations\Enums\AuthorizationStatus;
use App\Modules\AppointmentRequests\Enums\RequestStatus;
use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\Affiliates\Models\Eps;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AuthorizationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Authorization::query()
            ->with([
                'affiliate:id,uuid,first_name,second_name,last_name,second_last_name,document_number',
                'eps:id,name,code',
                'appointmentRequest:id,status,appointment_id',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->status($request->input('status'));
        }
        if ($request->filled('eps_id')) {
            $query->where('eps_id', $request->integer('eps_id'));
        }
        if ($request->filled('affiliate_id')) {
            $query->where('affiliate_id', $request->integer('affiliate_id'));
        }
        if ($request->filled('service_type')) {
            $query->where('service_type', 'like', '%' . $request->input('service_type') . '%');
        }
        if ($request->filled('authorization_number')) {
            $query->where('authorization_number', 'like', '%' . $request->input('authorization_number') . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // RF-AUT-18 / Dashboard: aprobadas sin cita vinculada
        if ($request->boolean('without_appointment')) {
            $query->approved()->whereDoesntHave('appointment');
        }
        // RF-AUT-18 / Dashboard: próximas a vencer (vigencia en los próximos 7 días)
        if ($request->boolean('expiring_soon')) {
            $query->approved()
                ->whereNotNull('valid_until')
                ->where('valid_until', '>=', now()->startOfDay())
                ->where('valid_until', '<=', now()->addDays(7)->endOfDay());
        }

        // Por defecto ocultar autorizaciones ya utilizadas (solicitud con cita ya creada)
        $hideUsed = $request->has('hide_used') ? $request->boolean('hide_used') : true;
        if ($hideUsed) {
            $query->where(function ($q) {
                $q->whereNull('appointment_request_id')
                    ->orWhereHas('appointmentRequest', fn ($q2) => $q2->whereNull('appointment_id'));
            });
        }

        $authorizations = $query->paginate($request->integer('per_page', 15))->withQueryString();

        return Inertia::render('Authorizations/Index', [
            'authorizations' => AuthorizationResource::collection($authorizations),
            'filters' => $request->only(['status', 'eps_id', 'affiliate_id', 'service_type', 'authorization_number', 'date_from', 'date_to', 'hide_used', 'without_appointment', 'expiring_soon']),
            'statuses' => AuthorizationStatus::toArray(),
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function create(Request $request): Response
    {
        $affiliates = Affiliate::whereServiconliManaged()
            ->with('socialSecurityProfile.eps')
            ->orderBy('first_name')
            ->get();

        $preselectedAffiliate = null;
        $appointmentRequestId = $request->integer('appointment_request_id');
        if ($request->filled('affiliate_id')) {
            $aff = Affiliate::with('socialSecurityProfile.eps')->find($request->integer('affiliate_id'));
            if ($aff) {
                $preselectedAffiliate = (new \App\Modules\Affiliates\Resources\AffiliateResource($aff))->toArray(request());
            }
        } elseif ($appointmentRequestId) {
            $req = \App\Modules\AppointmentRequests\Models\AppointmentRequest::with('affiliate.socialSecurityProfile.eps')->find($appointmentRequestId);
            if ($req?->affiliate) {
                $preselectedAffiliate = (new \App\Modules\Affiliates\Resources\AffiliateResource($req->affiliate))->toArray(request());
            }
        }

        return Inertia::render('Authorizations/Create', [
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name', 'code']),
            'affiliates' => $affiliates->map(fn ($a) => [
                'id' => $a->id,
                'full_name' => $a->full_name,
                'document_number' => $a->document_number,
                'eps_id' => $a->socialSecurityProfile?->eps_id,
                'eps_name' => $a->socialSecurityProfile?->eps?->name,
            ]),
            'preselectedAffiliate' => $preselectedAffiliate,
            'appointment_request_id' => $appointmentRequestId ?: null,
        ]);
    }

    public function store(StoreAuthorizationRequest $request): RedirectResponse
    {
        $data = $request->getData();
        $authorization = Authorization::create($data);

        if ($data['appointment_request_id'] ?? null) {
            $ar = \App\Modules\AppointmentRequests\Models\AppointmentRequest::find($data['appointment_request_id']);
            if ($ar) {
                $ar->update(['status' => RequestStatus::PENDING_AUTHORIZATION]);
            }
        }

        return redirect()->route('authorizations.show', $authorization)->with('success', 'Autorización registrada correctamente.');
    }

    public function show(Authorization $authorization): Response
    {
        $authorization->load([
            'affiliate',
            'eps',
            'appointmentRequest',
            'stateHistories.user:id,name',
            'documents',
        ]);

        return Inertia::render('Authorizations/Show', [
            'authorization' => new AuthorizationResource($authorization),
            'statuses' => AuthorizationStatus::toArray(),
            'documentTypes' => [
                ['value' => 'order_medica', 'label' => 'Orden médica'],
                ['value' => 'resultados', 'label' => 'Resultados'],
                ['value' => 'historia_clinica', 'label' => 'Historia clínica'],
                ['value' => 'otro', 'label' => 'Otro'],
            ],
        ]);
    }

    public function update(UpdateAuthorizationRequest $request, Authorization $authorization): RedirectResponse
    {
        $data = $request->validated();

        if (isset($data['status'])) {
            $newStatus = AuthorizationStatus::from($data['status']);
            if (! $authorization->changeStatus($newStatus, auth()->id(), $data['notes'] ?? null)) {
                return back()->with('error', 'No se puede cambiar a ese estado.');
            }
            unset($data['status'], $data['notes']);
        }

        $authorization->updated_by = auth()->id();
        $authorization->fill(array_filter($data));
        $authorization->save();

        if ($authorization->status === AuthorizationStatus::APPROVED && $authorization->appointment_request_id) {
            $authorization->appointmentRequest?->update(['status' => RequestStatus::IN_PROGRESS]);
        }
        if ($authorization->status === AuthorizationStatus::DENIED && ($request->filled('denial_reason'))) {
            // denial_reason already saved in fill()
        }

        return back()->with('success', 'Autorización actualizada correctamente.');
    }

    public function storeDocument(StoreAuthorizationDocumentRequest $request, Authorization $authorization): RedirectResponse
    {
        $file = $request->file('file');
        $path = $file->store('authorizations/' . $authorization->id, 'local');

        $authorization->documents()->create([
            'type' => $request->input('type'),
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Documento adjuntado correctamente.');
    }

    public function downloadDocument(Authorization $authorization, AuthorizationDocument $document): \Symfony\Component\HttpFoundation\StreamedResponse|RedirectResponse
    {
        if ($document->authorization_id !== $authorization->id) {
            abort(404);
        }
        if (! $document->existsInStorage()) {
            return back()->with('error', 'El archivo no está disponible.');
        }

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_name,
            ['Content-Type' => $document->mime_type]
        );
    }

    /**
     * Desvincular la autorización de la solicitud actual (permite corregir asignaciones erróneas).
     * Solo si la solicitud vinculada aún no tiene cita creada.
     */
    public function detachRequest(Authorization $authorization): RedirectResponse
    {
        $request = $authorization->appointmentRequest;

        if (! $request) {
            return back()->with('info', 'Esta autorización no está vinculada a ninguna solicitud.');
        }

        if ($request->appointment_id) {
            return back()->with('error', 'No se puede desvincular: la solicitud ya tiene una cita creada con esta autorización.');
        }

        $authorization->update(['appointment_request_id' => null]);
        $request->update(['status' => RequestStatus::PENDING_AUTHORIZATION]);

        return back()->with('success', 'Autorización desvinculada de la solicitud. La solicitud vuelve a estado "Pendiente de autorización".');
    }

    /**
     * Desactivar (soft delete) la autorización. Útil para ocultar registros erróneos sin borrar historial.
     */
    public function destroy(Authorization $authorization): RedirectResponse
    {
        $authorization->delete();

        return redirect()
            ->route('authorizations.index')
            ->with('success', 'Autorización desactivada correctamente.');
    }
}
