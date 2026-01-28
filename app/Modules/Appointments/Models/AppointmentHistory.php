<?php

namespace App\Modules\Appointments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'appointment_id', 'user_id', 'action', 'field_changed',
        'old_value', 'new_value', 'description', 'ip_address', 'user_agent', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_STATUS_CHANGED = 'status_changed';
    public const ACTION_CONFIRMATION_SENT = 'confirmation_sent';
    public const ACTION_REMINDER_SENT = 'reminder_sent';

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class);
    }

    public function getActionDescription(): string
    {
        return match($this->action) {
            self::ACTION_CREATED => 'Cita creada',
            self::ACTION_UPDATED => "Campo '{$this->field_changed}' actualizado",
            self::ACTION_STATUS_CHANGED => "Estado cambiado de '{$this->old_value}' a '{$this->new_value}'",
            self::ACTION_CONFIRMATION_SENT => 'Confirmación enviada al paciente',
            self::ACTION_REMINDER_SENT => 'Recordatorio enviado al paciente',
            default => $this->description ?? $this->action,
        };
    }

    public static function log(Appointment $appointment, string $action, ?string $fieldChanged = null, mixed $oldValue = null, mixed $newValue = null, ?string $description = null): self
    {
        return self::create([
            'appointment_id' => $appointment->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'field_changed' => $fieldChanged,
            'old_value' => is_array($oldValue) ? json_encode($oldValue) : $oldValue,
            'new_value' => is_array($newValue) ? json_encode($newValue) : $newValue,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
