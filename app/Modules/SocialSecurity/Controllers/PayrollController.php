<?php

namespace App\Modules\SocialSecurity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Affiliates\Enums\PatientType;
use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\SocialSecurity\Enums\PayrollStatus;
use App\Modules\SocialSecurity\Models\Payroll;
use App\Modules\SocialSecurity\Models\SocialSecurityProfile;
use App\Modules\SocialSecurity\Requests\StorePayrollRequest;
use App\Modules\SocialSecurity\Resources\PayrollResource;
use App\Modules\SocialSecurity\Services\PayrollBatchService;
use App\Modules\SocialSecurity\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function __construct(
        private PayrollService $payrollService,
        private PayrollBatchService $payrollBatchService
    ) {}

    public function index(Request $request): Response
    {
        $query = Payroll::query()
            ->with(['affiliate:id,first_name,second_name,last_name,second_last_name,document_number', 'affiliate.socialSecurityProfile.payer:id,name,document_number'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderBy('affiliate_id');

        if ($request->filled('year')) {
            $query->where('year', $request->integer('year'));
        }
        if ($request->filled('month')) {
            $query->where('month', $request->integer('month'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('payer_id')) {
            $payerId = (int) $request->input('payer_id');
            $query->where(function ($q) use ($payerId) {
                $q->whereHas('affiliate.socialSecurityProfile', fn ($profileQuery) => $profileQuery->where('payer_id', $payerId))
                    ->orWhereJsonContains('calculation_metadata->parameters_used->contracts->contract_payer_ids', $payerId)
                    ->orWhereJsonContains('calculation_metadata->parameters_used->contracts->contract_payer_ids', (string) $payerId);
            });
        }
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->whereHas('affiliate', fn ($q) => $q->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('document_number', 'like', "%{$term}%"));
        }
        if ($request->filled('due_date')) {
            $dueDate = $request->input('due_date');
            if ($dueDate === 'today') {
                $query->whereDate('due_date', now()->toDateString());
            } else {
                $query->whereDate('due_date', $dueDate);
            }
        }

        $payrolls = $query->paginate($request->integer('per_page', 15))->withQueryString();

        $payers = \App\Modules\SocialSecurity\Models\Payer::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'document_number']);

        return Inertia::render('Payrolls/Index', [
            'payrolls' => PayrollResource::collection($payrolls),
            'filters' => $request->only(['year', 'month', 'status', 'payer_id', 'search', 'due_date']),
            'payers' => $payers,
            'statusOptions' => PayrollStatus::toSelectArray(),
        ]);
    }

    public function create(Request $request): Response
    {
        $payers = \App\Modules\SocialSecurity\Models\Payer::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'document_number']);
        $currentYear = (int) now()->format('Y');
        $currentMonth = (int) now()->format('n');

        $affiliates = Affiliate::whereHas('socialSecurityProfile')
            ->where('patient_type', PatientType::COTIZANTE)
            ->with(['socialSecurityProfile.payer:id,name,document_number', 'socialSecurityProfile.contributorType:id,code,name'])
            ->orderBy('first_name')
            ->limit(500)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'full_name' => $a->full_name ?? trim(collect([
                    $a->first_name,
                    $a->second_name,
                    $a->last_name,
                    $a->second_last_name,
                ])->filter()->join(' ')),
                'document_number' => $a->document_number,
                'payer_id' => $a->socialSecurityProfile?->payer_id,
                'payer_name' => $a->socialSecurityProfile?->payer?->name,
                'contributor_type_code' => $a->socialSecurityProfile?->contributorType?->code,
            ]);

        return Inertia::render('Payrolls/Create', [
            'payers' => $payers,
            'affiliates' => $affiliates,
            'defaultYear' => $request->integer('year', $currentYear),
            'defaultMonth' => $request->integer('month', $currentMonth),
            'months' => $this->monthsList(),
        ]);
    }

    public function store(StorePayrollRequest $request): RedirectResponse
    {
        $affiliate = Affiliate::findOrFail($request->input('affiliate_id'));
        $year = $request->integer('year');
        $month = $request->integer('month');

        $daysWorked = $request->filled('days_worked') ? $request->integer('days_worked') : null;
        $payroll = $this->payrollService->getOrCreatePayroll($affiliate, $year, $month, $daysWorked);

        return redirect()->route('payrolls.show', $payroll)
            ->with('success', 'Planilla creada. Puede liquidarla desde el detalle.');
    }

    public function show(Payroll $payroll): Response
    {
        $payroll->load([
            'affiliate:id,first_name,second_name,last_name,second_last_name,document_number,document_type',
            'affiliate.socialSecurityProfile.payer:id,name,document_number',
            'affiliate.socialSecurityProfile.contributorType:id,code,name',
            'trackings',
        ]);

        return Inertia::render('Payrolls/Show', [
            'payroll' => new PayrollResource($payroll),
            'statusOptions' => PayrollStatus::toSelectArray(),
        ]);
    }

    /**
     * Preview de aportes (sin guardar). POST con affiliate_id, year, month.
     */
    public function preview(Request $request): Response|RedirectResponse
    {
        $request->validate([
            'affiliate_id' => ['required', 'exists:affiliates,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'days_worked' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $profile = SocialSecurityProfile::with('contributorType')
            ->where('affiliate_id', $request->input('affiliate_id'))
            ->first();

        if (! $profile) {
            throw ValidationException::withMessages(['affiliate_id' => 'El afiliado no tiene perfil de seguridad social.']);
        }

        $errors = $this->payrollService->validateProfileForPayroll($profile, $request->integer('year'), $request->integer('month'));
        if ($errors !== []) {
            throw ValidationException::withMessages(['affiliate_id' => implode(' ', $errors)]);
        }

        $daysWorked = $request->filled('days_worked') ? $request->integer('days_worked') : null;
        $breakdown = $this->payrollService->preview($profile, $request->integer('year'), $request->integer('month'), $daysWorked);

        if ($request->wantsJson()) {
            return response()->json($breakdown->toArray());
        }

        return back()->with('preview', $breakdown->toArray());
    }

    /**
     * Generación masiva de planillas para un año/mes. POST con year, month.
     */
    public function batchGenerate(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $result = $this->payrollBatchService->generateMonthlyPayrolls(
            $request->integer('year'),
            $request->integer('month')
        );

        return response()->json($result);
    }

    /**
     * Liquidación masiva de planillas del mes. POST con year, month y opcional payer_id.
     */
    public function batchSettle(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'payer_id' => ['nullable', 'integer', 'exists:payers,id'],
        ]);

        $result = app(\App\Modules\SocialSecurity\Services\PayrollBatchService::class)->settleMonthlyPayrolls(
            $request->integer('year'),
            $request->integer('month'),
            $request->input('payer_id') ? (int) $request->input('payer_id') : null
        );

        return response()->json($result);
    }

    public function settle(Payroll $payroll): RedirectResponse
    {
        try {
            $this->payrollService->settle($payroll);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        return redirect()->back()->with('success', 'Planilla liquidada correctamente.');
    }

    public function markSent(Payroll $payroll): RedirectResponse
    {
        if ($payroll->status !== PayrollStatus::SETTLED->value) {
            return redirect()->back()->with('error', 'Solo se puede marcar como enviada una planilla liquidada.');
        }
        $this->payrollService->transitionStatus($payroll, PayrollStatus::SENT_TO_CLIENT);
        return redirect()->back()->with('success', 'Planilla marcada como enviada al cliente.');
    }

    public function markPaid(Payroll $payroll): RedirectResponse
    {
        if ($payroll->status !== PayrollStatus::SENT_TO_CLIENT->value) {
            return redirect()->back()->with('error', 'Solo se puede marcar como pagada una planilla enviada al cliente.');
        }
        $this->payrollService->transitionStatus($payroll, PayrollStatus::PAID);
        return redirect()->back()->with('success', 'Planilla marcada como pagada.');
    }

    private function monthsList(): array
    {
        return [
            ['value' => 1, 'label' => 'Enero'],
            ['value' => 2, 'label' => 'Febrero'],
            ['value' => 3, 'label' => 'Marzo'],
            ['value' => 4, 'label' => 'Abril'],
            ['value' => 5, 'label' => 'Mayo'],
            ['value' => 6, 'label' => 'Junio'],
            ['value' => 7, 'label' => 'Julio'],
            ['value' => 8, 'label' => 'Agosto'],
            ['value' => 9, 'label' => 'Septiembre'],
            ['value' => 10, 'label' => 'Octubre'],
            ['value' => 11, 'label' => 'Noviembre'],
            ['value' => 12, 'label' => 'Diciembre'],
        ];
    }
}
