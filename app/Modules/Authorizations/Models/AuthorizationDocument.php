<?php

namespace App\Modules\Authorizations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AuthorizationDocument extends Model
{
    protected $fillable = [
        'authorization_id',
        'type',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function authorization(): BelongsTo
    {
        return $this->belongsTo(Authorization::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'uploaded_by');
    }

    public function getStoragePath(): string
    {
        return $this->file_path;
    }

    public function existsInStorage(): bool
    {
        return Storage::disk('local')->exists($this->file_path);
    }
}
