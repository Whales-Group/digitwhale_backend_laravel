<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'support_smtp' => [
            'transport' => env('SUPPORT_MAIL_MAILER', 'smtp'),
            'host' => env('SUPPORT_MAIL_HOST', 'wolverine.hkdns.host'),
            'port' => env('SUPPORT_MAIL_PORT', 587),
            'encryption' => env('SUPPORT_MAIL_ENCRYPTION', 'ssl'),
            'username' => env('SUPPORT_MAIL_USERNAME', 'support@whales.com.ng'),
            'password' => env('SUPPORT_MAIL_PASSWORD', 'Vivian2024.'),
            'from' => [
                'address' => env('SUPPORT_MAIL_FROM_ADDRESS', 'support@whales.com.ng'),
                'name' => env('SUPPORT_MAIL_FROM_NAME', 'DigitWhale Team'),
            ],
        ],

        // 'test_smtp' => [
        //     'transport' => env('TEST_MAIL_MAILER', 'smtp'),
        //     'host' => env('TEST_MAIL_HOST', 'smtp.gmail.com'),
        //     'port' => env('TEST_MAIL_PORT', 587),
        //     'encryption' => env('TEST_MAIL_ENCRYPTION', 'ssl'),
        //     'username' => env('TEST_MAIL_USERNAME'),
        //     'password' => env('TEST_MAIL_PASSWORD'),
        //     'from' => [
        //         'address' => env('TEST_MAIL_FROM_ADDRESS', 'jessedan160@gmail.com'),
        //         'name' => env('TEST_MAIL_FROM_NAME', 'Whales Finance'),
        //     ],
        // ],



        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

];