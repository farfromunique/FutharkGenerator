<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'futhark',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        /**************************************************************************
         * General App config.
         **************************************************************************/
        'APP_VERSION' => 'v2.1beta',
        'APP_TITLE' => 'Futhark Power Generator ',

        'powersDB' => [
            'location' => __DIR__ . '/../sourceData/firebase.english.json'
        ],

        'AWS' => [
            'region' => 'eu-central-1',
            'dynamoDB' => [
                'tableName' => 'futhark_save_data'
            ]
        ],
    ],
];
