<?php

namespace App\Modules\Appointments\Jobs;

use App\Modules\Appointments\Models\Reminder;
use App\Modules\Appointments\Models\AppointmentHistory;
use App\Modules\Core\Contracts\NotificationChannelInterface;
use App\Modules\Integrations\WhatsApp\Templates\ReminderTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendReminderJob implements ShouldQueue
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
        if ($reminder->status === Reminder::STATUS_SENT || $reminder->status === Reminder::STATUS_CANCELLED) {
            return;
        }

        $appointment = $reminder->appointment;
        $patient = $appointment->patient;

        $recipient = $patient?->getWhatsAppNumber();
        if (! $recipient) {
            Log::warning('Patient has no WhatsApp number for reminder', ['appointment_id' => $appointment->id, 'reminder_id' => $reminder->id]);
            return;
        }

        try {
            $template = new ReminderTemplate();
            $message = $template->build($appointment);
            $templateName = config('services.whatsapp.templates.reminder_morning', 'serviconli_recordatorio_cita_manana');
            $language = config('services.whatsapp.language', 'es_CO');
            $parameters = $template->templateParameters($appointment);

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

            $reminder->markAsSent($response);

            $appointment->update([
                'reminder_sent_at' => now(),
            ]);

            AppointmentHistory::log($appointment, AppointmentHistory::ACTION_REMINDER_SENT, description: 'Recordatorio enviado por WhatsApp');
        } catch (\Exception $e) {
            $reminder->markAsFailed($e->getMessage());
            Log::error('Failed to send reminder', ['appointment_id' => $appointment->id, 'reminder_id' => $reminder->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}

