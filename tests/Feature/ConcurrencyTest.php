<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Sale;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * Real concurrency: several independent PHP processes check out the same
 * product at the same instant. Row locks must prevent overselling.
 */
class ConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_checkouts_cannot_oversell(): void
    {
        $f = $this->makeShop(); // tea: 10 packs at branch A
        $workers = 6;            // each wants 3 packs -> only 3 can succeed
        $start = microtime(true) + 1.5;
        $procs = [];
        $env = array_merge(getenv(), ['APP_ENV' => 'testing', 'DB_DATABASE' => 'kirasoft_test', 'DB_CONNECTION' => 'mysql']);
        for ($i = 0; $i < $workers; $i++) {
            $procs[] = proc_open([PHP_BINARY, base_path('tests/Support/checkout_worker.php'), $f->cashier->id, $f->b1->id, $f->tea->id, '3', (string) $start],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes[$i], base_path(), $env);
        }
        $out = [];
        foreach ($procs as $i => $p) {
            $out[] = stream_get_contents($pipes[$i][1]).stream_get_contents($pipes[$i][2]);
            proc_close($p);
        }
        $ok = count(array_filter($out, fn ($o) => str_starts_with($o, 'OK')));
        $rejected = count(array_filter($out, fn ($o) => str_starts_with($o, 'REJECTED')));
        $this->assertSame(3, $ok, implode("\n", $out));
        $this->assertSame(3, $rejected, implode("\n", $out));
        $this->assertSame('1.000', Inventory::where('branch_id', $f->b1->id)->where('product_id', $f->tea->id)->value('quantity'));
        $this->assertSame(3, Sale::count());
        $this->assertSame(3, Sale::distinct()->count('invoice_no'));
        $this->assertSame([], app(InventoryService::class)->reconcile($f->shop->id));
    }
}
