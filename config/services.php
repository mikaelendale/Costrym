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
    /*
    |------------------------------------------------------------------------|
    | GitHub Auth
    |------------------------------------------------------------------------|
    */
    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI', 'http://localhost:8000/auth/github/callback'),
    ],
    /*
    |------------------------------------------------------------------------|
    | Google Auth
    |------------------------------------------------------------------------|
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', 'http://localhost:8000/auth/google/callback'),
        'scopes' => [
            'openid',
            'profile',
            'email',
        ],
        'access_type' => 'offline', // This is crucial for refresh token
        'approval_prompt' => 'force', // Force re-approval to get refresh token
    ],

    /*
    |------------------------------------------------------------------------|
    | Pipedream Connect
    |------------------------------------------------------------------------|
    */
    'pipedream' => [
        'client_id' => env('PIPEDREAM_CLIENT_ID'),
        'client_secret' => env('PIPEDREAM_CLIENT_SECRET'),
        'project_id' => env('PIPEDREAM_PROJECT_ID'),
        'project_environment' => env('PIPEDREAM_PROJECT_ENVIRONMENT', 'development'),
        'base_url' => env('PIPEDREAM_BASE_URL', 'https://api.pipedream.com/v1'),
    ],

    'firecrawl' => [
        'key' => env('FIRECRAWL_KEY'),
    ],

];
