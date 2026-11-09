<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\ActivityLogger;
use App\Services\BackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function index()
    {
        return view('admin.backups', ['backups' => Backup::with('creator')->latest()->paginate(15)]);
    }

    public function store(Request $request, BackupService $service)
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:255']]);
        try {
            $backup = $service->create($request->user(), $data['notes'] ?? null);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Backup failed. Check that mysqldump is installed and MYSQLDUMP_PATH is correct. See storage/logs for details.');
        }

        return back()->with('success', "Backup {$backup->filename} created.");
    }

    public function download(Backup $backup, BackupService $service, ActivityLogger $logger)
    {
        $logger->log('system.backup_downloaded', "Backup {$backup->filename} downloaded", $backup);

        return response()->download($service->path($backup), $backup->filename, ['Content-Type' => 'application/sql']);
    }

    public function testRestore(Backup $backup, BackupService $service, ActivityLogger $logger)
    {
        $target = config('kirasoft.restore_test_database');
        try {
            $counts = $service->restore($backup, $target);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Restore test failed: '.$e->getMessage());
        }
        $backup->update(['restore_tested_at' => now()]);
        $logger->log('system.backup_restore_tested', "Backup {$backup->filename} restored into {$target}", $backup, $counts);

        return back()->with('success', "Restore test passed into '{$target}': {$counts['shops']} shops, {$counts['products']} products, {$counts['sales']} sales, {$counts['movements']} stock movements.");
    }
}
