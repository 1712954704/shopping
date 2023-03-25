<?php
//// 加载db配置
// $db = \Common::fromFile(CONFIG_PATH . 'db.ini');
$db = \Common::fromFile(CONFIG_PATH . 'mail.ini');


// var_dump($db['MAIL_MAILER']);exit();
//
// var_dump(CONFIG_PATH. 'mail.ini');exit();
// var_dump($db);exit();

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send any email
    | messages sent by your application. Alternative mailers may be setup
    | and used as needed; however, this mailer will be used by default.
    |
    */

    'default' => $db['MAIL_MAILER'],

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers to be used while
    | sending an e-mail. You will specify which one you are using for your
    | mailers below. You are free to add additional mailers as required.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses",
    |            "postmark", "log", "array", "failover"
    |
    */

    'mailers' => [
        'smtp'     => [
            'transport'    => 'smtp',
            'host'         => $db['MAIL_HOST'],
            'port'         => $db['MAIL_PORT'],
            'encryption'   => $db['MAIL_ENCRYPTION'],
            'username'     => $db['MAIL_USERNAME'],
            'password'     => $db['MAIL_PASSWORD'],
            'timeout'      => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN_QQ'),
        ],
        'smtp_bio' => [
            'transport'    => 'smtp',
            'host'         => $db['MAIL_HOST_BIO'],
            'port'         => $db['MAIL_PORT_BIO'],
            'encryption'   => $db['MAIL_ENCRYPTION_BIO'],
            'username'     => $db['MAIL_USERNAME_BIO'],
            'password'     => $db['MAIL_PASSWORD_BIO'],
            'timeout'      => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN_QQ'),
        ],

        'smtp_qq' => [
            'transport'    => 'smtp',
            'host'         => env('MAIL_HOST_QQ', 'smtp.163.com'),
            'port'         => env('MAIL_PORT_QQ', 25),
            'encryption'   => env('MAIL_ENCRYPTION_QQ', 'tls'),
            'username'     => env('MAIL_USERNAME_QQ', 'guomengtao@163.com'),
            'password'     => env('MAIL_PASSWORD_QQ', 'QHLSDDGEQFPFTUUA'),
            'timeout'      => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN_QQ'),
        ],



        'ses' => [
            'transport' => 'ses',
        ],

        'mailgun' => [
            'transport' => 'mailgun',
        ],

        'postmark' => [
            'transport' => 'postmark',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path'      => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel'   => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers'   => [
                'smtp',
                'log',
            ],
        ],


    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all e-mails sent by your application to be sent from
    | the same address. Here, you may specify a name and address that is
    | used globally for all e-mails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'inform@biolink.com'),
        'name'    => env('MAIL_FROM_NAME', 'BIO'),
    ],


    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    |
    | If you are using Markdown based email rendering, you may configure your
    | theme and component paths here, allowing you to customize the design
    | of the emails. Or, you may simply stick with the Laravel defaults!
    |
    */

    'markdown' => [
        'theme' => 'default',

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],




];
