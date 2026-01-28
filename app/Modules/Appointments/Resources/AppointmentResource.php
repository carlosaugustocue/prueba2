<?php

namespace App\Modules\Appointments\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Patients\Resources\PatientResource;
use App\Modules\Appointments\Enums\AppointmentStatus;
use App\Modules\Appointments\Enums\AppointmentType;
use App\Modules\Appointments\Enums\Priority;

class AppointmentResource extends JsonResource
{
    private function getActionType(string $action): string
    {
        return match($action) {
            'created' => 'create',
            'updated' => 'update',
            'status_changed' => 'status',
            'confirmation_sent' => 'message',
            'reminder_sent' => 'message',
            default => 'info',
        };
    }

    private function getActionIcon(string $action): string
    {
        return match($action) {
            'created' => '🎉',
            'updated' => '✏️',
            'status_changed' => '🔄',
            'confirmation_sent' => '📱',
            'reminder_sent' => '⏰',
            default => 'ℹ️',
        };
    }

    private function getActionColor(string $action): string
    {
        return match($action) {
            'created' => 'green',
            'updated' => 'blue',
            'status_changed' => 'purple',
            'confirmation_sent' => 'emerald',
            'reminder_sent' => 'amber',
            default => 'gray',
        };
    }

    public function toArray(Request $request): array
    {
        // Acceder directamente a los atributos del modelo
        $typeValue = $this->resource->getRawOriginal('type') ?? $this->type;
        $statusValue = $this->resource->getRawOriginal('status') ?? $this->status;
        $priorityValue = $this->resource->getRawOriginal('priority') ?? $this->priority;

        // Convertir a enum si es string
        $typeEnum = is_string($typeValue) ? AppointmentType::tryFrom($typeValue) : $typeValue;
        $statusEnum = is_string($statusValue) ? AppointmentStatus::tryFrom($statusValue) : $statusValue;
        $priorityEnum = is_string($priorityValue) ? Priority::tryFrom($priorityValue) : $priorityValue;

        // Formatear fecha
        $appointmentDate = $this->resource->getRawOriginal('appointment_date');
        $dateFormatted = null;
        $dateValue = null;
        if ($appointmentDate) {
            $dateValue = substr($appointmentDate, 0, 10); // YYYY-MM-DD
            $dateFormatted = date('d/m/Y', strtotime($appointmentDate));
        }

        // Formatear hora
        $appointmentTime = $this->resource->getRawOriginal('appointment_time');
        $timeValue = $appointmentTime ? substr($appointmentTime, 0, 5) : null;

        // DateTime formateado
        $formattedDateTime = $dateFormatted;
        if ($formattedDateTime && $timeValue) {
            $formattedDateTime .= ' ' . $timeValue;
        }

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
            
            // Prioridad
            'priority' => $priorityEnum?->value ?? $priorityValue,
            'priority_label' => $priorityEnum?->label() ?? 'Sin prioridad',
            'priority_color' => $priorityEnum?->color() ?? 'gray',
            'priority_badge_class' => $priorityEnum?->badgeClass() ?? 'bg-gray-100 text-gray-800',
            
            // Especialidad
            'specialty' => $this->specialty,
            
            // Fecha y hora
            'appointment_date' => $dateValue,
            'appointment_date_formatted' => $dateFormatted,
            'appointment_time' => $timeValue,
            'formatted_datetime' => $formattedDateTime,
            
            // Ubicación y detalles
            'doctor_name' => $this->doctor_name,
            'location_name' => $this->location_name,
            'location_address' => $this->location_address,
            'authorization_number' => $this->authorization_number,
            'specifications' => $this->specifications,
            'internal_notes' => $this->internal_notes,
            
            // Seguimiento de solicitud (para medir eficiencia)
            'requested_at' => $this->requested_at?->format('Y-m-d H:i:s'),
            'requested_at_formatted' => $this->requested_at?->format('d/m/Y H:i'),
            'processed_at' => $this->processed_at?->format('Y-m-d H:i:s'),
            'processed_at_formatted' => $this->processed_at?->format('d/m/Y H:i'),
            'processing_time_hours' => $this->processing_time_hours,
            'processing_time_formatted' => $this->processing_time_formatted,
            
            // Mensajes enviados
            'confirmation_sent_at' => $this->confirmation_sent_at ? $this->confirmation_sent_at->format('d/m/Y H:i') : null,
            'reminder_sent_at' => $this->reminder_sent_at ? $this->reminder_sent_at->format('d/m/Y H:i') : null,
            
            // Capacidades
            'can_send_confirmation' => $statusEnum && in_array($statusEnum, [AppointmentStatus::CONFIRMED, AppointmentStatus::IN_PROGRESS]),
            'allowed_status_transitions' => $statusEnum ? collect($statusEnum->allowedTransitions())->map(fn($s) => [
                'value' => $s->value,
                'label' => $s->label()
            ])->toArray() : [],
            
            // Relaciones - Patient
            'patient' => $this->whenLoaded('patient', function() {
                $p = $this->patient;
                return [
                    'id' => $p->id,
                    'uuid' => $p->uuid,
                    'document_type' => $p->getRawOriginal('document_type'),
                    'document_type_abbreviation' => strtoupper($p->getRawOriginal('document_type') ?? ''),
                    'document_number' => $p->document_number,
                    'first_name' => $p->first_name,
                    'last_name' => $p->last_name,
                    'full_name' => $p->first_name . ' ' . $p->last_name,
                    'phone' => $p->phone,
                    'whatsapp' => $p->whatsapp,
                    'whatsapp_number' => $p->whatsapp ?? $p->phone,
                    'email' => $p->email,
                    'eps' => $p->relationLoaded('eps') && $p->eps ? [
                        'id' => $p->eps->id,
                        'name' => $p->eps->name,
                        'code' => $p->eps->code,
                    ] : null,
                ];
            }),
            
            // Creator
            'creator' => $this->whenLoaded('creator', function() {
                return $this->creator ? [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name
                ] : null;
            }),
            
            // Assignee
            'assignee' => $this->whenLoaded('assignee', function() {
                return $this->assignee ? [
                    'id' => $this->assignee->id,
                    'name' => $this->assignee->name
                ] : null;
            }),
            
            // History (Timeline)
            'history' => $this->whenLoaded('history', function() {
                return $this->history->map(fn($h) => [
                    'id' => $h->id,
                    'action' => $h->action,
                    'action_type' => $this->getActionType($h->action),
                    'action_icon' => $this->getActionIcon($h->action),
                    'action_color' => $this->getActionColor($h->action),
                    'description' => $h->getActionDescription(),
                    'field_changed' => $h->field_changed,
                    'old_value' => $h->old_value,
                    'new_value' => $h->new_value,
                    'user' => $h->user?->name ?? 'Sistema',
                    'user_id' => $h->user_id,
                    'created_at' => $h->created_at?->format('d/m/Y H:i:s'),
                    'created_at_relative' => $h->created_at?->diffForHumans(),
                    'ip_address' => $h->ip_address,
                ])->toArray();
            }),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_at_formatted' => $this->created_at?->format('d/m/Y H:i'),
        ];
    }
}
