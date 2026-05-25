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

    'external_soap' => [
        'url' => env('EXTERNAL_SOAP_URL', 'http://192.168.1.17:6500/ouaf/XAIApp/xaiserver/CMINDEREG'),
        'username' => env('EXTERNAL_SOAP_USERNAME', 'USER22'),
        'password' => env('EXTERNAL_SOAP_PASSWORD', 'password22'),
        'action' => env('EXTERNAL_SOAP_ACTION', 'http://ouaf.oracle.com/spl/XAIXapp/xaiserver/CMINDEREG'),
        'mock_mode' => env('EXTERNAL_SOAP_MOCK_MODE', false),
    ],

    'business_soap' => [
        'url' => env('BUSINESS_SOAP_URL', 'http://192.168.1.17:6500/ouaf/XAIApp/xaiserver/CMBUSEREG'),
        'username' => env('BUSINESS_SOAP_USERNAME', 'USER22'),
        'password' => env('BUSINESS_SOAP_PASSWORD', 'password22'),
        'action' => env('BUSINESS_SOAP_ACTION', 'http://ouaf.oracle.com/spl/XAIXapp/xaiserver/CMBUSEREG'),
        'mock_mode' => env('BUSINESS_SOAP_MOCK_MODE', false),
    ],

    'sms' => [
        'api_url' => env('SMS_API_URL', 'https://api.etl.co.ls/restapi/sms/1/text/single'),
        'username' => env('SMS_USERNAME', 'LRALesotho'),
        'password' => env('SMS_PASSWORD', 'RSLAdmin@2024'),
        'from_number' => env('SMS_FROM_NUMBER', '22235000'),
        'enabled' => env('SMS_ENABLED', true),
    ],

    'riit_soap' => [
        'url' => env('RIIT_SOAP_URL', 'http://192.168.1.17:6500/ouaf/XAIApp/xaiserver/CMRIITSUBMISSION'),
        'username' => env('RIIT_SOAP_USERNAME', 'USER22'),
        'password' => env('RIIT_SOAP_PASSWORD', 'password22'),
        'mock_mode' => env('RIIT_SOAP_MOCK_MODE', false),
    ],

    'ai_reports' => [
        'enabled' => env('AI_REPORTS_ENABLED', false),
        'base_url' => env('AI_REPORTS_BASE_URL', 'https://api.openai.com/v1'),
        'api_key' => env('AI_REPORTS_API_KEY'),
        'model' => env('AI_REPORTS_MODEL', 'gpt-4.1-mini'),
        'timeout' => env('AI_REPORTS_TIMEOUT', 45),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
