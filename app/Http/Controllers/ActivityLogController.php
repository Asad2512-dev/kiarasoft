<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branchIds = $user->accessibleBranchIds();
        [$from, $to] = $this->dateRange($request, 'last30');
        $logs = ActivityLog::with(['user', 'branch'])->where('shop_id', $user->shop_id)
            ->when(! $user->isShopAdmin(), fn ($q) => $q->where(fn ($w) => $w->whereIn('branch_id', $branchIds ?: [0])->orWhere('user_id', $user->id)))
            ->when($request->integer('branch'), function ($q, $b) use ($branchIds) {
                abort_unless(in_array($b, $branchIds, true), 403);
                $q->where('branch_id', $b);
            })
            ->when($request->action, fn ($q, $a) => $q->where('action', 'like', preg_replace('/[^a-z_.]/', '', $a).'%'))
            ->when($request->q, fn ($q, $s) => $q->where('description', 'like', "%{$s}%"))
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->latest('id')->paginate(30)->withQueryString();

        return view('activity.index', ['logs' => $logs, 'branches' => $user->accessibleBranches()->get(), 'from' => $from, 'to' => $to, 'platform' => false]);
    }

    public function platform(Request $request)
    {
        [$from, $to] = $this->dateRange($request, 'last30');
        $logs = ActivityLog::with(['user', 'shop', 'branch'])
            ->when($request->integer('shop'), fn ($q, $s) => $q->where('shop_id', $s))
            ->when($request->action, fn ($q, $a) => $q->where('action', 'like', preg_replace('/[^a-z_.]/', '', $a).'%'))
            ->when($request->q, fn ($q, $s) => $q->where('description', 'like', "%{$s}%"))
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->latest('id')->paginate(30)->withQueryString();

        return view('activity.index', ['logs' => $logs, 'branches' => collect(), 'from' => $from, 'to' => $to, 'platform' => true,
            'shops' => \App\Models\Shop::orderBy('name')->get(['id', 'name'])]);
    }
}
