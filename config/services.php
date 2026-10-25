<?php
return [
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],
    'nikita' => [
        'otp_key' => env('NIKITA_OTP_API_KEY'),
        'sender' => 'SMSPRO.KG', // configured in the Nikita OTP dashboard
    ],
    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', false),
        'sender_number' => env('WHATSAPP_SENDER_NUMBER', '996550512233'),
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'version' => env('WHATSAPP_API_VERSION'),
        'language' => env('WHATSAPP_LANGUAGE', 'ru'),
        'client_template' => env('WHATSAPP_CLIENT_TEMPLATE', 'bosh_booking_client'),
        'master_template' => env('WHATSAPP_MASTER_TEMPLATE', 'bosh_booking_master'),
    ],
    'postmark' => ['key' => env('POSTMARK_API_KEY')],
    'resend' => ['key' => env('RESEND_API_KEY')],
    'ses' => ['key' => env('AWS_ACCESS_KEY_ID'), 'secret' => env('AWS_SECRET_ACCESS_KEY'), 'region' => env('AWS_DEFAULT_REGION', 'us-east-1')],
    'slack' => ['notifications' => ['bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'), 'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL')]],
];
