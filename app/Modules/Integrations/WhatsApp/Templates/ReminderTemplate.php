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
        $time = $appointment->appointment_time ? substr((string) $appointment->appointment_time, 0, 5) : 'la hora asignada';
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

    /**
     * Variables para la plantilla de WhatsApp Cloud API (recordatorio mañana anterior).
     *
     * Orden esperado:
     * 1) Primer nombre
     * 2) Tipo de cita
     * 3) Fecha (DD/MM/YYYY)
     * 4) Hora (HH:mm)
     * 5) IPS/Sede
     * 6) Dirección con prefijo ", " o vacío
     */
    public function templateParameters(Appointment $appointment): array
    {
        $patient = $appointment->patient;
        $firstName = trim((string) ($patient->first_name ?? ''));

        $typeDesc = $appointment->type->value === 'specialist' && $appointment->specialty
            ? "especialista ({$appointment->specialty})"
            : $appointment->type->label();

        $date = $appointment->appointment_date ? $appointment->appointment_date->format('d/m/Y') : '';
        $time = $appointment->appointment_time ? substr((string) $appointment->appointment_time, 0, 5) : '';
        $location = (string) ($appointment->location_name ?? '');
        $address = trim((string) ($appointment->location_address ?? ''));
        $addressWithPrefix = $address !== '' ? ', ' . $address : '';

        return [$firstName, $typeDesc, $date, $time, $location, $addressWithPrefix];
    }
}
