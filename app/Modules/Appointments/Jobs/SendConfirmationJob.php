<?php

namespace App\Modules\Appointments\Jobs;

use App\Modules\Appointments\Models\Reminder;
use App\Modules\Appointments\Models\AppointmentHistory;
use App\Modules\Core\Contracts\NotificationChannelInterface;
use App\Modules\Integrations\WhatsApp\AppointmentMessageFooter;
use App\Modules\Integrations\WhatsApp\Templates\ConfirmationTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $reminderId) {}

    public function handle(NotificationChannelInterface $notificationChannel): void
    {
        $reminder = Reminder::query()
            ->with(['appointment.patient'])
            ->find($this->reminderId);

        if (! $reminder || ! $reminder->appointment) {
            return;
        }

        // Evitar duplicados
        if ($reminder->status === Reminder::STATUS_SENT) {
            return;
        }

        $appointment = $reminder->appointment;
        $patient = $appointment->patient;

        if (! $appointment->canSendConfirmation()) {
            return;
        }

        $recipient = $patient->getWhatsAppNumber();

        if (! $recipient) {
            Log::warning('Patient has no WhatsApp number', ['appointment_id' => $appointment->id, 'reminder_id' => $reminder->id]);
            return;
        }

        try {
            $template = new ConfirmationTemplate();
            $message = $template->build($appointment);
            $templateName = config('services.whatsapp.templates.confirmation', 'serviconli_cita_confirmada');
            $language = config('services.whatsapp.language', 'es_CO');
            $parameters = $template->templateParameters($appointment);

            // Asegurar que el record guarde el contenido que se intentó enviar
            $reminder->update([
                'recipient' => $recipient,
                'message' => $message,
            ]);

            $response = $notificationChannel->send($recipient, $message, [
                'type' => 'template',
                'template_name' => $templateName,
                'language' => $language,
                'parameters' => $parameters,
            ]);

            // Mensaje diplomático sobre gestionar la cita si no podrá asistir (segundo mensaje)
            try {
                $notificationChannel->send($recipient, AppointmentMessageFooter::noShowNotice(), ['type' => 'text']);
            } catch (\Exception $e) {
                Log::warning('WhatsApp footer (no-show notice) not sent after confirmation', [
                    'reminder_id' => $reminder->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $reminder->markAsSent($response);

            $appointment->update([
                'confirmation_sent_at' => now(),
            ]);

            AppointmentHistory::log($appointment, AppointmentHistory::ACTION_CONFIRMATION_SENT, description: 'Confirmación enviada por WhatsApp');

        } catch (\Exception $e) {
            // Marcar como fallido (permitirá reintento manual / por cola)
            $reminder->markAsFailed($e->getMessage());
            Log::error('Failed to send confirmation', ['appointment_id' => $appointment->id, 'reminder_id' => $reminder->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
