<?php

namespace App\Console\Commands;

use App\Support\SystemHealth;
use Illuminate\Console\Command;

class HealthCheck extends Command
{
    protected $signature = 'kirasoft:health';

    protected $description = 'Run KiraSoft configuration and connectivity diagnostics';

    public function handle(SystemHealth $health): int
    {
        $checks = $health->checks();
        $this->table(['Check', 'Result', 'Details'], array_map(fn ($c) => [$c['name'], strtoupper($c['level']), $c['detail']], $checks));

        return collect($checks)->contains('level', 'error') ? self::FAILURE : self::SUCCESS;
    }
}
