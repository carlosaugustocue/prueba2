<?php

namespace App\Modules\Appointments\Jobs;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Appointments\Models\Reminder;
use App\Modules\Appointments\Models\AppointmentHistory;
use App\Modules\Core\Contracts\NotificationChannelInterface;
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

    public function __construct(public Appointment $appointment) {}

    public function handle(NotificationChannelInterface $notificationChannel): void
    {
        $appointment = $this->appointment->fresh();

        if (! $appointment->canSendConfirmation()) {
            return;
        }

        $patient = $appointment->patient;
        $recipient = $patient->getWhatsAppNumber();

        if (! $recipient) {
            Log::warning('Patient has no WhatsApp number', ['appointment_id' => $appointment->id]);
            return;
        }

        try {
            $template = new ConfirmationTemplate();
            $message = $template->build($appointment);

            $reminder = Reminder::create([
                'appointment_id' => $appointment->id,
                'type' => Reminder::TYPE_CONFIRMATION,
                'channel' => Reminder::CHANNEL_WHATSAPP,
                'recipient' => $recipient,
                'message' => $message,
                'scheduled_at' => now(),
                'status' => Reminder::STATUS_PENDING,
            ]);

            $response = $notificationChannel->send($recipient, $message);
            $reminder->markAsSent($response);

            $appointment->update([
                'confirmation_sent_at' => now(),
            ]);

            AppointmentHistory::log($appointment, AppointmentHistory::ACTION_CONFIRMATION_SENT, description: 'Confirmación enviada por WhatsApp');

        } catch (\Exception $e) {
            Log::error('Failed to send confirmation', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
