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

    /*
    |------------------------------------------------------------------------|
    | Paddle Connect
    |------------------------------------------------------------------------|
    */
    'paddle' => [
        'client_side_token' => env('PADDLE_CLIENT_SIDE_TOKEN'),
        // Price IDs        
        'startup_monthly_price_id' => env('PADDLE_STARTUP_MONTHLY_PRICE_ID'),
        'startup_annual_price_id' => env('PADDLE_STARTUP_ANNUAL_PRICE_ID'),
        'enterprise_annual_price_id' => env('PADDLE_ENTERPRISE_ANNUAL_PRICE_ID'),
        // Price amounts
        'startup_monthly_amount' => env('STARTUP_MONTHLY_SUBSCRIPTION_AMOUNT'),
        'startup_annual_amount' => env('STARTUP_ANNUAL_SUBSCRIPTION_AMOUNT'),
        'enterprise_annual_amount' => env('ENTERPRISE_ANNUAL_SUBSCRIPTION_AMOUNT'), 
        // Discounts
        'startup_monthly_discount' => env('STARTUP_MONTHLY_DISCOUNT'),
        'startup_annual_discount' => env('STARTUP_ANNUAL_DISCOUNT'),
        'enterprise_annual_discount' => env('ENTERPRISE_ANNUAL_DISCOUNT'),
    ],
];
