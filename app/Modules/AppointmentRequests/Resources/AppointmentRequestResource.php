<?php

namespace App\Modules\AppointmentRequests\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\AppointmentRequests\Enums\RequestStatus;
use App\Modules\Appointments\Enums\AppointmentType;
use App\Modules\Appointments\Enums\Priority;

class AppointmentRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $typeValue = $this->resource->getRawOriginal('type') ?? $this->type;
        $statusValue = $this->resource->getRawOriginal('status') ?? $this->status;
        $priorityValue = $this->resource->getRawOriginal('priority') ?? $this->priority;

        $typeEnum = is_string($typeValue) ? AppointmentType::tryFrom($typeValue) : $typeValue;
        $statusEnum = is_string($statusValue) ? RequestStatus::tryFrom($statusValue) : $statusValue;
        $priorityEnum = is_string($priorityValue) ? Priority::tryFrom($priorityValue) : $priorityValue;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'patient_id' => $this->patient_id,
            
            // Tipo
            'type' => $typeEnum?->value ?? $typeValue,
            'type_label' => $typeEnum?->label() ?? 'Sin tipo',
            
            // Estado
            'status' => $statusEnum?->value ?? $statusValue,
            'status_label' => $statusEnum?->label() ?? 'Sin estado',
            'status_color' => $statusEnum?->color() ?? 'gray',
            'status_badge_class' => $statusEnum?->badgeClass() ?? 'bg-gray-100 text-gray-800',
            'status_icon' => $statusEnum?->icon() ?? 'ℹ️',
            'is_active' => $statusEnum && in_array($statusEnum, RequestStatus::activeStatuses()),
            'is_pending' => $statusEnum === RequestStatus::PENDING,
            'is_completed' => $statusEnum === RequestStatus::COMPLETED,
            
            // Prioridad
            'priority' => $priorityEnum?->value ?? $priorityValue,
            'priority_label' => $priorityEnum?->label() ?? 'Sin prioridad',
            'priority_color' => $priorityEnum?->color() ?? 'gray',
            'priority_badge_class' => $priorityEnum?->badgeClass() ?? 'bg-gray-100 text-gray-800',
            
            // Especialidad
            'specialty' => $this->specialty,
            
            // Fechas de seguimiento
            'requested_at' => $this->requested_at?->format('Y-m-d H:i:s'),
            'requested_at_formatted' => $this->requested_at?->format('d/m/Y H:i'),
            'requested_at_relative' => $this->requested_at?->diffForHumans(),
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'started_at_formatted' => $this->started_at?->format('d/m/Y H:i'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'completed_at_formatted' => $this->completed_at?->format('d/m/Y H:i'),
            
            // Tiempos calculados
            'waiting_time_minutes' => $this->waiting_time_minutes,
            'waiting_time_formatted' => $this->waiting_time_formatted,
            'processing_time_minutes' => $this->processing_time_minutes,
            'processing_time_formatted' => $this->processing_time_formatted,
            'elapsed_time_formatted' => $this->elapsed_time_formatted,
            
            // Notas
            'client_notes' => $this->client_notes,
            'operator_notes' => $this->operator_notes,
            
            // Relación con cita
            'appointment_id' => $this->appointment_id,
            'has_appointment' => !is_null($this->appointment_id),
            
            // Paciente
            'patient' => $this->whenLoaded('patient', function() {
                $p = $this->patient;
                return [
                    'id' => $p->id,
                    'uuid' => $p->uuid,
                    'document_type_abbreviation' => strtoupper($p->getRawOriginal('document_type') ?? ''),
                    'document_number' => $p->document_number,
                    'first_name' => $p->first_name,
                    'last_name' => $p->last_name,
                    'full_name' => $p->first_name . ' ' . $p->last_name,
                    'phone' => $p->phone,
                    'whatsapp' => $p->whatsapp,
                    'whatsapp_number' => $p->whatsapp ?? $p->phone,
                    'eps' => $p->relationLoaded('eps') && $p->eps ? [
                        'id' => $p->eps->id,
                        'name' => $p->eps->name,
                    ] : null,
                ];
            }),
            
            // Cita relacionada
            'appointment' => $this->whenLoaded('appointment', function() {
                if (!$this->appointment) return null;
                $a = $this->appointment;
                return [
                    'id' => $a->id,
                    'uuid' => $a->uuid,
                    'status' => $a->getRawOriginal('status'),
                    'appointment_date' => $a->appointment_date?->format('Y-m-d'),
                    'appointment_time' => $a->appointment_time,
                    'formatted_datetime' => $a->formatted_datetime,
                ];
            }),
            
            // Usuarios
            'creator' => $this->whenLoaded('creator', fn() => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            
            'assignee' => $this->whenLoaded('assignee', fn() => $this->assignee ? [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
            ] : null),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_at_formatted' => $this->created_at?->format('d/m/Y H:i'),
        ];
    }
}
