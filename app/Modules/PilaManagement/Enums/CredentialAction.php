<?php

namespace App\Modules\PilaManagement\Enums;

enum CredentialAction: string
{
    case VIEWED = 'viewed';
    case UPDATED = 'updated';
    case CREATED = 'created';
}

