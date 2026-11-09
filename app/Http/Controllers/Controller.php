<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;

    /** Branch IDs the current user may see, optionally narrowed by a ?branch= filter (validated). */
    protected function branchScope(Request $request, string $param = 'branch'): array
    {
        $ids = $request->user()->accessibleBranchIds();
        $selected = $request->integer($param);
        if ($selected) {
            abort_unless(in_array($selected, $ids, true), 403, 'You do not have access to this branch.');

            return [$selected];
        }

        return $ids;
    }

    /** @return array{0:CarbonImmutable,1:CarbonImmutable} */
    protected function dateRange(Request $request, string $default = 'month'): array
    {
        $today = CarbonImmutable::today();
        $preset = $request->input('period', $default);
        [$from, $to] = match ($preset) {
            'today' => [$today, $today],
            'yesterday' => [$today->subDay(), $today->subDay()],
            'week' => [$today->startOfWeek(), $today],
            'last7' => [$today->subDays(6), $today],
            'last30' => [$today->subDays(29), $today],
            'year' => [$today->startOfYear(), $today],
            default => [$today->startOfMonth(), $today],
        };
        try {
            if ($request->filled('from')) {
                $from = CarbonImmutable::parse($request->input('from'));
            }
            if ($request->filled('to')) {
                $to = CarbonImmutable::parse($request->input('to'));
            }
        } catch (\Throwable) {
            abort(422, 'Invalid date.');
        }
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }
        if ($from->diffInDays($to) > 366) {
            $from = $to->subDays(366);
        }

        return [$from, $to];
    }
}
