<?php

namespace App\Modules\Appointments\Services;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Appointments\Models\AppointmentHistory;
use App\Modules\Appointments\Enums\AppointmentStatus;
use App\Modules\Appointments\Jobs\SendConfirmationJob;
use App\Modules\Appointments\Models\Reminder;
use App\Modules\Appointments\Jobs\SendReminderJob;
use App\Modules\AppointmentRequests\Models\AppointmentRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Appointment::query()->with(['patient.eps', 'creator', 'assignee']);

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }
        if (! empty($filters['status'])) {
            $query->status($filters['status']);
        }
        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }
        if (! empty($filters['today'])) {
            $query->today();
        }
        if (! empty($filters['active'])) {
            $query->active();
        }

        // Mostrar primero las citas más recientes (por ID)
        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function getTodayAppointments(): Collection
    {
        return Appointment::query()
            ->with(['patient.eps'])
            ->today()
            ->confirmed()
            ->orderBy('appointment_time')
            ->get();
    }

    public function getDashboardStats(): array
    {
        return [
            'today' => Appointment::today()->confirmed()->count(),
            'pending_requests' => \App\Modules\AppointmentRequests\Models\AppointmentRequest::where('status', 'pending')->count(),
            'urgent_requests' => \App\Modules\AppointmentRequests\Models\AppointmentRequest::whereIn('status', ['pending', 'in_progress'])->where('priority', 'urgent')->count(),
            'confirmed' => Appointment::confirmed()->count(),
        ];
    }

    public function create(array $data): Appointment
    {
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? AppointmentStatus::CONFIRMED->value;

        $appointment = Appointment::create($data);

        // Si viene de una solicitud, marcarla como completada
        if (!empty($data['appointment_request_id'])) {
            $this->completeAppointmentRequest($data['appointment_request_id'], $appointment->id);
        }

        if (config('services.appointments.confirmation_auto_send') && ! empty($data['send_confirmation']) && $appointment->canSendConfirmation()) {
            $this->queueWhatsAppConfirmation($appointment);
        }

        // Programar recordatorio (mañana anterior) si aplica
        $this->scheduleWhatsAppReminderMorning($appointment);

        return $appointment;
    }

    /**
     * Marcar una solicitud como completada cuando se crea la cita
     */
    protected function completeAppointmentRequest(int $requestId, int $appointmentId): void
    {
        $request = AppointmentRequest::find($requestId);
        if ($request) {
            $request->markAsCompleted($appointmentId);
        }
    }

    public function update(Appointment $appointment, array $data): bool
    {
        $oldData = $appointment->toArray();
        $updated = $appointment->update($data);

        if ($updated) {
            foreach ($data as $field => $newValue) {
                $oldValue = $oldData[$field] ?? null;
                if ($oldValue !== $newValue && ! in_array($field, ['updated_at'])) {
                    AppointmentHistory::log($appointment, AppointmentHistory::ACTION_UPDATED, $field, $oldValue, $newValue);
                }
            }
        }

        if ($updated) {
            // Reprogramar recordatorio si cambió fecha u hora
            if (array_key_exists('appointment_date', $data) || array_key_exists('appointment_time', $data)) {
                $this->scheduleWhatsAppReminderMorning($appointment, reschedule: true);
            }
        }

        return $updated;
    }

    public function changeStatus(Appointment $appointment, AppointmentStatus $newStatus): bool
    {
        $changed = $appointment->changeStatus($newStatus);

        if ($changed) {
            if ($newStatus === AppointmentStatus::CANCELLED) {
                $this->cancelPendingReminders($appointment);
            }
            if ($newStatus === AppointmentStatus::CONFIRMED) {
                $this->scheduleWhatsAppReminderMorning($appointment);
            }
        }

        return $changed;
    }

    public function sendConfirmation(Appointment $appointment): bool
    {
        if (! $appointment->canSendConfirmation()) {
            return false;
        }
        $this->queueWhatsAppConfirmation($appointment, force: true);
        return true;
    }

    /**
     * Encolar confirmación por WhatsApp (Cloud API template) y registrar evidencia en reminders.
     */
    protected function queueWhatsAppConfirmation(Appointment $appointment, bool $force = false): void
    {
        $appointment->loadMissing('patient');

        if (! $force && $appointment->confirmation_sent_at) {
            return;
        }

        $recipient = $appointment->patient?->getWhatsAppNumber();
        if (! $recipient) {
            return;
        }

        // Si ya existe una confirmación enviada, no duplicar
        $alreadySent = Reminder::query()
            ->where('appointment_id', $appointment->id)
            ->where('type', Reminder::TYPE_CONFIRMATION)
            ->where('channel', Reminder::CHANNEL_WHATSAPP)
            ->where('status', Reminder::STATUS_SENT)
            ->exists();

        if ($alreadySent && ! $force) {
            return;
        }

        $reminder = Reminder::create([
            'appointment_id' => $appointment->id,
            'type' => Reminder::TYPE_CONFIRMATION,
            'channel' => Reminder::CHANNEL_WHATSAPP,
            'recipient' => $recipient,
            'message' => null,
            'scheduled_at' => now(),
            'status' => Reminder::STATUS_PENDING,
        ]);

        SendConfirmationJob::dispatch($reminder->id);
    }

    /**
     * Programar recordatorio por WhatsApp para la mañana del día anterior.
     */
    protected function scheduleWhatsAppReminderMorning(Appointment $appointment, bool $reschedule = false): void
    {
        $appointment->refresh();
        $appointment->loadMissing('patient');

        if (! $appointment->appointment_date || ! $appointment->appointment_time) {
            return;
        }

        $recipient = $appointment->patient?->getWhatsAppNumber();
        if (! $recipient) {
            // Sin WhatsApp: no se programa recordatorio automático
            return;
        }

        if ($appointment->status === AppointmentStatus::CANCELLED) {
            $this->cancelPendingReminders($appointment);
            return;
        }

        // Si la cita es hoy, no se programa recordatorio.
        if ($appointment->appointment_date->isSameDay(today())) {
            $this->cancelPendingReminders($appointment);
            return;
        }

        $tz = config('services.appointments.reminder_timezone', config('app.timezone', 'UTC'));
        $sendTime = config('services.appointments.reminder_send_time', '08:00');

        $date = $appointment->appointment_date->format('Y-m-d');
        $scheduledLocal = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$sendTime}", $tz)
            ->subDay();

        // Si ya pasó la hora programada pero la cita aún es futura, enviar lo antes posible
        if ($scheduledLocal->lessThanOrEqualTo(now($tz)) && $appointment->appointment_date->isAfter(today())) {
            $scheduledLocal = now($tz)->addMinute();
        }

        $scheduledAt = $scheduledLocal->clone()->setTimezone(config('app.timezone', 'UTC'));

        if ($reschedule) {
            $this->cancelPendingReminders($appointment);
        }

        // Evitar duplicados si ya existe un reminder pendiente en la misma hora
        $exists = Reminder::query()
            ->where('appointment_id', $appointment->id)
            ->where('type', Reminder::TYPE_REMINDER_24H)
            ->where('channel', Reminder::CHANNEL_WHATSAPP)
            ->whereIn('status', [Reminder::STATUS_PENDING, Reminder::STATUS_PROCESSING])
            ->where('scheduled_at', $scheduledAt)
            ->exists();

        if ($exists) {
            return;
        }

        Reminder::create([
            'appointment_id' => $appointment->id,
            'type' => Reminder::TYPE_REMINDER_24H,
            'channel' => Reminder::CHANNEL_WHATSAPP,
            'recipient' => $recipient,
            'message' => null,
            'scheduled_at' => $scheduledAt,
            'status' => Reminder::STATUS_PENDING,
        ]);
    }

    protected function cancelPendingReminders(Appointment $appointment): void
    {
        Reminder::query()
            ->where('appointment_id', $appointment->id)
            ->where('type', Reminder::TYPE_REMINDER_24H)
            ->whereIn('status', [Reminder::STATUS_PENDING, Reminder::STATUS_PROCESSING])
            ->update([
                'status' => Reminder::STATUS_CANCELLED,
                'error_message' => 'Recordatorio cancelado por actualización de la cita',
            ]);
    }

    public function getWithDetails(Appointment $appointment): Appointment
    {
        return $appointment->load(['patient.eps', 'patient.holder', 'creator', 'assignee', 'history.user', 'reminders', 'communications.user']);
    }
}
