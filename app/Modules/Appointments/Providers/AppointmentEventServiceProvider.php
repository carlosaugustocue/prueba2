<?php

namespace App\Modules\Appointments\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use App\Modules\Appointments\Events\AppointmentCreated;
use App\Modules\Appointments\Events\AppointmentStatusChanged;
use App\Modules\Appointments\Listeners\LogAppointmentCreated;
use App\Modules\Appointments\Listeners\LogAppointmentStatusChanged;

class AppointmentEventServiceProvider extends EventServiceProvider
{
    protected $listen = [
        AppointmentCreated::class => [
            LogAppointmentCreated::class,
        ],
        AppointmentStatusChanged::class => [
            LogAppointmentStatusChanged::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
