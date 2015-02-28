<?php

return [
    'client_cors_domain' => 'http://braindump-client.local',
    'migration_table_script' => __DIR__ . '/../migrations/migration-table-sqlite.sql',
    'drop_tables_script' => __DIR__ . '/../migrations/migration-drop-tables-sqlite.sql',
    'database_config' => [ 'connection_string' => 'sqlite:' . __DIR__ . '/../data/braindump.sqlite3' ],
    'databases_setup_scripts' => [
        '1' => __DIR__ . '/../migrations/braindump-1-sqlite.sql',
        '2' => __DIR__ . '/../migrations/braindump-2-sqlite.sql',
        '3' => __DIR__ . '/../migrations/braindump-3-sqlite.sql',
        '4' => __DIR__ . '/../migrations/braindump-4-sqlite.sql',
    ]
];
