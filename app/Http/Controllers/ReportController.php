<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ReportService;
use App\Support\Csv;
use App\Support\Money;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports)
    {
        $user = $request->user();
        $branchIds = $this->branchScope($request);
        [$from, $to] = $this->dateRange($request, 'month');
        $cashierId = $this->cashier($request);

        $summary = $reports->summary($user->shop_id, $branchIds, $from, $to, $cashierId);
        $daily = $reports->daily($user->shop_id, $branchIds, $from, $to, $cashierId);
        $byBranch = $reports->byBranch($user->shop_id, $branchIds, $from, $to);
        $byCashier = $reports->byCashier($user->shop_id, $branchIds, $from, $to);
        $top = $reports->topProducts($user->shop_id, $branchIds, $from, $to, 10);
        $branches = $user->accessibleBranches()->get();
        $cashiers = User::where('shop_id', $user->shop_id)->whereIn('role', ['cashier', 'branch_manager', 'shop_admin'])->orderBy('name')->get(['id', 'name']);

        return view('reports.index', compact('summary', 'daily', 'byBranch', 'byCashier', 'top', 'branches', 'cashiers', 'from', 'to'));
    }

    public function export(Request $request, ReportService $reports, ActivityLogger $logger)
    {
        $user = $request->user();
        $branchIds = $this->branchScope($request);
        [$from, $to] = $this->dateRange($request, 'month');
        $cashierId = $this->cashier($request);
        $type = in_array($request->type, ['daily', 'branch', 'cashier', 'products'], true) ? $request->type : 'daily';

        [$header, $rows] = match ($type) {
            'branch' => [['Branch', 'Code', 'Invoices', 'Invoiced (PKR)', 'Returns (PKR)', 'Net (PKR)'],
                array_map(fn ($r) => [$r['name'], $r['code'], $r['invoices'], $r['invoiced'], $r['returns'], $r['net']], $reports->byBranch($user->shop_id, $branchIds, $from, $to))],
            'cashier' => [['Cashier', 'Invoices', 'Discounts (PKR)', 'Invoiced (PKR)', 'Returns (PKR)', 'Net (PKR)'],
                array_map(fn ($r) => [$r['name'], $r['invoices'], $r['discounts'], $r['invoiced'], $r['returns'], $r['net']], $reports->byCashier($user->shop_id, $branchIds, $from, $to))],
            'products' => [['Product', 'Unit', 'Net quantity', 'Revenue (PKR)'],
                array_map(fn ($r) => [$r['product_name'], $r['unit'], Money::qty($r['qty']), $r['revenue']], $reports->topProducts($user->shop_id, $branchIds, $from, $to, 1000))],
            default => [['Date', 'Invoices', 'Gross (PKR)', 'Discounts (PKR)', 'Invoiced (PKR)', 'Returns (PKR)', 'Net (PKR)'],
                array_map(fn ($r) => [$r['date'], $r['invoices'], $r['gross'], $r['discounts'], $r['invoiced'], $r['returns'], $r['net']], $reports->daily($user->shop_id, $branchIds, $from, $to, $cashierId))],
        };
        $logger->log('report.exported', "Exported {$type} report {$from->toDateString()} to {$to->toDateString()}", null, ['branches' => $branchIds]);

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, array_map([Csv::class, 'safe'], $row));
            }
            fclose($out);
        }, "kirasoft-{$type}-report-{$from->format('Ymd')}-{$to->format('Ymd')}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function cashier(Request $request): ?int
    {
        $id = $request->integer('cashier') ?: null;
        if ($id) {
            abort_unless(User::where('shop_id', $request->user()->shop_id)->whereKey($id)->exists(), 403);
        }

        return $id;
    }
}
