<?php

namespace App\Modules\PilaManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateNote extends Model
{
    protected $table = 'affiliate_notes';

    protected $fillable = [
        'affiliate_id',
        'type',
        'content',
        'is_pinned',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Affiliates\Models\Affiliate::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

