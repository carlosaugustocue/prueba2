<?php

namespace App\Modules\SocialSecurity\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndependentContract extends Model
{
    public const CONTRACT_TYPES = [
        'SERVICE_PROVISION',
        'CIVIL_WORK',
        'CONSULTING',
        'OTHER',
    ];

    protected $fillable = [
        'affiliate_id',
        'payer_id',
        'contract_reference',
        'contract_type',
        'start_date',
        'end_date',
        'monthly_income',
        'risk_class',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'monthly_income' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Affiliates\Models\Affiliate::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(Payer::class);
    }

    public function scopeActiveForPeriod(Builder $query, int $year, int $month): Builder
    {
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = (clone $periodStart)->endOfMonth()->endOfDay();

        return $query
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $periodEnd->toDateString())
            ->where(function (Builder $q) use ($periodStart) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $periodStart->toDateString());
            });
    }
}

