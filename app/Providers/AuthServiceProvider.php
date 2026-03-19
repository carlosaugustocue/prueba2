<?php

namespace App\Providers;

use App\Modules\Auth\Models\User;
use App\Modules\PilaManagement\Models\PilaCredential;
use App\Modules\PilaManagement\Models\PortalCredential;
use App\Modules\PilaManagement\Policies\CredentialPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Map credential models to the single credential policy.
     */
    protected $policies = [
        PilaCredential::class => CredentialPolicy::class,
        PortalCredential::class => CredentialPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Ability sin modelo (vista de auditoría por rol).
        Gate::define('viewAuditLog', function (User $user) {
            return app(CredentialPolicy::class)->viewAuditLog($user);
        });
    }
}

