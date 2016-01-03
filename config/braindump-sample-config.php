<?php

return [
    'client_cors_domain' => 'http://braindump-client.local',
    'database_config' => [ 'connection_string' => 'sqlite:' . __DIR__ . '/../data/braindump.sqlite3' ],
    'imap_config' => [
        'server' => 'imap.yourserver.com',
        'port' => 993,
        'messageLimit' => 10,
        'user' => 'your_imap_user',
        'password' => 'your_imap_password',
        'sourceFolder' => 'Inbox',
        'processedFolder' => 'Processed'
     ],
     'file_upload_config' => [
        'upload_directory' => __DIR__ . '/../data/uploads',
        'mime_types' => [
            'application/msword'                                                        => 'attachment',
            'application/pdf'                                                           => 'inline',
            'application/vnd.ms-excel'                                                  => 'attachment',
            'application/vnd.ms-powerpointtd'                                           => 'attachment',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'attachment',
            'application/vnd.openxmlformats-officedocument.presentationml.slideshow'    => 'attachment',
            'application/vnd.openxmlformats-officedocument.presentationml.template'     => 'attachment',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'attachment',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'attachment',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.template'   => 'attachment',
            'application/zip'                                                           => 'attachment',
            'image/gif'                                                                 => 'inline',
            'image/jpg'                                                                 => 'inline',
            'image/png'                                                                 => 'inline',
            'text/html'                                                                 => 'attachment',
            'text/plain'                                                                => 'attachment'
        ],
     ],
];
