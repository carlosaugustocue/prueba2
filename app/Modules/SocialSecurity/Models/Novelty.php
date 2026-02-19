<?php

namespace App\Modules\SocialSecurity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Novelty extends Model
{
    protected $table = 'novelties';

    protected $fillable = [
        'affiliate_id',
        'novelty_type_id',
        'effective_date',
        'description',
        'old_value',
        'new_value',
        'registered_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Patients\Models\Affiliate::class);
    }

    public function noveltyType(): BelongsTo
    {
        return $this->belongsTo(NoveltyType::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'registered_by');
    }
}
