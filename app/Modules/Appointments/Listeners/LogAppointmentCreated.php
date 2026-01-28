<?php

namespace App\Modules\Appointments\Listeners;

use App\Modules\Appointments\Events\AppointmentCreated;
use App\Modules\Appointments\Models\AppointmentHistory;

class LogAppointmentCreated
{
    public function handle(AppointmentCreated $event): void
    {
        AppointmentHistory::log(
            $event->appointment,
            AppointmentHistory::ACTION_CREATED,
            null,
            null,
            null,
            'Cita creada en el sistema'
        );
    }
}
