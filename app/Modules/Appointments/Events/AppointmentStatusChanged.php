<?php

namespace App\Modules\Appointments\Events;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Appointments\Enums\AppointmentStatus;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public AppointmentStatus $oldStatus,
        public AppointmentStatus $newStatus
    ) {}
}
