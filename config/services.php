<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business API (Meta)
    |--------------------------------------------------------------------------
    */
    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'language' => env('WHATSAPP_LANGUAGE', 'es_CO'),
        'templates' => [
            'confirmation' => env('WHATSAPP_TEMPLATE_CONFIRMATION', 'serviconli_cita_confirmada'),
            'reminder_morning' => env('WHATSAPP_TEMPLATE_REMINDER_MORNING', 'serviconli_recordatorio_cita_manana'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Citas
    |--------------------------------------------------------------------------
    */
    'appointments' => [
        'reminder_hours_before' => env('REMINDER_HOURS_BEFORE', 24),
        'confirmation_auto_send' => env('CONFIRMATION_AUTO_SEND', true),
        // Recordatorio "mañana anterior" (D-1) a hora fija
        'reminder_send_time' => env('REMINDER_SEND_TIME', '08:00'),
        'reminder_timezone' => env('REMINDER_TIMEZONE', 'America/Bogota'),
    ],

];
