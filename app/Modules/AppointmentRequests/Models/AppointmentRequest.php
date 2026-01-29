<?php

namespace App\Modules\AppointmentRequests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Core\Traits\Searchable;
use App\Modules\AppointmentRequests\Enums\RequestStatus;
use App\Modules\Appointments\Enums\AppointmentType;
use App\Modules\Appointments\Enums\Priority;

class AppointmentRequest extends Model
{
    use SoftDeletes, HasUuid, Searchable;

    protected $fillable = [
        'uuid',
        'patient_id',
        'type',
        'priority',
        'specialty',
        'status',
        'requested_at',
        'started_at',
        'completed_at',
        'tiempo_total_gestion',
        'client_notes',
        'operator_notes',
        'appointment_id',
        'created_by',
        'assigned_to',
    ];

    protected array $searchable = [
        'specialty',
        'client_notes',
        'patient.first_name',
        'patient.last_name',
        'patient.document_number',
    ];

    protected function casts(): array
    {
        return [
            'type' => AppointmentType::class,
            'priority' => Priority::class,
            'status' => RequestStatus::class,
            'requested_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // ==================== RELACIONES ====================

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Patients\Models\Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Appointments\Models\Appointment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'assigned_to');
    }

    // ==================== MÉTODOS ====================

    /**
     * Iniciar el trámite de la solicitud
     */
    public function startProcessing(?int $operatorId = null): bool
    {
        if ($this->status !== RequestStatus::PENDING) {
            return false;
        }

        $this->status = RequestStatus::IN_PROGRESS;
        $this->started_at = now();
        
        if ($operatorId) {
            $this->assigned_to = $operatorId;
        }

        return $this->save();
    }

    /**
     * Marcar como completada (cita obtenida)
     */
    public function markAsCompleted(int $appointmentId): bool
    {
        $this->status = RequestStatus::COMPLETED;
        $this->completed_at = now();
        $this->appointment_id = $appointmentId;
        $this->tiempo_total_gestion = $this->requested_at ? $this->requested_at->diffInMinutes($this->completed_at) : null;

        return $this->save();
    }

    /**
     * Marcar como fallida (no se pudo obtener)
     */
    public function markAsFailed(?string $reason = null): bool
    {
        $this->status = RequestStatus::FAILED;
        $this->completed_at = now();
        $this->tiempo_total_gestion = $this->requested_at ? $this->requested_at->diffInMinutes($this->completed_at) : null;
        
        if ($reason) {
            $this->operator_notes = ($this->operator_notes ? $this->operator_notes . "\n" : '') . "Motivo: " . $reason;
        }

        return $this->save();
    }

    /**
     * Cancelar solicitud
     */
    public function cancel(?string $reason = null): bool
    {
        $this->status = RequestStatus::CANCELLED;
        $this->completed_at = now();
        $this->tiempo_total_gestion = $this->requested_at ? $this->requested_at->diffInMinutes($this->completed_at) : null;
        
        if ($reason) {
            $this->operator_notes = ($this->operator_notes ? $this->operator_notes . "\n" : '') . "Cancelación: " . $reason;
        }

        return $this->save();
    }

    // ==================== ATRIBUTOS CALCULADOS ====================

    /**
     * Tiempo de espera hasta que se comenzó a tramitar (en minutos)
     */
    public function getWaitingTimeMinutesAttribute(): ?int
    {
        if (!$this->requested_at || !$this->started_at) {
            return null;
        }
        return $this->requested_at->diffInMinutes($this->started_at);
    }

    /**
     * Tiempo total de trámite hasta completar (en minutos)
     */
    public function getProcessingTimeMinutesAttribute(): ?int
    {
        if (!$this->requested_at || !$this->completed_at) {
            return null;
        }
        return $this->requested_at->diffInMinutes($this->completed_at);
    }

    /**
     * Tiempo formateado de espera
     */
    public function getWaitingTimeFormattedAttribute(): ?string
    {
        $minutes = $this->waiting_time_minutes;
        if ($minutes === null) return null;
        
        return $this->formatMinutes($minutes);
    }

    /**
     * Tiempo formateado de trámite total
     */
    public function getProcessingTimeFormattedAttribute(): ?string
    {
        $minutes = $this->processing_time_minutes;
        if ($minutes === null) return null;
        
        return $this->formatMinutes($minutes);
    }

    /**
     * Tiempo transcurrido desde la solicitud (si aún está activa)
     */
    public function getElapsedTimeFormattedAttribute(): ?string
    {
        if (!$this->requested_at) return null;
        
        // Si ya está completada, usar completed_at
        if ($this->completed_at) {
            return $this->processing_time_formatted;
        }
        
        // Si está activa, calcular desde ahora
        $minutes = $this->requested_at->diffInMinutes(now());
        return $this->formatMinutes($minutes);
    }

    private function formatMinutes(int $minutes): string
    {
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

    // ==================== SCOPES ====================

    public function scopePending($query)
    {
        return $query->where('status', RequestStatus::PENDING);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', RequestStatus::IN_PROGRESS);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', RequestStatus::activeStatuses());
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', RequestStatus::COMPLETED);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }
}
