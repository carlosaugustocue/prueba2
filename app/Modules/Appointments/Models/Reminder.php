<?php

namespace App\Modules\Appointments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    public const TYPE_CONFIRMATION = 'confirmation';
    public const TYPE_REMINDER_24H = 'reminder_24h';

    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_EMAIL = 'email';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'appointment_id', 'type', 'channel', 'recipient', 'message',
        'scheduled_at', 'sent_at', 'status', 'response', 'error_message', 'attempts',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'response' => 'array',
            'attempts' => 'integer',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function isPending(): bool { return $this->status === self::STATUS_PENDING; }

    public function markAsSent(array $response = []): bool
    {
        return $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'response' => $response,
        ]);
    }

    public function markAsFailed(string $error): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
            'attempts' => $this->attempts + 1,
        ]);
    }

    public function scopeDueToSend($query)
    {
        return $query->where('status', self::STATUS_PENDING)->where('scheduled_at', '<=', now());
    }
}
