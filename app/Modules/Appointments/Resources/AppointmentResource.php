<?php

namespace App\Modules\Appointments\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Affiliates\Resources\AffiliateResource;
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

    /** Valor legible en español para el timeline (enum, fecha u hora) */
    private function historyValueDisplay(?string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $str = is_array($value) ? json_encode($value) : (string) $value;
        return match ($field) {
            'status' => AppointmentStatus::tryFrom($str)?->label() ?? $str,
            'type' => AppointmentType::tryFrom($str)?->label() ?? $str,
            'priority' => Priority::tryFrom($str)?->label() ?? $str,
            'appointment_date' => $this->formatHumanDate($str),
            'appointment_time' => $this->formatHumanTime($str),
            default => $str,
        };
    }

    /** Fecha legible: acepta Y-m-d o ISO y devuelve "5 de febrero de 2026" */
    private function formatHumanDate(string $value): ?string
    {
        try {
            $date = \Carbon\Carbon::parse($value)->locale('es');
            return $date->translatedFormat('j \d\e F \d\e Y');
        } catch (\Throwable) {
            return $value;
        }
    }

    /** Hora legible: "10:00:00" → "10:00" */
    private function formatHumanTime(string $value): ?string
    {
        try {
            $parsed = \Carbon\Carbon::parse($value);
            return $parsed->format('H:i');
        } catch (\Throwable) {
            if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', trim($value))) {
                return substr($value, 0, 5);
            }
            return $value;
        }
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

        $confirmationReminderStatus = null;
        $confirmationReminderError = null;
        if ($this->resource->relationLoaded('reminders')) {
            $last = $this->reminders
                ->where('type', \App\Modules\Appointments\Models\Reminder::TYPE_CONFIRMATION)
                ->where('channel', \App\Modules\Appointments\Models\Reminder::CHANNEL_WHATSAPP)
                ->sortByDesc('created_at')
                ->first();

            if ($last) {
                $confirmationReminderStatus = $last->status;
                $confirmationReminderError = $last->error_message;
            }
        }

        $whatsappConfirmationStatus =
            $this->confirmation_sent_at ? 'sent' :
            ($confirmationReminderStatus === \App\Modules\Appointments\Models\Reminder::STATUS_FAILED ? 'failed' :
            (in_array($confirmationReminderStatus, [\App\Modules\Appointments\Models\Reminder::STATUS_PENDING, \App\Modules\Appointments\Models\Reminder::STATUS_PROCESSING], true) ? 'pending' : 'not_sent'));

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'affiliate_id' => $this->affiliate_id,
            
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
            'location_phone' => $this->location_phone,
            'authorization_number' => $this->authorization_number,
            'specifications' => $this->specifications,
            'internal_notes' => $this->internal_notes,
            
            // Mensajes enviados
            'confirmation_sent_at' => $this->confirmation_sent_at ? $this->confirmation_sent_at->format('d/m/Y H:i') : null,
            'reminder_sent_at' => $this->reminder_sent_at ? $this->reminder_sent_at->format('d/m/Y H:i') : null,

            // WhatsApp (operativo)
            'whatsapp_confirmation_status' => $whatsappConfirmationStatus,
            'whatsapp_confirmation_error' => $whatsappConfirmationStatus === 'failed' ? $confirmationReminderError : null,
            
            // Capacidades
            'can_send_confirmation' => $statusEnum === AppointmentStatus::CONFIRMED && $whatsappConfirmationStatus !== 'sent' && $whatsappConfirmationStatus !== 'pending',
            'allowed_status_transitions' => $statusEnum ? collect($statusEnum->allowedTransitions())->map(fn($s) => [
                'value' => $s->value,
                'label' => $s->label()
            ])->toArray() : [],
            
            // Relaciones - Affiliate (nombre completo: todos los nombres y apellidos)
            'affiliate' => $this->whenLoaded('affiliate', function () {
                $a = $this->affiliate;
                return [
                    'id' => $a->id,
                    'uuid' => $a->uuid,
                    'document_type' => $a->getRawOriginal('document_type'),
                    'document_type_abbreviation' => strtoupper($a->getRawOriginal('document_type') ?? ''),
                    'document_number' => $a->document_number,
                    'first_name' => $a->first_name,
                    'second_name' => $a->second_name,
                    'last_name' => $a->last_name,
                    'second_last_name' => $a->second_last_name,
                    'full_name' => $a->full_name,
                    'phone' => $a->phone,
                    'whatsapp' => $a->whatsapp,
                    'whatsapp_number' => $a->getWhatsAppNumber(),
                    'email' => $a->email,
                    'eps' => $a->relationLoaded('socialSecurityProfile') && $a->socialSecurityProfile?->eps ? [
                        'id' => $a->socialSecurityProfile->eps->id,
                        'name' => $a->socialSecurityProfile->eps->name,
                        'code' => $a->socialSecurityProfile->eps->code,
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
            
            // History (Timeline) — textos en español
            'history' => $this->whenLoaded('history', function () {
                return $this->history->map(fn ($h) => [
                    'id' => $h->id,
                    'action' => $h->action,
                    'action_type' => $this->getActionType($h->action),
                    'action_icon' => $this->getActionIcon($h->action),
                    'action_color' => $this->getActionColor($h->action),
                    'description' => $h->getActionDescription(),
                    'field_changed' => $h->field_changed,
                    'old_value' => $h->old_value,
                    'new_value' => $h->new_value,
                    'old_value_display' => $this->historyValueDisplay($h->field_changed, $h->old_value),
                    'new_value_display' => $this->historyValueDisplay($h->field_changed, $h->new_value),
                    'user' => $h->user?->name ?? 'Sistema',
                    'user_id' => $h->user_id,
                    'created_at' => $h->created_at?->format('d/m/Y H:i:s'),
                    'created_at_human' => $h->created_at ? $h->created_at->locale('es')->translatedFormat('j \d\e F \d\e Y, H:i') : null,
                    'created_at_relative' => $h->created_at?->locale('es')->diffForHumans(),
                    'ip_address' => $h->ip_address,
                ])->toArray();
            }),

            // Comunicaciones (ej. teléfono)
            'communications' => $this->whenLoaded('communications', function () {
                return $this->communications->map(fn ($c) => [
                    'id' => $c->id,
                    'channel' => $c->channel,
                    'category' => $c->category,
                    'note' => $c->note,
                    'user' => $c->user?->name ?? 'Sistema',
                    'created_at_formatted' => $c->created_at?->format('d/m/Y H:i'),
                ])->toArray();
            }),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_at_formatted' => $this->created_at?->format('d/m/Y H:i'),
        ];
    }
}
