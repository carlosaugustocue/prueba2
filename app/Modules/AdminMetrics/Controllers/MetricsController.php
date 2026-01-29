<?php

namespace App\Modules\AdminMetrics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AppointmentRequests\Models\AppointmentRequest;
use App\Modules\AppointmentRequests\Enums\RequestStatus;
use App\Modules\Appointments\Enums\AppointmentType;
use App\Modules\Appointments\Enums\Priority;
use App\Modules\Auth\Models\User;
use App\Modules\Patients\Models\Eps;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MetricsController extends Controller
{
    public function operators(Request $request): Response|StreamedResponse
    {
        [$from, $to] = $this->dateRange($request);

        $filters = [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'operator_id' => $request->integer('operator_id') ?: null,
            'type' => $request->string('type')->toString() ?: null,
            'priority' => $request->string('priority')->toString() ?: null,
            'eps_id' => $request->integer('eps_id') ?: null,
        ];

        $base = DB::table('appointment_requests as ar')
            ->leftJoin('users as u', 'u.id', '=', 'ar.assigned_to')
            ->leftJoin('patients as p', 'p.id', '=', 'ar.patient_id')
            ->leftJoin('eps as e', 'e.id', '=', 'p.eps_id')
            ->whereNotNull('ar.assigned_to')
            ->whereNotNull('ar.completed_at')
            ->whereBetween('ar.completed_at', [$from, $to])
            ->whereIn('ar.status', [
                RequestStatus::COMPLETED->value,
                RequestStatus::FAILED->value,
                RequestStatus::CANCELLED->value,
            ]);

        if ($filters['operator_id']) {
            $base->where('ar.assigned_to', $filters['operator_id']);
        }
        if ($filters['type']) {
            $base->where('ar.type', $filters['type']);
        }
        if ($filters['priority']) {
            $base->where('ar.priority', $filters['priority']);
        }
        if ($filters['eps_id']) {
            $base->where('p.eps_id', $filters['eps_id']);
        }

        $rows = (clone $base)
            ->selectRaw('
                ar.assigned_to as operator_id,
                COALESCE(u.name, CONCAT("Usuario #", ar.assigned_to)) as operator_name,
                COUNT(*) as total_cerradas,
                SUM(ar.status = ?) as total_obtenidas,
                SUM(ar.status = ?) as total_no_obtenidas,
                SUM(ar.status = ?) as total_canceladas,
                AVG(ar.tiempo_total_gestion) as avg_total_min,
                AVG(TIMESTAMPDIFF(MINUTE, ar.requested_at, ar.started_at)) as avg_espera_min,
                AVG(TIMESTAMPDIFF(MINUTE, ar.started_at, ar.completed_at)) as avg_gestion_min
            ', [
                RequestStatus::COMPLETED->value,
                RequestStatus::FAILED->value,
                RequestStatus::CANCELLED->value,
            ])
            ->groupBy('ar.assigned_to', 'u.name')
            ->orderByDesc('total_obtenidas')
            ->get()
            ->map(fn ($r) => [
                'operator_id' => (int) $r->operator_id,
                'operator_name' => $r->operator_name,
                'total_cerradas' => (int) $r->total_cerradas,
                'total_obtenidas' => (int) $r->total_obtenidas,
                'total_no_obtenidas' => (int) $r->total_no_obtenidas,
                'total_canceladas' => (int) $r->total_canceladas,
                'avg_total_min' => $r->avg_total_min !== null ? round((float) $r->avg_total_min, 1) : null,
                'avg_espera_min' => $r->avg_espera_min !== null ? round((float) $r->avg_espera_min, 1) : null,
                'avg_gestion_min' => $r->avg_gestion_min !== null ? round((float) $r->avg_gestion_min, 1) : null,
            ])
            ->toArray();

        if ($request->string('format')->toString() === 'csv') {
            return $this->csvResponse(
                filename: "metricas_operadores_{$filters['from']}_{$filters['to']}.csv",
                headers: ['operator_id', 'operator_name', 'total_cerradas', 'total_obtenidas', 'total_no_obtenidas', 'total_canceladas', 'avg_total_min', 'avg_espera_min', 'avg_gestion_min'],
                rows: array_map(fn ($r) => [
                    $r['operator_id'],
                    $r['operator_name'],
                    $r['total_cerradas'],
                    $r['total_obtenidas'],
                    $r['total_no_obtenidas'],
                    $r['total_canceladas'],
                    $r['avg_total_min'],
                    $r['avg_espera_min'],
                    $r['avg_gestion_min'],
                ], $rows),
            );
        }

        return Inertia::render('Admin/Metrics/Operators', [
            'filters' => $filters,
            'rows' => $rows,
            'operators' => User::whereHas('role', fn ($q) => $q->whereIn('name', ['operator', 'admin']))
                ->orderBy('name')
                ->get(['id', 'name']),
            'types' => AppointmentType::toArray(),
            'priorities' => Priority::toArray(),
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function times(Request $request): Response
    {
        [$from, $to] = $this->dateRange($request);

        $summary = DB::table('appointment_requests')
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$from, $to])
            ->selectRaw('
                COUNT(*) as total_cerradas,
                AVG(tiempo_total_gestion) as avg_total_min,
                MIN(tiempo_total_gestion) as min_total_min,
                MAX(tiempo_total_gestion) as max_total_min
            ')
            ->first();

        return Inertia::render('Admin/Metrics/Times', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'summary' => [
                'total_cerradas' => (int) ($summary->total_cerradas ?? 0),
                'avg_total_min' => $summary?->avg_total_min !== null ? round((float) $summary->avg_total_min, 1) : null,
                'min_total_min' => $summary?->min_total_min !== null ? (int) $summary->min_total_min : null,
                'max_total_min' => $summary?->max_total_min !== null ? (int) $summary->max_total_min : null,
            ],
        ]);
    }

    public function annotations(Request $request): Response|StreamedResponse
    {
        [$from, $to] = $this->dateRange($request);

        $query = AppointmentRequest::query()
            ->with(['patient.eps', 'assignee'])
            ->whereNotNull('operator_notes')
            ->where('operator_notes', '!=', '')
            ->whereBetween('updated_at', [$from, $to])
            ->orderByDesc('updated_at');

        if ($request->filled('operator_id')) {
            $query->where('assigned_to', $request->integer('operator_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('eps_id')) {
            $query->whereHas('patient', fn ($q) => $q->where('eps_id', $request->integer('eps_id')));
        }

        $items = $query->paginate(30)->withQueryString();

        if ($request->string('format')->toString() === 'csv') {
            $rows = $items->getCollection()->map(fn ($r) => [
                $r->id,
                $r->assigned_to,
                $r->assignee?->name,
                $r->patient?->full_name,
                $r->patient?->eps?->name,
                $r->type?->value ?? $r->getRawOriginal('type'),
                $r->status?->value ?? $r->getRawOriginal('status'),
                $r->updated_at?->format('Y-m-d H:i:s'),
                preg_replace("/\\s+/", ' ', (string) $r->operator_notes),
            ])->toArray();

            return $this->csvResponse(
                filename: "anotaciones_{$from->toDateString()}_{$to->toDateString()}.csv",
                headers: ['request_id', 'operator_id', 'operator_name', 'patient', 'eps', 'type', 'status', 'updated_at', 'operator_notes'],
                rows: $rows,
            );
        }

        return Inertia::render('Admin/Metrics/Annotations', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'operator_id' => $request->integer('operator_id') ?: null,
                'type' => $request->string('type')->toString() ?: null,
                'eps_id' => $request->integer('eps_id') ?: null,
            ],
            'items' => $items,
            'operators' => User::whereHas('role', fn ($q) => $q->whereIn('name', ['operator', 'admin']))
                ->orderBy('name')
                ->get(['id', 'name']),
            'types' => AppointmentType::toArray(),
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function dateRange(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : now()->subDays(30)->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : now()->endOfDay();

        return [$from, $to];
    }

    private function csvResponse(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

