<?php

namespace App\Modules\AdminWhatsApp\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Appointments\Models\Reminder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppSendsController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Reminder::query()
            ->with(['appointment.affiliate.socialSecurityProfile.eps'])
            ->where('channel', Reminder::CHANNEL_WHATSAPP)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('from') && $request->filled('to')) {
            $from = \Carbon\Carbon::parse($request->input('from'))->startOfDay();
            $to = \Carbon\Carbon::parse($request->input('to'))->endOfDay();
            $query->whereBetween('created_at', [$from, $to]);
        }

        $items = $query->paginate($request->integer('per_page', 20))->withQueryString()->through(function (Reminder $r) {
            $apt = $r->appointment;
            $affiliate = $apt?->affiliate;
            $eps = $affiliate?->socialSecurityProfile?->eps;
            return [
                'id' => $r->id,
                'type' => $r->type,
                'type_label' => $r->type === Reminder::TYPE_CONFIRMATION ? 'Confirmación' : 'Recordatorio 24h',
                'status' => $r->status,
                'status_label' => $this->statusLabel($r->status),
                'is_pending' => in_array($r->status, [Reminder::STATUS_PENDING, Reminder::STATUS_PROCESSING], true),
                'is_cancellable' => $r->isCancellable(),
                'recipient' => $r->recipient,
                'scheduled_at' => $r->scheduled_at?->format('Y-m-d H:i'),
                'scheduled_at_formatted' => $r->scheduled_at?->format('d/m/Y H:i'),
                'sent_at' => $r->sent_at?->format('Y-m-d H:i'),
                'sent_at_formatted' => $r->sent_at?->format('d/m/Y H:i'),
                'created_at' => $r->created_at?->format('Y-m-d H:i:s'),
                'created_at_formatted' => $r->created_at?->format('d/m/Y H:i'),
                'error_message' => $r->error_message,
                'appointment_id' => $apt?->id,
                'affiliate' => $affiliate ? [
                    'id' => $affiliate->id,
                    'full_name' => $affiliate->full_name,
                    'eps' => $eps ? ['id' => $eps->id, 'name' => $eps->name] : null,
                ] : null,
            ];
        });

        $pendingCount = Reminder::query()
            ->where('channel', Reminder::CHANNEL_WHATSAPP)
            ->whereIn('status', [Reminder::STATUS_PENDING, Reminder::STATUS_PROCESSING])
            ->count();

        return Inertia::render('Admin/WhatsAppSends/Index', [
            'filters' => [
                'status' => $request->string('status')->toString() ?: null,
                'type' => $request->string('type')->toString() ?: null,
                'from' => $request->string('from')->toString() ?: null,
                'to' => $request->string('to')->toString() ?: null,
            ],
            'items' => $items,
            'pending_count' => $pendingCount,
            'statuses' => [
                ['value' => 'pending', 'label' => 'Pendiente'],
                ['value' => 'processing', 'label' => 'Procesando'],
                ['value' => 'sent', 'label' => 'Enviado'],
                ['value' => 'failed', 'label' => 'Fallido'],
                ['value' => 'cancelled', 'label' => 'Cancelado'],
            ],
            'types' => [
                ['value' => Reminder::TYPE_CONFIRMATION, 'label' => 'Confirmación'],
                ['value' => Reminder::TYPE_REMINDER_24H, 'label' => 'Recordatorio 24h'],
            ],
        ]);
    }

    public function cancel(Reminder $reminder): \Illuminate\Http\RedirectResponse
    {
        if ($reminder->channel !== Reminder::CHANNEL_WHATSAPP) {
            return redirect()->route('admin.whatsapp-envios.index')->with('error', 'Solo se pueden cancelar envíos por WhatsApp.');
        }

        if (! $reminder->isCancellable()) {
            return redirect()->route('admin.whatsapp-envios.index')->with('error', 'Este envío ya no se puede cancelar.');
        }

        $reminder->markAsCancelled();

        return redirect()->route('admin.whatsapp-envios.index')->with('success', 'Envío cancelado correctamente.');
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            Reminder::STATUS_PENDING => 'Pendiente',
            Reminder::STATUS_PROCESSING => 'Procesando',
            Reminder::STATUS_SENT => 'Enviado',
            Reminder::STATUS_FAILED => 'Fallido',
            Reminder::STATUS_CANCELLED => 'Cancelado',
            default => $status,
        };
    }
}
