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
];
