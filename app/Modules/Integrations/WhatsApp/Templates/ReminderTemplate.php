<?php

namespace App\Modules\Integrations\WhatsApp\Templates;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Core\Helpers\DateHelper;

class ReminderTemplate
{
    /**
     * Construir mensaje de recordatorio (RF-21)
     */
    public function build(Appointment $appointment): string
    {
        $date = DateHelper::formatLong($appointment->appointment_date);
        $time = $appointment->appointment_time?->format('H:i') ?? 'la hora asignada';
        $location = $appointment->location_name ?? 'la sede asignada';
        $doctor = $appointment->doctor_name ?? 'el especialista asignado';

        $message = "Le saluda Biviana de la Central de Citas de Serviconli. 🌿\n\n";
        $message .= "Me permito recordarle que tiene una cita programada el día *{$date}* a las *{$time}*, ";
        $message .= "en *{$location}*, con el doctor/a *{$doctor}*.\n\n";
        $message .= "En Serviconli estamos comprometidos con su bienestar y el de su familia. ";
        $message .= "Si tiene alguna inquietud, no dude en contactarnos.\n\n";
        $message .= "¡Gracias por confiar en nosotros! 💙\n\n";
        $message .= "— Equipo Serviconli";

        return $message;
    }
}
