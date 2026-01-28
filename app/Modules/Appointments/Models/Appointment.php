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
        'requested_at', 'processed_at',
        'confirmation_sent_at', 'reminder_sent_at', 'completed_at',
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
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'completed_at' => 'datetime',
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

        // Registrar fecha de procesamiento cuando se confirma o se pone en progreso
        if (in_array($newStatus, [AppointmentStatus::CONFIRMED, AppointmentStatus::IN_PROGRESS]) && !$this->processed_at) {
            $this->processed_at = now();
        }

        if ($newStatus === AppointmentStatus::COMPLETED) {
            $this->completed_at = now();
        }

        $saved = $this->save();

        if ($saved) {
            event(new AppointmentStatusChanged($this, $oldStatus, $newStatus));
        }

        return $saved;
    }

    /**
     * Calcular tiempo de trámite en horas
     */
    public function getProcessingTimeHoursAttribute(): ?float
    {
        if (!$this->requested_at || !$this->processed_at) {
            return null;
        }
        return round($this->requested_at->diffInMinutes($this->processed_at) / 60, 1);
    }

    /**
     * Calcular tiempo de trámite formateado
     */
    public function getProcessingTimeFormattedAttribute(): ?string
    {
        if (!$this->requested_at || !$this->processed_at) {
            return null;
        }
        
        $minutes = $this->requested_at->diffInMinutes($this->processed_at);
        
        if ($minutes < 60) {
            return "{$minutes} min";
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours < 24) {
            return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}min" : "{$hours}h";
        }
        
        $days = floor($hours / 24);
        $remainingHours = $hours % 24;
        
        return $remainingHours > 0 ? "{$days}d {$remainingHours}h" : "{$days}d";
    }

    public function canSendConfirmation(): bool
    {
        return in_array($this->status, [AppointmentStatus::CONFIRMED, AppointmentStatus::IN_PROGRESS]);
    }

    public function getFormattedDateTimeAttribute(): ?string
    {
        if (! $this->appointment_date) return null;
        $date = $this->appointment_date->format('d/m/Y');
        $time = $this->appointment_time ? substr($this->appointment_time, 0, 5) : '';
        return trim("{$date} {$time}");
    }

    public function scopeToday($query) { return $query->whereDate('appointment_date', today()); }
    public function scopePending($query) { return $query->where('status', AppointmentStatus::PENDING); }
    public function scopeActive($query) { return $query->whereIn('status', AppointmentStatus::activeStatuses()); }
    public function scopeStatus($query, string|AppointmentStatus $status) {
        $status = is_string($status) ? AppointmentStatus::from($status) : $status;
        return $query->where('status', $status);
    }
}
