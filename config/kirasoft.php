<?php

return [
    // Separate database used to verify backups can be restored. Never the live database.
    'restore_test_database' => env('KIRASOFT_RESTORE_TEST_DB', 'kirasoft_restore_test'),
    // Password for seeded demo accounts. If empty, the seeder generates one and prints it.
    'demo_password' => env('KIRASOFT_DEMO_PASSWORD'),
    'max_upload_kb' => 2048,
];
