<?php

namespace App\Modules\Appointments\Events;

use App\Modules\Appointments\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Appointment $appointment) {}
}
