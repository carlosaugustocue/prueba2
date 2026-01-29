<?php

namespace App\Modules\Appointments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Core\Traits\Searchable;
use App\Modules\Appointments\Enums\AppointmentStatus;
use App\Modules\Appointments\Enums\AppointmentType;
use App\Modules\Appointments\Enums\Priority;
use App\Modules\Appointments\Events\AppointmentCreated;
use App\Modules\Appointments\Events\AppointmentStatusChanged;

class Appointment extends Model
{
    use SoftDeletes, HasUuid, Searchable;

    protected $fillable = [
        'uuid', 'patient_id', 'created_by', 'assigned_to',
        'appointment_request_id',
        'type', 'status', 'priority', 'specialty',
        'appointment_date', 'appointment_time', 'doctor_name',
        'location_name', 'location_address', 'authorization_number',
        'specifications', 'internal_notes',
        'confirmation_sent_at', 'reminder_sent_at',
    ];

    protected array $searchable = [
        'doctor_name', 'location_name', 'authorization_number',
        'patient.first_name', 'patient.last_name', 'patient.document_number',
    ];

    protected function casts(): array
    {
        return [
            'type' => AppointmentType::class,
            'status' => AppointmentStatus::class,
            'priority' => Priority::class,
            'appointment_date' => 'date',
            // appointment_time se maneja como string para evitar problemas de timezone
            'confirmation_sent_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    protected $dispatchesEvents = [
        'created' => AppointmentCreated::class,
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Patients\Models\Patient::class);
    }

    public function appointmentRequest(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\AppointmentRequests\Models\AppointmentRequest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'assigned_to');
    }

    public function history(): HasMany
    {
        return $this->hasMany(AppointmentHistory::class)->orderByDesc('created_at');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function changeStatus(AppointmentStatus $newStatus): bool
    {
        if (! $this->status->canTransitionTo($newStatus)) {
            return false;
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;

        $saved = $this->save();

        if ($saved) {
            event(new AppointmentStatusChanged($this, $oldStatus, $newStatus));
        }

        return $saved;
    }

    public function canSendConfirmation(): bool
    {
        return $this->status === AppointmentStatus::CONFIRMED;
    }

    public function getFormattedDateTimeAttribute(): ?string
    {
        if (! $this->appointment_date) return null;
        $date = $this->appointment_date->format('d/m/Y');
        $time = $this->appointment_time ? substr($this->appointment_time, 0, 5) : '';
        return trim("{$date} {$time}");
    }

    public function scopeToday($query) { return $query->whereDate('appointment_date', today()); }
    public function scopeConfirmed($query) { return $query->where('status', AppointmentStatus::CONFIRMED); }
    public function scopeActive($query) { return $query->whereIn('status', AppointmentStatus::activeStatuses()); }
    public function scopeStatus($query, string|AppointmentStatus $status) {
        $status = is_string($status) ? AppointmentStatus::from($status) : $status;
        return $query->where('status', $status);
    }
}
