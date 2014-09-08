<?php

return [
    'client_cors_domain' => 'http://braindump-client.local',
    'migration_table_script' => __DIR__ . '/../migrations/migration-table-sqlite.sql',
    'drop_tables_script' => __DIR__ . '/../migrations/migration-drop-tables-sqlite.sql',
    'databases_setup_scripts' => [
        '0.1' => __DIR__ . '/../migrations/braindump-0.1-sqlite.sql',
    ]
];
