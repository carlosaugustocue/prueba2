<?php

namespace App\Modules\PilaManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\Affiliates\Models\Eps;
use App\Modules\PilaManagement\Models\PilaAffiliation;
use App\Modules\PilaManagement\Models\PilaCotizanteType;
use App\Modules\PilaManagement\Models\PilaEmployer;
use App\Modules\PilaManagement\Models\PilaRiskClass;
use App\Modules\PilaManagement\Models\AffiliateNote;
use App\Modules\SocialSecurity\Models\Afp;
use App\Modules\SocialSecurity\Models\Arp;
use App\Modules\SocialSecurity\Models\Ccf;
use App\Modules\SocialSecurity\Models\NoveltyType;
use App\Modules\SocialSecurity\Models\PaymentOperator;
use Illuminate\Support\Str;
use App\Modules\PilaManagement\Requests\StorePilaAffiliationRequest;
use App\Modules\PilaManagement\Requests\UpdatePilaAffiliationRequest;
use App\Modules\PilaManagement\Resources\PilaAffiliationResource;
use App\Modules\PilaManagement\Services\DeadlineService;
use App\Modules\SocialSecurity\Services\ContributionParametersResolver;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Schema;

class PilaAffiliationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = PilaAffiliation::query()
            ->with(['affiliate', 'employer', 'cotizanteType'])
            ->orderByDesc('updated_at');

        $search = request('search');
        if ($search) {
            $terms = array_filter(preg_split('/\s+/', trim($search)));
            $hasFullNameColumn = Schema::hasColumn('affiliates', 'full_name');

            if (! empty($terms)) {
                $query->where(function ($q) use ($terms, $hasFullNameColumn) {
                    foreach ($terms as $term) {
                        $q->where(function ($inner) use ($term, $hasFullNameColumn) {
                            $inner
                                ->whereHas('affiliate', function ($a) use ($term, $hasFullNameColumn) {
                                    $a->where(function ($a2) use ($term, $hasFullNameColumn) {
                                        if ($hasFullNameColumn) {
                                            $a2->where('full_name', 'LIKE', "%{$term}%");
                                        }

                                        $firstWhereApplied = $hasFullNameColumn;

                                        if (! $firstWhereApplied) {
                                            $a2->where('first_name', 'LIKE', "%{$term}%");
                                        }

                                        $a2
                                            ->orWhere('second_name', 'LIKE', "%{$term}%")
                                            ->orWhere('last_name', 'LIKE', "%{$term}%")
                                            ->orWhere('second_last_name', 'LIKE', "%{$term}%")
                                            ->orWhere('document_number', 'LIKE', "%{$term}%");
                                    });
                                })
                                ->orWhereHas('employer', function ($e) use ($term) {
                                    $e->where('name', 'LIKE', "%{$term}%")
                                        ->orWhere('document_number', 'LIKE', "%{$term}%");
                                });
                        });
                    }
                });
            }
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }
        if ($request->filled('pila_operator')) {
            $query->where('pila_operator', $request->input('pila_operator'));
        }
        if ($request->filled('employer_id')) {
            $query->where('employer_id', $request->integer('employer_id'));
        }
        if ($request->filled('payment_business_day')) {
            $query->whereHas('employer', fn ($q) => $q->where('payment_business_day', $request->integer('payment_business_day')));
        }
        if ($request->filled('eps_id')) {
            $query->where('eps_id', $request->integer('eps_id'));
        }
        if ($request->filled('afp_id')) {
            $query->where('afp_id', $request->integer('afp_id'));
        }
        if ($request->filled('arp_id')) {
            $query->where('arp_id', $request->integer('arp_id'));
        }
        if ($request->filled('ccf_id')) {
            $query->where('ccf_id', $request->integer('ccf_id'));
        }
        if ($request->filled('last_payment_period')) {
            $query->where('last_payment_period', $request->input('last_payment_period'));
        }

        $affiliations = $query->paginate($request->integer('per_page', 15))->withQueryString();

        return Inertia::render('PilaAffiliations/Index', [
            'affiliations' => PilaAffiliationResource::collection($affiliations),
            'filters' => $request->only([
                'search', 'payment_status', 'pila_operator', 'employer_id', 'payment_business_day',
                'eps_id', 'afp_id', 'arp_id', 'ccf_id', 'last_payment_period',
            ]),
            'filterOptions' => $this->indexFilterOptions(),
        ]);
    }

    public function export(Request $request)
    {
        $query = PilaAffiliation::query()
            ->with(['affiliate', 'employer', 'cotizanteType'])
            ->orderByDesc('updated_at');

        $search = request('search');
        if ($search) {
            $terms = array_filter(preg_split('/\s+/', trim($search)));
            $hasFullNameColumn = Schema::hasColumn('affiliates', 'full_name');

            if (! empty($terms)) {
                $query->where(function ($q) use ($terms, $hasFullNameColumn) {
                    foreach ($terms as $term) {
                        $q->where(function ($inner) use ($term, $hasFullNameColumn) {
                            $inner
                                ->whereHas('affiliate', function ($a) use ($term, $hasFullNameColumn) {
                                    $a->where(function ($a2) use ($term, $hasFullNameColumn) {
                                        if ($hasFullNameColumn) {
                                            $a2->where('full_name', 'LIKE', "%{$term}%");
                                        }

                                        $firstWhereApplied = $hasFullNameColumn;

                                        if (! $firstWhereApplied) {
                                            $a2->where('first_name', 'LIKE', "%{$term}%");
                                        }

                                        $a2
                                            ->orWhere('second_name', 'LIKE', "%{$term}%")
                                            ->orWhere('last_name', 'LIKE', "%{$term}%")
                                            ->orWhere('second_last_name', 'LIKE', "%{$term}%")
                                            ->orWhere('document_number', 'LIKE', "%{$term}%");
                                    });
                                })
                                ->orWhereHas('employer', function ($e) use ($term) {
                                    $e->where('name', 'LIKE', "%{$term}%")
                                        ->orWhere('document_number', 'LIKE', "%{$term}%");
                                });
                        });
                    }
                });
            }
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }
        if ($request->filled('pila_operator')) {
            $query->where('pila_operator', $request->input('pila_operator'));
        }
        if ($request->filled('employer_id')) {
            $query->where('employer_id', $request->integer('employer_id'));
        }
        if ($request->filled('payment_business_day')) {
            $query->whereHas('employer', fn ($q) => $q->where('payment_business_day', $request->integer('payment_business_day')));
        }
        if ($request->filled('eps_id')) {
            $query->where('eps_id', $request->integer('eps_id'));
        }
        if ($request->filled('afp_id')) {
            $query->where('afp_id', $request->integer('afp_id'));
        }
        if ($request->filled('arp_id')) {
            $query->where('arp_id', $request->integer('arp_id'));
        }
        if ($request->filled('ccf_id')) {
            $query->where('ccf_id', $request->integer('ccf_id'));
        }
        if ($request->filled('last_payment_period')) {
            $query->where('last_payment_period', $request->input('last_payment_period'));
        }

        $affiliations = $query->get();

        $deadlineService = app(DeadlineService::class);
        $year = now()->format('Y');
        $month = now()->format('n');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Afiliaciones');

        $headers = [
            'Afiliado',
            'Documento',
            'Empleador',
            'Tipo cotizante',
            'Operador PILA',
            'Día hábil',
            'Fecha límite estimada',
            'Estado pago',
        ];

        foreach ($headers as $i => $header) {
            $col = $i + 1;
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $sheet->getStyleByColumnAndRow($col, 1)->getFont()->setBold(true);
        }

        $row = 2;
        foreach ($affiliations as $a) {
            $affiliate = $a->affiliate;
            $employer = $a->employer;
            $docType = $affiliate?->document_type?->abbreviation() ?? '';
            $docNumber = $affiliate?->document_number ?? '';
            $document = trim($docType . ' ' . $docNumber);

            $day = $employer?->payment_business_day ?? null;
            $nextDue = null;
            if ($day) {
                $nextDue = $deadlineService->dueDateForPeriodByPaymentDay(
                    (int) $year,
                    (int) $month,
                    (int) $day
                )->toDateString();
            }

            $sheet->setCellValue("A{$row}", $affiliate?->full_name ?? '');
            $sheet->setCellValue("B{$row}", $document);
            $sheet->setCellValue("C{$row}", $employer?->name ?? '');
            $sheet->setCellValue("D{$row}", $a->cotizanteType?->code ?? '');
            $sheet->setCellValue("E{$row}", $a->pila_operator ?? '');
            $sheet->setCellValue("F{$row}", $day !== null ? (string) $day : '');
            $sheet->setCellValue("G{$row}", $nextDue ?? '');
            $sheet->setCellValue("H{$row}", $a->payment_status ?? '');

            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        $fileName = 'pila-afiliaciones-' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $fileName,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    public function create(): Response
    {
        return Inertia::render('PilaAffiliations/Create', $this->formData());
    }

    public function store(StorePilaAffiliationRequest $request): RedirectResponse
    {
        $affiliation = PilaAffiliation::create($request->validated());

        return redirect()->route('affiliations.show', $affiliation)
            ->with('success', 'Afiliación PILA registrada correctamente.');
    }

    public function show(Request $request, PilaAffiliation $affiliation): Response
    {
        $affiliation->load(['affiliate', 'employer', 'cotizanteType', 'riskClass', 'eps', 'afp', 'arp', 'ccf']);

        $auditLogs = null;
        if ($request->user()?->role?->name === 'admin') {
            $auditLogsPaginator = $affiliation->credentialLogs()
                ->latest()
                ->paginate(10);

            $auditLogs = $auditLogsPaginator->through(function ($log) {
                return [
                    'id' => $log->id,
                    'created_at' => $log->created_at?->toIso8601String(),
                    'user' => $log->user?->name,
                    'ip_address' => $log->ip_address,
                    'action' => $log->action?->value ?? (string) $log->action,
                    'credential_kind' => $log->credential_kind?->value ?? (string) $log->credential_kind,
                    'credential_id' => $log->credential_id,
                ];
            });
        }

        $notes = AffiliateNote::query()
            ->where('affiliate_id', $affiliation->affiliate_id)
            ->with('creator:id,name')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'content' => $n->content,
                'is_pinned' => (bool) $n->is_pinned,
                'created_at' => $n->created_at?->toIso8601String(),
                'created_by' => $n->creator?->name,
            ]);

        return Inertia::render('PilaAffiliations/Show', [
            'affiliation' => new PilaAffiliationResource($affiliation),
            'audit_logs' => $auditLogs,
            'notes' => $notes,
        ]);
    }

    public function edit(PilaAffiliation $affiliation): Response
    {
        $affiliation->load(['affiliate', 'employer', 'cotizanteType']);

        $period = (string) ($affiliation->last_payment_period ?? '');
        if (preg_match('/^\d{6}$/', $period) === 1) {
            $year = (int) substr($period, 0, 4);
            $month = (int) substr($period, 4, 2);
            $periodDate = sprintf('%04d-%02d-01', $year, $month);
        } else {
            $periodDate = now()->format('Y-m-01');
        }

        $paramsResolver = app(ContributionParametersResolver::class);
        $smlmvAmount = $paramsResolver->getSmlmv($periodDate);
        $ibcMinAmount = $paramsResolver->getIbcMin($periodDate);
        $ibcMaxAmount = $paramsResolver->getIbcMax($periodDate);

        $ibcMinMultiplier = ($smlmvAmount && $ibcMinAmount !== null && $smlmvAmount > 0)
            ? (int) round($ibcMinAmount / $smlmvAmount)
            : null;
        $ibcMaxMultiplier = ($smlmvAmount && $ibcMaxAmount !== null && $smlmvAmount > 0)
            ? (int) round($ibcMaxAmount / $smlmvAmount)
            : null;

        return Inertia::render('PilaAffiliations/Edit', [
            'affiliation' => new PilaAffiliationResource($affiliation),
            'ibcMinAmount' => $ibcMinAmount,
            'ibcMaxAmount' => $ibcMaxAmount,
            'smlmvAmount' => $smlmvAmount,
            'ibcMinMultiplier' => $ibcMinMultiplier,
            'ibcMaxMultiplier' => $ibcMaxMultiplier,
            ...$this->formData(),
        ]);
    }

    public function update(UpdatePilaAffiliationRequest $request, PilaAffiliation $affiliation): RedirectResponse
    {
        $affiliation->update($request->validated());

        return redirect()->route('affiliations.show', $affiliation)
            ->with('success', 'Afiliación PILA actualizada correctamente.');
    }

    public function destroy(PilaAffiliation $affiliation): RedirectResponse
    {
        $affiliation->delete();

        return redirect()->route('affiliations.index')->with('success', 'Afiliación eliminada correctamente.');
    }

    private function indexFilterOptions(): array
    {
        $operators = PilaAffiliation::query()
            ->whereNotNull('pila_operator')
            ->where('pila_operator', '!=', '')
            ->distinct()
            ->pluck('pila_operator')
            ->sort()
            ->values()
            ->map(fn ($op) => ['value' => $op, 'label' => $op])
            ->all();

        $employers = PilaEmployer::query()
            ->orderBy('name')
            ->get(['id', 'name', 'payment_business_day'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'label' => $e->name,
                'payment_business_day' => $e->payment_business_day,
            ]);

        $businessDays = [];
        for ($d = 2; $d <= 16; $d++) {
            $businessDays[] = ['value' => $d, 'label' => (string) $d];
        }

        $epsOptions = Eps::active()->orderBy('name')->get(['id', 'name'])->map(fn ($e) => ['id' => $e->id, 'label' => $e->name]);
        $afpOptions = Afp::active()->orderBy('name')->get(['id', 'name'])->map(fn ($e) => ['id' => $e->id, 'label' => $e->name]);
        $arpOptions = Arp::active()->orderBy('name')->get(['id', 'name'])->map(fn ($e) => ['id' => $e->id, 'label' => $e->name]);
        $ccfOptions = Ccf::active()->orderBy('name')->get(['id', 'name'])->map(fn ($e) => ['id' => $e->id, 'label' => $e->name]);

        return [
            'pila_operators' => $operators,
            'employers' => $employers,
            'payment_business_days' => $businessDays,
            'payment_statuses' => [
                ['value' => 'current', 'label' => __('pila.payment_status.current')],
                ['value' => 'overdue', 'label' => __('pila.payment_status.overdue')],
                ['value' => 'anticipated', 'label' => __('pila.payment_status.anticipated')],
            ],
            'epsOptions' => $epsOptions,
            'afpOptions' => $afpOptions,
            'arpOptions' => $arpOptions,
            'ccfOptions' => $ccfOptions,
        ];
    }

    private function formData(): array
    {
        $affiliates = Affiliate::query()
            ->orderBy('first_name')
            ->limit(500)
            ->get(['id', 'first_name', 'second_name', 'last_name', 'second_last_name', 'document_number'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'label' => $a->full_name . ' — ' . $a->document_number,
            ]);

        $employers = PilaEmployer::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'document_type', 'document_number', 'check_digit'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'label' => $e->name,
                'description' => trim($e->document_type . ' ' . $e->document_number . ($e->check_digit ? '-' . $e->check_digit : '')),
            ]);

        $cotizanteTypes = PilaCotizanteType::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn ($t) => [
                'id' => $t->id,
                'label' => "{$t->code} — {$t->name}",
            ]);

        // Clases de riesgo ARL: catálogo pila_risk_classes (tarifa % viene de BD, no hardcodeada).
        $riskClasses = PilaRiskClass::query()
            ->where('is_active', true)
            ->orderBy('level')
            ->get(['id', 'level', 'class_name', 'description', 'rate'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'label' => $r->level === 0
                    ? '0 — No aplica'
                    : sprintf(
                        '%d (%s) — %s (%s%%)',
                        (int) $r->level,
                        (string) ($r->class_name ?? ''),
                        (string) $r->description,
                        number_format((float) ($r->rate * 100), 3, '.', '')
                    ),
                'description' => '',
            ]);

        $epsOptions = Eps::active()->orderBy('name')->get(['id', 'name', 'code'])->map(fn ($e) => [
            'id' => $e->id,
            'label' => $e->name,
            'description' => $e->code,
        ]);
        $afpOptions = Afp::active()->orderBy('name')->get(['id', 'name', 'code'])->map(fn ($e) => [
            'id' => $e->id,
            'label' => $e->name,
            'description' => $e->code,
        ]);
        $arpOptions = Arp::active()->orderBy('name')->get(['id', 'name', 'code'])->map(fn ($e) => [
            'id' => $e->id,
            'label' => $e->name,
            'description' => $e->code,
        ]);
        $ccfOptions = Ccf::active()->orderBy('name')->get(['id', 'name', 'code'])->map(fn ($e) => [
            'id' => $e->id,
            'label' => $e->name,
            'description' => $e->code,
        ]);

        return [
            'affiliateOptions' => $affiliates,
            'employerOptions' => $employers,
            'cotizanteTypeOptions' => $cotizanteTypes,
            'riskClassOptions' => $riskClasses,
            'epsOptions' => $epsOptions,
            'afpOptions' => $afpOptions,
            'arpOptions' => $arpOptions,
            'ccfOptions' => $ccfOptions,
            // Operadores de pago: mismo catálogo que Configuración → Operadores de pago.
            'pilaOperatorOptions' => PaymentOperator::active()
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn ($op) => [
                    'value' => $op->code ?: Str::lower(Str::slug($op->name, '')),
                    'label' => $op->name,
                ])
                ->values(),
            // Tipos de novedad: mismo catálogo que Configuración → Tipos de novedad.
            'noveltyOptions' => NoveltyType::active()
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn ($n) => [
                    'value' => $n->code ?: $n->name,
                    'title' => $n->name,
                ])
                ->values(),
            'paymentStatusOptions' => collect((array) config('pila.affiliation.payment_statuses', []))
                ->map(fn ($v) => ['value' => $v, 'label' => __("pila.payment_status.$v")])
                ->values(),
            'paymentPeriodicityOptions' => collect((array) config('pila.affiliation.payment_periodicities', []))
                ->map(fn ($v) => ['value' => $v, 'label' => __("pila.payment_periodicity.$v")])
                ->values(),
            'billingTypeOptions' => collect((array) config('pila.affiliation.billing_types', []))
                ->map(fn ($v) => ['value' => $v, 'label' => __("pila.billing_type.$v")])
                ->values(),
        ];
    }
}

