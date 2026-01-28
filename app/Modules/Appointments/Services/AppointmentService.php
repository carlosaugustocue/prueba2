<?php

namespace App\Modules\Appointments\Services;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Appointments\Models\AppointmentHistory;
use App\Modules\Appointments\Enums\AppointmentStatus;
use App\Modules\Appointments\Jobs\SendConfirmationJob;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
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

        return $query->orderBy('appointment_date', 'asc')->paginate($perPage);
    }

    public function getTodayAppointments(): Collection
    {
        return Appointment::query()
            ->with(['patient.eps'])
            ->today()
            ->active()
            ->orderBy('appointment_time')
            ->get();
    }

    public function getDashboardStats(): array
    {
        return [
            'today' => Appointment::today()->active()->count(),
            'pending' => Appointment::pending()->count(),
            'urgent' => Appointment::active()->where('priority', 'urgent')->count(),
            'to_confirm' => Appointment::active()->whereNull('confirmation_sent_at')->whereNotNull('appointment_date')->count(),
        ];
    }

    public function create(array $data): Appointment
    {
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? AppointmentStatus::PENDING->value;

        $appointment = Appointment::create($data);

        if (config('services.appointments.confirmation_auto_send') && ! empty($data['send_confirmation']) && $appointment->canSendConfirmation()) {
            SendConfirmationJob::dispatch($appointment);
        }

        return $appointment;
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

        return $updated;
    }

    public function changeStatus(Appointment $appointment, AppointmentStatus $newStatus): bool
    {
        return $appointment->changeStatus($newStatus);
    }

    public function sendConfirmation(Appointment $appointment): bool
    {
        if (! $appointment->canSendConfirmation()) {
            return false;
        }
        SendConfirmationJob::dispatch($appointment);
        return true;
    }

    public function getWithDetails(Appointment $appointment): Appointment
    {
        return $appointment->load(['patient.eps', 'patient.holder', 'creator', 'assignee', 'history.user', 'reminders']);
    }
}
