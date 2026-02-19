<?php

namespace App\Modules\SocialSecurity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollTracking extends Model
{
    protected $table = 'payroll_trackings';

    protected $fillable = [
        'payroll_id',
        'event',
        'user_id',
        'old_status',
        'new_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class);
    }
}
