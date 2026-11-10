<?php

namespace App\Services;

use App\Support\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Sales reporting. Metric definitions:
 *  - Gross sales   = Σ sales.subtotal            (before discount & tax) for invoices dated in range
 *  - Discounts     = Σ sales.discount_amount
 *  - Tax           = Σ sales.tax_amount
 *  - Invoiced      = Σ sales.total               (= gross - discounts + tax)
 *  - Returns       = Σ sale_returns.refund_amount for returns processed in range
 *  - Net sales     = Invoiced - Returns
 *  - Invoices      = COUNT(sales)
 *  - Avg. invoice  = Invoiced / Invoices
 * Returns are attributed to the date they were processed (cash basis).
 */
class ReportService
{
    /** @param int[] $branchIds */
    public function summary(int $shopId, array $branchIds, CarbonImmutable $from, CarbonImmutable $to, ?int $cashierId = null): array
    {
        [$start, $end] = [$from->startOfDay(), $to->endOfDay()];
        $s = $this->sales($shopId, $branchIds, $start, $end, $cashierId)
            ->selectRaw('COUNT(*) as invoices, COALESCE(SUM(subtotal),0) as gross, COALESCE(SUM(discount_amount),0) as discounts, COALESCE(SUM(tax_amount),0) as tax, COALESCE(SUM(total),0) as invoiced')
            ->first();
        $returns = $this->returns($shopId, $branchIds, $start, $end, $cashierId)->sum('refund_amount');

        $invoiced = Money::toMinor($s->invoiced);
        $ret = Money::toMinor((string) $returns);

        return [
            'invoices' => (int) $s->invoices,
            'gross' => Money::fromMinor(Money::toMinor($s->gross)),
            'discounts' => Money::fromMinor(Money::toMinor($s->discounts)),
            'tax' => Money::fromMinor(Money::toMinor($s->tax)),
            'invoiced' => Money::fromMinor($invoiced),
            'returns' => Money::fromMinor($ret),
            'net' => Money::fromMinor($invoiced - $ret),
            'average' => Money::fromMinor($s->invoices ? Money::divRound($invoiced, (int) $s->invoices) : 0),
        ];
    }

    /** Daily series (labels + invoiced + net + invoices) for charts and the daily table. */
    public function daily(int $shopId, array $branchIds, CarbonImmutable $from, CarbonImmutable $to, ?int $cashierId = null): array
    {
        [$start, $end] = [$from->startOfDay(), $to->endOfDay()];
        $sales = $this->sales($shopId, $branchIds, $start, $end, $cashierId)
            ->selectRaw('DATE(sold_at) as d, COUNT(*) as invoices, SUM(subtotal) as gross, SUM(discount_amount) as discounts, SUM(total) as invoiced')
            ->groupBy('d')->get()->keyBy('d');
        $returns = $this->returns($shopId, $branchIds, $start, $end, $cashierId)
            ->selectRaw('DATE(sale_returns.created_at) as d, SUM(refund_amount) as refunds')->groupBy('d')->pluck('refunds', 'd');

        $rows = [];
        foreach (CarbonPeriod::create($start, '1 day', $end) as $day) {
            $d = $day->format('Y-m-d');
            $inv = Money::toMinor($sales[$d]->invoiced ?? '0');
            $ret = Money::toMinor($returns[$d] ?? '0');
            $rows[] = [
                'date' => $d, 'label' => $day->format('d M'),
                'invoices' => (int) ($sales[$d]->invoices ?? 0),
                'gross' => Money::fromMinor(Money::toMinor($sales[$d]->gross ?? '0')),
                'discounts' => Money::fromMinor(Money::toMinor($sales[$d]->discounts ?? '0')),
                'invoiced' => Money::fromMinor($inv), 'returns' => Money::fromMinor($ret), 'net' => Money::fromMinor($inv - $ret),
            ];
        }

        return $rows;
    }

