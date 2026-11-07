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
    'freelancer' => [
        'client_id' => env('FREELANCER_CLIENT_ID'),
        'client_secret' => env('FREELANCER_CLIENT_SECRET'),
        'redirect_uri' => env('FREELANCER_REDIRECT_URI'),
        'auth_url' => env(
            'FREELANCER_AUTH_URL',
            'https://accounts.freelancer.com/oauth/authorize'
        ),
        'token_url' => env(
            'FREELANCER_TOKEN_URL',
            'https://accounts.freelancer.com/oauth/token'
        ),
        'api_url' => env(
            'FREELANCER_API_URL',
            'https://www.freelancer.com/api'
        ),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model'   => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
    ],

];
