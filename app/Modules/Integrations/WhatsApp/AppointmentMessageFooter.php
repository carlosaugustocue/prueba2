<?php

namespace App\Modules\Integrations\WhatsApp;

/**
 * Mensaje diplomático que se envía después de confirmación y recordatorio de cita.
 * Invita a gestionar la cita si no podrán asistir y, con cordialidad, a no volver a solicitar
 * si no tienen certeza de asistir, para no dificultar la operación.
 */
class AppointmentMessageFooter
{
    /**
     * Texto cordial y diplomático para el cierre de los envíos por WhatsApp.
     */
    public static function noShowNotice(): string
    {
        return "Si en algún momento no le fuera posible asistir, le agradeceríamos que gestione la cancelación o reprogramación directamente con su IPS. "
            . "Le pedimos con todo respeto que solo solicite citas cuando tenga la certeza de poder asistir; "
            . "las inasistencias nos complican un poco la operación y el poder atender a todos con la misma calidad. "
            . "Agradecemos de corazón su comprensión. Gracias. 💙";
    }
}
