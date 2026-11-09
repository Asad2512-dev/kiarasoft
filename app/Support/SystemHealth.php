<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

/** Operational diagnostics used by the System page and `php artisan kirasoft:health`. */
class SystemHealth
{
    public function checks(): array
    {
        $checks = [];
        $add = function (string $name, bool $ok, string $detail, bool $critical = true) use (&$checks) {
            $checks[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail, 'level' => $ok ? 'ok' : ($critical ? 'error' : 'warning')];
        };

        $add('PHP version', version_compare(PHP_VERSION, '8.2.0', '>='), 'PHP '.PHP_VERSION);
        foreach (['pdo_mysql', 'mbstring', 'openssl', 'fileinfo'] as $ext) {
            $add("PHP extension: {$ext}", extension_loaded($ext), extension_loaded($ext) ? 'loaded' : 'missing');
        }
        try {
            $version = DB::selectOne('SELECT VERSION() as v')->v;
            $add('Database connection', true, config('database.default').' '.$version.' / '.DB::getDatabaseName());
            $add('MySQL 8.x', str_starts_with($version, '8.') || str_contains(strtolower($version), 'mariadb'), $version, false);
            $add('Migrations', Schema::hasTable('sales') && Schema::hasTable('inventory_movements'), Schema::hasTable('sales') ? 'core tables present' : 'run php artisan migrate');
            $engines = collect(DB::select('SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()'))->filter(fn ($t) => $t->ENGINE && $t->ENGINE !== 'InnoDB');
            $add('InnoDB tables', $engines->isEmpty(), $engines->isEmpty() ? 'all tables use InnoDB' : 'non-InnoDB: '.$engines->pluck('TABLE_NAME')->implode(', '));
        } catch (\Throwable $e) {
            $add('Database connection', false, 'Cannot connect: check DB_* values in .env');
        }
        $add('APP_KEY set', ! empty(config('app.key')), config('app.key') ? 'configured' : 'run php artisan key:generate');
        $add('Debug mode off in production', ! (app()->isProduction() && config('app.debug')), app()->environment().' / debug '.(config('app.debug') ? 'on' : 'off'), app()->isProduction());
        $add('Storage writable', is_writable(storage_path('app')) && is_writable(storage_path('logs')), storage_path());
        $add('Public storage link', is_link(public_path('storage')) || is_dir(public_path('storage')), 'needed for logos/product images (php artisan storage:link)', false);
        $add('Built frontend assets', is_file(public_path('build/manifest.json')), is_file(public_path('build/manifest.json')) ? 'public/build present' : 'run npm run build', false);
        $add('Secure cookies (HTTPS)', ! app()->isProduction() || config('session.secure'), 'SESSION_SECURE_COOKIE='.(config('session.secure') ? 'true' : 'false'), false);
        $dump = new Process([env('MYSQLDUMP_PATH', 'mysqldump'), '--version']);
        try {
            $dump->run();
            $add('mysqldump available (backups)', $dump->isSuccessful(), trim($dump->getOutput()) ?: 'not found - set MYSQLDUMP_PATH', false);
        } catch (\Throwable) {
            $add('mysqldump available (backups)', false, 'not found - set MYSQLDUMP_PATH', false);
        }

        return $checks;
    }
}
