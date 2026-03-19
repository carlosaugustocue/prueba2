<?php

namespace App\Modules\Affiliates\Models;

use App\Modules\Auth\Models\User;
use App\Modules\SocialSecurity\Models\AccountingRegistry;
use App\Modules\SocialSecurity\Models\Payroll;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliatePayment extends Model
{
    protected $table = 'affiliate_payments';

    protected $fillable = [
        'affiliate_id',
        'payroll_id',
        'accounting_registry_id',
        'payment_date',
        'amount',
        'external_number',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function accountingRegistry(): BelongsTo
    {
        return $this->belongsTo(AccountingRegistry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

