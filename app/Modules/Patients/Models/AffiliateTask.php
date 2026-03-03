<?php

namespace App\Modules\Patients\Models;

use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateTask extends Model
{
    public const AREA_CARTERA = 'cartera';
    public const AREA_SEGURIDAD_SOCIAL = 'seguridad_social';

    protected $table = 'affiliate_tasks';

    protected $fillable = [
        'affiliate_id',
        'area',
        'description',
        'is_completed',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}

