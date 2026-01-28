<?php

namespace App\Modules\Appointments\Listeners;

use App\Modules\Appointments\Events\AppointmentStatusChanged;
use App\Modules\Appointments\Models\AppointmentHistory;

class LogAppointmentStatusChanged
{
    public function handle(AppointmentStatusChanged $event): void
    {
        AppointmentHistory::log(
            $event->appointment,
            AppointmentHistory::ACTION_STATUS_CHANGED,
            'status',
            $event->oldStatus->label(),
            $event->newStatus->label(),
            "Estado cambiado de '{$event->oldStatus->label()}' a '{$event->newStatus->label()}'"
        );
    }
}
