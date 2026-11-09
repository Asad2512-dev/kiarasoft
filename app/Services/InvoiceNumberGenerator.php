<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\InvoiceSequence;
use Illuminate\Support\Facades\DB;

/**
 * Generates gap-free, unique, human-readable document numbers per branch per day,
 * e.g. INV-G11-20261109-0007. Must run inside the caller's transaction: the
 * sequence row stays locked until commit so concurrent checkouts serialize here.
 */
class InvoiceNumberGenerator
{
    public function next(Branch $branch, string $prefix): string
    {
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('Invoice numbers must be generated inside a transaction.');
        }
        $date = now()->format('Ymd');
        $scope = ($prefix === 'RET' ? 'R' : 'S').$date;

        InvoiceSequence::insertOrIgnore([
            'branch_id' => $branch->id, 'scope' => $scope, 'last_number' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $seq = InvoiceSequence::where('branch_id', $branch->id)->where('scope', $scope)->lockForUpdate()->first();
        $seq->increment('last_number');

        return sprintf('%s-%s-%s-%04d', strtoupper($prefix), strtoupper($branch->code), $date, $seq->last_number);
    }
}
