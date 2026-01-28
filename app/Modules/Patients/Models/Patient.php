<?php

namespace App\Modules\Patients\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Core\Traits\HasUuid;
use App\Modules\Core\Traits\Searchable;
use App\Modules\Patients\Enums\DocumentType;
use App\Modules\Patients\Enums\PatientType;

class Patient extends Model
{
    use SoftDeletes, HasUuid, Searchable;

    protected $fillable = [
        'uuid',
        'document_type',
        'document_number',
        'first_name',
        'last_name',
        'phone',
        'whatsapp',
        'email',
        'address',
        'eps_id',
        'patient_type',
        'holder_id',
        'birth_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected array $searchable = [
        'first_name',
        'last_name',
        'document_number',
        'phone',
        'whatsapp',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'patient_type' => PatientType::class,
            'birth_date' => 'date',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function eps(): BelongsTo
    {
        return $this->belongsTo(Eps::class);
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'holder_id');
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Patient::class, 'holder_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(\App\Modules\Appointments\Models\Appointment::class);
    }

    public function getWhatsAppNumber(): ?string
    {
        return $this->whatsapp ?? $this->phone;
    }
}
