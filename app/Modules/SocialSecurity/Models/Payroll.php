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
        'parafiscal_amount',
        'fsp_amount',
        'total_amount',
        'days_worked',
        'sent_at',
        'paid_at',
        'notes',
        'calculation_metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => PayrollStatus::class,
            'due_date' => 'date',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'health_amount' => 'decimal:2',
            'pension_amount' => 'decimal:2',
            'arl_amount' => 'decimal:2',
            'ccf_amount' => 'decimal:2',
            'parafiscal_amount' => 'decimal:2',
            'fsp_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'calculation_metadata' => 'array',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Affiliates\Models\Affiliate::class);
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

}
