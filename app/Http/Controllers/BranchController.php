<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branches = Branch::with('manager')->withCount('users')->whereIn('id', $user->accessibleBranchIds())
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%")))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->orderBy('name')->get();
        $today = Sale::whereIn('branch_id', $branches->pluck('id'))->whereDate('sold_at', today())
            ->selectRaw('branch_id, COUNT(*) as c, SUM(total) as t')->groupBy('branch_id')->get()->keyBy('branch_id');

        return view('branches.index', compact('branches', 'today'));
    }

    public function create(Request $request)
    {
        return view('branches.form', ['branch' => new Branch(['is_active' => true]), 'managers' => $this->managers($request), 'staff' => $this->staff($request)]);
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        $this->authorize('create', Branch::class);
        $data = $this->validated($request);
        $branch = Branch::create($data + ['shop_id' => $request->user()->shop_id]);
        $this->syncStaff($branch, $request);
        $logger->log('branch.created', "Branch {$branch->name} created", $branch);

        return redirect()->route('branches.show', $branch)->with('success', 'Branch created.');
    }

    public function show(Request $request, Branch $branch, ReportService $reports)
    {
        $this->authorize('view', $branch);
        $today = CarbonImmutable::today();
        $summary = $reports->summary($branch->shop_id, [$branch->id], $today->startOfMonth(), $today);
        $todaySummary = $reports->summary($branch->shop_id, [$branch->id], $today, $today);
        $trend = $reports->daily($branch->shop_id, [$branch->id], $today->subDays(13), $today);
        $inventory = Inventory::with('product.category')->where('branch_id', $branch->id)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))->orderBy('quantity')->paginate(12, ['*'], 'stock_page');
        $staff = $branch->users()->orderBy('role')->get();
        $sales = Sale::with('cashier')->where('branch_id', $branch->id)->latest('sold_at')->limit(8)->get();
        $default = $branch->shop->config()->default_low_stock_threshold;

        return view('branches.show', compact('branch', 'summary', 'todaySummary', 'trend', 'inventory', 'staff', 'sales', 'default'));
    }

    public function edit(Request $request, Branch $branch)
    {
        $this->authorize('update', $branch);

        return view('branches.form', ['branch' => $branch->load('users'), 'managers' => $this->managers($request), 'staff' => $this->staff($request)]);
    }

    public function update(Request $request, Branch $branch, ActivityLogger $logger)
    {
        $this->authorize('update', $branch);
        $branch->update($this->validated($request, $branch));
        $this->syncStaff($branch, $request);
        $logger->log('branch.updated', "Branch {$branch->name} updated", $branch, ActivityLogger::diff($branch));

        return redirect()->route('branches.show', $branch)->with('success', 'Branch updated.');
    }

    public function toggle(Branch $branch, ActivityLogger $logger)
    {
        $this->authorize('update', $branch);
        $branch->update(['is_active' => ! $branch->is_active]);
        $logger->log('branch.status', "Branch {$branch->name} ".($branch->is_active ? 'activated' : 'deactivated'), $branch);

        return back()->with('success', "Branch {$branch->name} ".($branch->is_active ? 'activated' : 'deactivated').'.');
    }

    private function validated(Request $request, ?Branch $branch = null): array
    {
        $shopId = $request->user()->shop_id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('branches')->where('shop_id', $shopId)->ignore($branch?->id)],
            'code' => ['required', 'alpha_num', 'max:10', Rule::unique('branches')->where('shop_id', $shopId)->ignore($branch?->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'manager_id' => ['nullable', Rule::exists('users', 'id')->where('shop_id', $shopId)->where('role', Role::BranchManager->value)],
            'staff' => ['array'],
            'staff.*' => [Rule::exists('users', 'id')->where('shop_id', $shopId)],
            'is_active' => ['boolean'],
        ]);
        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active');
        unset($data['staff']);

        return $data;
    }

    private function syncStaff(Branch $branch, Request $request): void
    {
        $ids = collect($request->input('staff', []))->map(fn ($id) => (int) $id);
        if ($branch->manager_id) {
            $ids->push($branch->manager_id);
        }
        // shop admins keep implicit access; sync employee assignments only
        $employeeIds = User::where('shop_id', $branch->shop_id)->whereIn('role', ['branch_manager', 'cashier'])->pluck('id');
        $keep = $branch->users()->whereNotIn('users.id', $employeeIds)->pluck('users.id');
        $branch->users()->sync($ids->intersect($employeeIds)->merge($keep)->unique()->values()->all());
    }

    private function managers(Request $request)
    {
        return User::where('shop_id', $request->user()->shop_id)->where('role', Role::BranchManager)->where('is_active', true)->orderBy('name')->get();
    }

    private function staff(Request $request)
    {
        return User::where('shop_id', $request->user()->shop_id)->whereIn('role', ['branch_manager', 'cashier'])->orderBy('name')->get();
    }
}
