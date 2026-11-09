<?php
// Separate OS process used by ConcurrencyTest: waits for a shared start time, then checks out.
// argv: userId branchId productId qty startAtMicrotime
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
[, $userId, $branchId, $productId, $qty, $startAt] = $argv;
$user = App\Models\User::find($userId);
$branch = App\Models\Branch::find($branchId);
while (microtime(true) < (float) $startAt) { usleep(500); }
try {
    $sale = app(App\Services\CheckoutService::class)->checkout($user, $branch, [['product_id' => (int) $productId, 'quantity' => $qty]],
        ['idempotency_key' => (string) Illuminate\Support\Str::uuid(), 'amount_tendered' => '100000']);
    echo "OK {$sale->invoice_no}";
} catch (App\Exceptions\BusinessRuleException $e) {
    echo 'REJECTED '.$e->getMessage();
}
