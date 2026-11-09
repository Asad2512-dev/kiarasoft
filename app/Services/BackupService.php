<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Database backup via mysqldump. Files are stored on the private "local" disk
 * (storage/app/private/backups) - never under public/. Credentials are passed
 * to the client through a temporary defaults-file, not the command line.
 */
class BackupService
{
    public function __construct(private ActivityLogger $logger) {}

    public function create(?User $user, ?string $notes = null): Backup
    {
        $cfg = config('database.connections.'.config('database.default'));
        $filename = 'kirasoft-'.now()->format('Ymd-His').'-'.substr(bin2hex(random_bytes(3)), 0, 6).'.sql';
        $dir = Storage::disk('local')->path('backups');
        @mkdir($dir, 0700, true);
        $path = $dir.DIRECTORY_SEPARATOR.$filename;

        $this->run([env('MYSQLDUMP_PATH', 'mysqldump'), '--single-transaction', '--routines', '--no-tablespaces', '--skip-comments',
            '--result-file='.$path, $cfg['database']], $cfg);

        if (! is_file($path) || filesize($path) === 0) {
            throw new RuntimeException('Backup file was not created.');
        }
        $backup = Backup::create(['filename' => $filename, 'size_bytes' => filesize($path), 'checksum' => hash_file('sha256', $path),
            'created_by' => $user?->id, 'notes' => $notes]);
        $this->logger->log('system.backup_created', "Database backup {$filename} created", $backup, [], $user);

        return $backup;
    }

    /** Restore a backup into a target database (default: the separate restore-test database). */
    public function restore(Backup $backup, string $targetDatabase): array
    {
        $cfg = config('database.connections.'.config('database.default'));
        $path = $this->path($backup);
        if (hash_file('sha256', $path) !== $backup->checksum) {
            throw new RuntimeException('Checksum mismatch: backup file is corrupt or was modified.');
        }
        if (! preg_match('/^[A-Za-z0-9_]+$/', $targetDatabase)) {
            throw new RuntimeException('Invalid target database name.');
        }
        $client = env('MYSQL_CLIENT_PATH', 'mysql');
        $this->run([$client, '-e', "DROP DATABASE IF EXISTS `{$targetDatabase}`; CREATE DATABASE `{$targetDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"], $cfg, null);
        $this->run([$client, $targetDatabase], $cfg, file_get_contents($path));

        // Verify by counting rows of key tables in the restored copy.
        $out = $this->run([$client, '-N', '-B', $targetDatabase, '-e',
            'SELECT (SELECT COUNT(*) FROM shops),(SELECT COUNT(*) FROM products),(SELECT COUNT(*) FROM sales),(SELECT COUNT(*) FROM inventory_movements)'], $cfg);
        [$shops, $products, $sales, $movements] = array_map('intval', preg_split('/\s+/', trim($out)));

        return compact('shops', 'products', 'sales', 'movements');
    }

    public function path(Backup $backup): string
    {
        $path = Storage::disk('local')->path('backups/'.basename($backup->filename));
        if (! is_file($path)) {
            throw new RuntimeException('Backup file not found on disk.');
        }

        return $path;
    }

    private function run(array $cmd, array $cfg, ?string $input = null): string
    {
        $defaults = tempnam(sys_get_temp_dir(), 'ksdb');
        file_put_contents($defaults, sprintf("[client]\nuser=\"%s\"\npassword=\"%s\"\nhost=\"%s\"\nport=%d\n",
            addslashes($cfg['username']), addslashes((string) $cfg['password']), $cfg['host'], (int) $cfg['port']));
        chmod($defaults, 0600);
        array_splice($cmd, 1, 0, ['--defaults-extra-file='.$defaults]);
        try {
            $process = new Process($cmd, null, null, $input, 300);
            $process->run();
            if (! $process->isSuccessful()) {
                throw new RuntimeException('Database tool failed: '.trim(preg_replace('/password\S*/i', '***', $process->getErrorOutput())));
            }

            return $process->getOutput();
        } finally {
            @unlink($defaults);
        }
    }
}
