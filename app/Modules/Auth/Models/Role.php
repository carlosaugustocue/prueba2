<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const ADMIN = 'admin';
    public const SUPERVISOR = 'supervisor';
    public const AGENT = 'agent';
    public const ATENCION = 'atencion';
    public const SEGURIDAD_SOCIAL = 'seguridad_social';
    public const CARTERA = 'cartera';

    /** Roles que pueden acceder al módulo de Citas (solicitudes + citas) */
    public const ROLES_CITAS = [self::ADMIN, self::SUPERVISOR, self::AGENT, self::ATENCION];

    /** Roles que pueden acceder al módulo de Afiliados / Seguridad Social */
    public const ROLES_AFILIADOS = [self::ADMIN, self::SUPERVISOR, self::AGENT, self::ATENCION, self::SEGURIDAD_SOCIAL, self::CARTERA];

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->name === self::ADMIN) {
            return true;
        }
        return in_array($permission, $this->permissions ?? []);
    }
}
