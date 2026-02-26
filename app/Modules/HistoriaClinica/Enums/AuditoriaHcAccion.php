<?php

namespace App\Modules\HistoriaClinica\Enums;

enum AuditoriaHcAccion: string
{
    case CREATE = 'CREATE';
    case READ = 'READ';
    case UPDATE = 'UPDATE';
}
