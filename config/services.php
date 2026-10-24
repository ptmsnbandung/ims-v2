<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'meta_whatsapp' => [
        'token'               => env('META_WA_TOKEN'),
        'phone_number_id'     => env('META_WA_PHONE_NUMBER_ID'),
        'business_account_id' => env('META_WA_BUSINESS_ACCOUNT_ID'),
        'webhook_verify_token'=> env('META_WA_WEBHOOK_VERIFY_TOKEN', 'ims_secret_token_2026'),
        'api_version'         => env('META_WA_API_VERSION', 'v21.0'),
    ],

    'midtrans' => [
        'server_key'    => env('MIDTRANS_SERVER_KEY', ''),
        'client_key'    => env('MIDTRANS_CLIENT_KEY', ''),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model'   => env('GEMINI_MODEL', 'gemini-3.8-flash'),
        'timeout' => (int)env('GEMINI_TIMEOUT', 60),
    ],

    'hostinger_mail' => [
        'token'       => env('HOSTINGER_MAIL_TOKEN', ''),
        'from'        => env('HOSTINGER_MAIL_FROM', 'info@ptmsn.co.id'),
        'daily_limit' => (int) env('HOSTINGER_MAIL_DAILY_LIMIT', 1000),
    ],

];
