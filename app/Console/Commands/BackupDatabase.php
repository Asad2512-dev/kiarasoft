<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'kirasoft:backup {--restore-test : Also restore it into the restore-test database}';

    protected $description = 'Create a database backup (and optionally verify it by restoring into a separate database)';

    public function handle(BackupService $service): int
    {
        $backup = $service->create(null, 'CLI backup');
        $this->info("Created {$backup->filename} (".number_format($backup->size_bytes / 1024, 1).' KB)');
        if ($this->option('restore-test')) {
            $c = $service->restore($backup, config('kirasoft.restore_test_database'));
            $backup->update(['restore_tested_at' => now()]);
            $this->info("Restore test OK: {$c['shops']} shops, {$c['products']} products, {$c['sales']} sales, {$c['movements']} movements.");
        }

        return self::SUCCESS;
    }
}
