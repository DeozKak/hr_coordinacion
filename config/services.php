<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'groq' => [
        'key' => env('GROQ_API_KEY'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp
    |--------------------------------------------------------------------------
    |
    | Dos integraciones distintas: whapi es la pasarela que avisa al usuario de
    | su visita, y meta es la API oficial de la Graph que atiende el bot.
    | Los valores viven en .env; aquí no puede quedar ninguno escrito, porque
    | este archivo sí va al repositorio.
    |
    */

    'whapi' => [
        'token' => env('WHAPI_TOKEN'),
        'url'   => env('WHAPI_URL', 'https://gate.whapi.cloud'),
    ],

    'meta_whatsapp' => [
        'token'        => env('META_WHATSAPP_TOKEN'),
        'phone_id'     => env('META_WHATSAPP_PHONE_ID'),
        'version'      => env('META_WHATSAPP_VERSION', 'v21.0'),
        'verify_token' => env('META_WHATSAPP_VERIFY_TOKEN'),
        'app_secret'   => env('META_WHATSAPP_APP_SECRET'),
    ],

];
