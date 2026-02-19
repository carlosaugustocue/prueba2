<?php

namespace App\Modules\SocialSecurity\Models;

use App\Modules\SocialSecurity\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $table = 'payrolls';

    protected $fillable = [
        'affiliate_id',
        'month',
        'year',
        'due_date',
        'status',
        'health_amount',
        'pension_amount',
        'arl_amount',
        'ccf_amount',
        'sent_at',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'health_amount' => 'decimal:2',
            'pension_amount' => 'decimal:2',
            'arl_amount' => 'decimal:2',
            'ccf_amount' => 'decimal:2',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Patients\Models\Affiliate::class);
    }

    public function supportDocuments(): HasMany
    {
        return $this->hasMany(SupportDocument::class);
    }

    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class);
    }

    public function trackings(): HasMany
    {
        return $this->hasMany(PayrollTracking::class);
    }

    public function getStatusEnum(): ?PayrollStatus
    {
        return $this->status ? PayrollStatus::tryFrom($this->status) : null;
    }
}