    public function byBranch(int $shopId, array $branchIds, CarbonImmutable $from, CarbonImmutable $to): array
    {
        [$start, $end] = [$from->startOfDay(), $to->endOfDay()];
        $sales = $this->sales($shopId, $branchIds, $start, $end)
            ->selectRaw('branch_id, COUNT(*) as invoices, SUM(total) as invoiced')->groupBy('branch_id')->get()->keyBy('branch_id');
        $returns = $this->returns($shopId, $branchIds, $start, $end)
            ->selectRaw('sale_returns.branch_id, SUM(refund_amount) as refunds')->groupBy('sale_returns.branch_id')->pluck('refunds', 'branch_id');

        return DB::table('branches')->where('shop_id', $shopId)->whereIn('id', $branchIds)->orderBy('name')->get(['id', 'name', 'code'])
            ->map(function ($b) use ($sales, $returns) {
                $inv = Money::toMinor($sales[$b->id]->invoiced ?? '0');
                $ret = Money::toMinor($returns[$b->id] ?? '0');

                return ['id' => $b->id, 'name' => $b->name, 'code' => $b->code, 'invoices' => (int) ($sales[$b->id]->invoices ?? 0),
                    'invoiced' => Money::fromMinor($inv), 'returns' => Money::fromMinor($ret), 'net' => Money::fromMinor($inv - $ret)];
            })->all();
    }

    public function byCashier(int $shopId, array $branchIds, CarbonImmutable $from, CarbonImmutable $to): array
    {
        [$start, $end] = [$from->startOfDay(), $to->endOfDay()];
        $returns = $this->returns($shopId, $branchIds, $start, $end)
            ->selectRaw('sales.cashier_id, SUM(refund_amount) as refunds')->groupBy('sales.cashier_id')->pluck('refunds', 'cashier_id');

        return $this->sales($shopId, $branchIds, $start, $end)
            ->join('users', 'users.id', '=', 'sales.cashier_id')
            ->selectRaw('sales.cashier_id, users.name, COUNT(*) as invoices, SUM(sales.total) as invoiced, SUM(sales.discount_amount) as discounts')
            ->groupBy('sales.cashier_id', 'users.name')->orderByDesc('invoiced')->get()
            ->map(function ($r) use ($returns) {
                $inv = Money::toMinor($r->invoiced);
                $ret = Money::toMinor($returns[$r->cashier_id] ?? '0');

                return ['id' => $r->cashier_id, 'name' => $r->name, 'invoices' => (int) $r->invoices, 'discounts' => Money::fromMinor(Money::toMinor($r->discounts)),
                    'invoiced' => Money::fromMinor($inv), 'returns' => Money::fromMinor($ret), 'net' => Money::fromMinor($inv - $ret)];
            })->all();
    }

    public function topProducts(int $shopId, array $branchIds, CarbonImmutable $from, CarbonImmutable $to, int $limit = 10): array
    {
        return DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.shop_id', $shopId)->whereIn('sales.branch_id', $branchIds ?: [0])
            ->whereBetween('sales.sold_at', [$from->startOfDay(), $to->endOfDay()])
            ->selectRaw('sale_items.product_id, sale_items.product_name, sale_items.unit, SUM(sale_items.quantity - sale_items.returned_quantity) as qty, SUM(sale_items.line_total) as revenue')
            ->groupBy('sale_items.product_id', 'sale_items.product_name', 'sale_items.unit')
            ->orderByDesc('revenue')->limit($limit)->get()->map(fn ($r) => (array) $r)->all();
    }

    private function sales(int $shopId, array $branchIds, $start, $end, ?int $cashierId = null)
    {
        return DB::table('sales')->where('sales.shop_id', $shopId)->whereIn('sales.branch_id', $branchIds ?: [0])
            ->whereBetween('sales.sold_at', [$start, $end])
            ->when($cashierId, fn ($q) => $q->where('sales.cashier_id', $cashierId));
    }

    private function returns(int $shopId, array $branchIds, $start, $end, ?int $cashierId = null)
    {
        return DB::table('sale_returns')->join('sales', 'sales.id', '=', 'sale_returns.sale_id')
            ->where('sale_returns.shop_id', $shopId)->whereIn('sale_returns.branch_id', $branchIds ?: [0])
            ->whereBetween('sale_returns.created_at', [$start, $end])
            ->when($cashierId, fn ($q) => $q->where('sales.cashier_id', $cashierId));
    }
}
