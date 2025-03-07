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

    'paystack' => [
        'test_sk' => env('PAYSTACK_TEST_SK'),
        'test_pk' => env('PAYSTACK_TEST_PK'),
        'prod_sk' => env('PAYSTACK_PROD_SK'),
        'prod_pk' => env('PAYSTACK_PROD_PK'),
    ],

    'fincra' => [
        'test_sk' => env('FINCRA_TEST_SK'),
        'test_pk' => env('FINCRA_TEST_PK'),
        'test_wk' => env('FINCRA_TEST_WK'),
        'test_id' => env('FINCRA_TEST_ID'),
        'prod_sk' => env('FINCRA_PROD_SK'),
        'prod_pk' => env('FINCRA_PROD_PK'),
        'prod_wk' => env('FINCRA_PROD_WK'),
        'prod_id' => env('FINCRA_PROD_ID'),
    ],

    'flutterwave' => [
        'test_sk' => env('FLUTTERWAVE_TEST_SK'),
        'test_pk' => env('FLUTTERWAVE_TEST_PK'),
        'test_encryption_key' => env('FLUTTERWAVE_TEST_ENCRYPTION_KEY'),
        'prod_sk' => env('FLUTTERWAVE_PROD_SK'),
        'prod_pk' => env('FLUTTERWAVE_PROD_PK'),
        'prod_encryption_key' => env('FLUTTERWAVE_PROD_ENCRYPTION_KEY'),
    ],

    'app' => [
        'url' => env('APP_URL'),
        'api_key' => env('API_KEY'),
        
    ]

];
