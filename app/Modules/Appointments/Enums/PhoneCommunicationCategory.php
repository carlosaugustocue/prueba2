<?php

namespace App\Modules\Appointments\Enums;

enum PhoneCommunicationCategory: string
{
    case REACHED = 'reached';
    case NO_ANSWER = 'no_answer';
    case BUSY = 'busy';
    case WRONG_NUMBER = 'wrong_number';
    case VOICEMAIL = 'voicemail';
    case CALL_BACK_LATER = 'call_back_later';
    case NOT_POSSIBLE = 'not_possible';

    public function label(): string
    {
        return match ($this) {
            self::REACHED => 'Contactado',
            self::NO_ANSWER => 'No contestó',
            self::BUSY => 'Ocupado',
            self::WRONG_NUMBER => 'Número equivocado',
            self::VOICEMAIL => 'Buzón de voz',
            self::CALL_BACK_LATER => 'Volver a llamar',
            self::NOT_POSSIBLE => 'No fue posible',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}

