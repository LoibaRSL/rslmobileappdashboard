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

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'wso2' => [
        'client_id' => env('WSO2_CLIENT_ID'),
        'client_secret' => env('WSO2_CLIENT_SECRET'),
        'redirect' => env('WSO2_REDIRECT_URI'),
        'scopes' => ['openid', 'username', 'email', 'profile'], 
        'authorization_url' => env('WSO2_AUTHORIZATION_URL'),
        'token_url' => env('WSO2_TOKEN_URL'),
        'userinfo_url' => env('WSO2_USERINFO_URL'),
        'logout_url' => env('WSO2_LOGOUT_URL'),
        'post_logout_redirect_uri' => env('WSO2_POST_LOGOUT_REDIRECT_URI'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
