<?php

namespace App\Modules\Authorizations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Authorizations\Enums\AuthorizationStatus;

class Authorization extends Model
{
    use SoftDeletes, HasUuid;

    protected $fillable = [
        'uuid',
        'appointment_request_id',
        'affiliate_id',
        'eps_id',
        'service_type',
        'diagnosis_or_reason',
        'status',
        'radicated_at',
        'radicado_number',
        'authorization_number',
        'authorized_ips_name',
        'valid_until',
        'denial_reason',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => AuthorizationStatus::class,
            'radicated_at' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function appointmentRequest(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\AppointmentRequests\Models\AppointmentRequest::class);
    }

    public function appointment(): HasOne
    {
        return $this->hasOne(\App\Modules\Appointments\Models\Appointment::class);
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Patients\Models\Affiliate::class);
    }

    public function eps(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Patients\Models\Eps::class);
    }

    public function stateHistories(): HasMany
    {
        return $this->hasMany(AuthorizationStateHistory::class)->orderByDesc('created_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AuthorizationDocument::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'updated_by');
    }

    public function changeStatus(AuthorizationStatus $newStatus, ?int $userId = null, ?string $notes = null): bool
    {
        if (! $this->status->canTransitionTo($newStatus)) {
            return false;
        }

        $from = $this->status;
        $this->status = $newStatus;
        $this->updated_by = $userId;

        if (! $this->save()) {
            return false;
        }

        $this->stateHistories()->create([
            'from_status' => $from->value,
            'to_status' => $newStatus->value,
            'user_id' => $userId,
            'notes' => $notes,
            'created_at' => now(),
        ]);

        return true;
    }

    public function scopeStatus($query, string|AuthorizationStatus $status)
    {
        $status = $status instanceof AuthorizationStatus ? $status : AuthorizationStatus::from($status);
        return $query->where('status', $status);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', AuthorizationStatus::APPROVED);
    }

    public function scopePendingRadication($query)
    {
        return $query->where('status', AuthorizationStatus::PENDING_RADICATION);
    }

    public function scopeRadicated($query)
    {
        return $query->where('status', AuthorizationStatus::RADICATED);
    }

    public function isApproved(): bool
    {
        return $this->status === AuthorizationStatus::APPROVED;
    }

    public function isWithinValidity(): bool
    {
        return $this->valid_until && $this->valid_until->isFuture();
    }
}
