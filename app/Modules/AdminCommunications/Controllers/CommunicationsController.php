<?php

namespace App\Modules\AdminCommunications\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Appointments\Models\Reminder;
use App\Modules\Auth\Models\User;
use App\Modules\Patients\Models\Eps;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommunicationsController extends Controller
{
    public function index(Request $request): Response|StreamedResponse
    {
        [$from, $to] = $this->dateRange($request);

        $filters = [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'channel' => $request->string('channel')->toString() ?: null, // whatsapp|phone
            'status' => $request->string('status')->toString() ?: null,   // pending|processing|sent|failed|cancelled|logged
            'operator_id' => $request->integer('operator_id') ?: null,
            'eps_id' => $request->integer('eps_id') ?: null,
        ];

        // Reminders (WhatsApp/email) como comunicaciones automáticas
        $remindersQuery = DB::table('reminders as r')
            ->join('appointments as a', 'a.id', '=', 'r.appointment_id')
            ->join('patients as p', 'p.id', '=', 'a.patient_id')
            ->leftJoin('eps as e', 'e.id', '=', 'p.eps_id')
            ->leftJoin('users as u', 'u.id', '=', 'a.created_by')
            ->selectRaw("
                r.id as id,
                'whatsapp' as channel,
                r.type as type,
                r.status as status,
                NULL as category,
                r.recipient as recipient,
                r.message as message,
                r.error_message as error_message,
                r.scheduled_at as scheduled_at,
                r.sent_at as sent_at,
                r.created_at as created_at,
                a.id as appointment_id,
                CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                COALESCE(u.name, 'Sistema') as operator_name,
                e.name as eps_name
            ")
            ->whereBetween('r.created_at', [$from, $to])
            ->where('r.channel', '=', Reminder::CHANNEL_WHATSAPP);

        // Phone communications manuales
        $phonesQuery = DB::table('appointment_communications as ac')
            ->join('appointments as a', 'a.id', '=', 'ac.appointment_id')
            ->join('patients as p', 'p.id', '=', 'a.patient_id')
            ->leftJoin('eps as e', 'e.id', '=', 'p.eps_id')
            ->leftJoin('users as u', 'u.id', '=', 'ac.user_id')
            ->selectRaw("
                ac.id as id,
                'phone' as channel,
                'phone' as type,
                'logged' as status,
                ac.category as category,
                NULL as recipient,
                NULL as message,
                NULL as error_message,
                NULL as scheduled_at,
                NULL as sent_at,
                ac.created_at as created_at,
                a.id as appointment_id,
                CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                COALESCE(u.name, 'Sistema') as operator_name,
                e.name as eps_name
            ")
            ->whereBetween('ac.created_at', [$from, $to])
            ->where('ac.channel', '=', 'phone');

        // Aplicar filtros comunes
        if ($filters['eps_id']) {
            $remindersQuery->where('p.eps_id', $filters['eps_id']);
            $phonesQuery->where('p.eps_id', $filters['eps_id']);
        }

        if ($filters['operator_id']) {
            $remindersQuery->where('a.created_by', $filters['operator_id']);
            $phonesQuery->where('ac.user_id', $filters['operator_id']);
        }

        if ($filters['channel'] === 'whatsapp') {
            $phonesQuery->whereRaw('1=0');
        } elseif ($filters['channel'] === 'phone') {
            $remindersQuery->whereRaw('1=0');
        }

        if ($filters['status']) {
            if ($filters['status'] === 'logged') {
                $remindersQuery->whereRaw('1=0');
            } else {
                $remindersQuery->where('r.status', $filters['status']);
            }
        }

        $union = $remindersQuery->unionAll($phonesQuery);
        $rows = DB::query()->fromSub($union, 'c')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 30))
            ->withQueryString();

        if ($request->string('format')->toString() === 'csv') {
            $all = DB::query()->fromSub($union, 'c')->orderByDesc('created_at')->get();

            return $this->csvResponse(
                filename: "comunicaciones_{$filters['from']}_{$filters['to']}.csv",
                headers: ['created_at', 'channel', 'type', 'status', 'category', 'appointment_id', 'patient', 'eps', 'operator', 'recipient', 'message', 'error'],
                rows: $all->map(fn ($r) => [
                    $r->created_at,
                    $r->channel,
                    $r->type,
                    $r->status,
                    $r->category,
                    $r->appointment_id,
                    $r->patient_name,
                    $r->eps_name,
                    $r->operator_name,
                    $r->recipient,
                    $r->message ? preg_replace("/\\s+/", ' ', (string) $r->message) : null,
                    $r->error_message ? preg_replace("/\\s+/", ' ', (string) $r->error_message) : null,
                ])->toArray(),
            );
        }

        return Inertia::render('Admin/Communications/Index', [
            'filters' => $filters,
            'items' => $rows,
            'operators' => User::whereHas('role', fn ($q) => $q->whereIn('name', ['operator', 'admin']))
                ->orderBy('name')
                ->get(['id', 'name']),
            'epsList' => Eps::active()->orderBy('name')->get(['id', 'name']),
            'statuses' => [
                ['value' => 'pending', 'label' => 'Pendiente'],
                ['value' => 'processing', 'label' => 'Procesando'],
                ['value' => 'sent', 'label' => 'Enviado'],
                ['value' => 'failed', 'label' => 'Fallido'],
                ['value' => 'cancelled', 'label' => 'Cancelado'],
                ['value' => 'logged', 'label' => 'Llamada registrada'],
            ],
            'channels' => [
                ['value' => 'whatsapp', 'label' => 'WhatsApp'],
                ['value' => 'phone', 'label' => 'Teléfono'],
            ],
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

