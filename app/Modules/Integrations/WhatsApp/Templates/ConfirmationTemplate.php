<?php

namespace App\Modules\Integrations\WhatsApp\Templates;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Core\Helpers\DateHelper;

class ConfirmationTemplate
{
    /**
     * Construir mensaje de confirmación de cita (RF-20)
     */
    public function build(Appointment $appointment): string
    {
        $patient = $appointment->patient;
        $dateInfo = DateHelper::formatForConfirmation($appointment->appointment_date);
        $fullName = mb_strtoupper($patient->full_name);
        $typeDesc = $this->getAppointmentTypeDescription($appointment);
        $time = $appointment->appointment_time?->format('H:i') ?? 'por confirmar';
        $doctor = $appointment->doctor_name ? 'DR. ' . mb_strtoupper($appointment->doctor_name) : 'el especialista asignado';
        $location = $appointment->location_name ?? 'la sede asignada';
        $address = $appointment->location_address ?? '';
        $specifications = $appointment->specifications ?? '';

        $message = "La cita de *{$fullName}* para *{$typeDesc}* fue asignada para el día ";
        $message .= "*{$dateInfo['day_name']}* *{$dateInfo['day']}* de *{$dateInfo['month']}* del *{$dateInfo['year']}* ";
        $message .= "a las *{$time}* con el *{$doctor}*. ";
        $message .= "En *{$location}*";
        if ($address) $message .= ", {$address}";
        $message .= ", recuerde llegar 30 minutos antes con el documento de identidad original, cuota moderadora y tapabocas.";
        if ($specifications) $message .= " {$specifications}";
        $message .= "\n\n¡Gracias por confiar en nosotros! 💙\n\n— Equipo Serviconli";

        return $message;
    }

    protected function getAppointmentTypeDescription(Appointment $appointment): string
    {
        $typeLabel = $appointment->type->shortDescription();
        if ($appointment->type->value === 'specialist' && $appointment->specialty) {
            return "cita con especialista ({$appointment->specialty})";
        }
        return $typeLabel;
    }
}
