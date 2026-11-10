<?php

use App\Exceptions\BusinessRuleException;
use App\Models\Branch;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

// Separate OS process used by ConcurrencyTest: waits for a shared start time, then checks out.
// argv: userId branchId productId qty startAtMicrotime
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
[, $userId, $branchId, $productId, $qty, $startAt] = $argv;
$user = User::find($userId);
$branch = Branch::find($branchId);
while (microtime(true) < (float) $startAt) {
    usleep(500);
}
try {
    $sale = app(CheckoutService::class)->checkout($user, $branch, [['product_id' => (int) $productId, 'quantity' => $qty]],
        ['idempotency_key' => (string) Str::uuid(), 'amount_tendered' => '100000']);
    echo "OK {$sale->invoice_no}";
} catch (BusinessRuleException $e) {
    echo 'REJECTED '.$e->getMessage();
}
