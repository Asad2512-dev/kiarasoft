<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $this->authorize('viewAny', User::class);
        $employees = User::with('branches')->where('shop_id', $user->shop_id)
            ->when(! $user->isShopAdmin(), fn ($q) => $q->where(fn ($w) => $w->whereKey($user->id)
                ->orWhereHas('branches', fn ($b) => $b->whereIn('branches.id', $user->accessibleBranchIds()))))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")))
            ->when(in_array($request->role, ['shop_admin', 'branch_manager', 'cashier'], true), fn ($q) => $q->where('role', $request->role))
            ->when($request->integer('branch'), fn ($q, $b) => $q->whereHas('branches', fn ($w) => $w->where('branches.id', $b)))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->orderBy('name')->paginate(15)->withQueryString();
        $branches = $user->accessibleBranches()->get();

        return view('employees.index', compact('employees', 'branches'));
    }

    public function create(Request $request)
    {
        return view('employees.form', ['employee' => new User(['is_active' => true, 'role' => Role::Cashier]), 'branches' => $this->branches($request)]);
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        $this->authorize('create', User::class);
        $data = $this->validated($request);
        $employee = DB::transaction(function () use ($data, $request) {
            $employee = User::create([
                'shop_id' => $request->user()->shop_id, 'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null,
                'role' => $data['role'], 'is_active' => $request->boolean('is_active', true), 'password' => $data['password'],
            ]);
            $employee->branches()->sync($data['branches']);

            return $employee;
        });
        $logger->log('employee.created', "Employee {$employee->name} ({$employee->role->label()}) created", $employee, ['branches' => $data['branches']]);

        return redirect()->route('employees.index')->with('success', "Employee {$employee->name} created.");
    }

    public function edit(Request $request, User $employee)
    {
        $this->authorize('update', $employee);

        return view('employees.form', ['employee' => $employee->load('branches'), 'branches' => $this->branches($request)]);
    }

    public function update(Request $request, User $employee, ActivityLogger $logger)
    {
        $this->authorize('update', $employee);
        $data = $this->validated($request, $employee);
        DB::transaction(function () use ($employee, $data, $request) {
            $employee->update(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null, 'role' => $data['role'], 'is_active' => $request->boolean('is_active')]);
            $employee->branches()->sync($data['branches']);
            // a manager removed from the manager role/branch no longer manages it
            Branch::where('manager_id', $employee->id)->where(fn ($q) => $q->whereNotIn('id', $data['branches'])->orWhereRaw('? <> ?', [$data['role'], 'branch_manager']))->update(['manager_id' => null]);
        });
        $logger->log('employee.updated', "Employee {$employee->name} updated", $employee, ActivityLogger::diff($employee) + ['branches' => $data['branches']]);

        return redirect()->route('employees.index')->with('success', 'Employee updated.');
    }

    public function toggle(User $employee, ActivityLogger $logger)
    {
        $this->authorize('update', $employee);
        $employee->update(['is_active' => ! $employee->is_active]);
        if (! $employee->is_active) {
            DB::table('sessions')->where('user_id', $employee->id)->delete();
        }
        $logger->log('employee.status', "Employee {$employee->name} ".($employee->is_active ? 'activated' : 'deactivated'), $employee);

        return back()->with('success', "{$employee->name} ".($employee->is_active ? 'activated' : 'deactivated').'.');
    }

    public function resetPassword(User $employee, ActivityLogger $logger)
    {
        $this->authorize('update', $employee);
        $password = Str::password(10, symbols: false);
        $employee->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
        DB::table('sessions')->where('user_id', $employee->id)->delete();
        $logger->log('employee.password_reset', "Password reset for {$employee->name}", $employee);

        return back()->with('credentials', ['email' => $employee->email, 'password' => $password])
            ->with('success', "A new temporary password was generated for {$employee->name}. It is shown only once.");
    }

    private function validated(Request $request, ?User $employee = null): array
    {
        $branchIds = $request->user()->accessibleBranchIds();

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($employee?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(array_map(fn ($r) => $r->value, Role::employeeRoles()))],
            'branches' => ['required', 'array', 'min:1'],
            'branches.*' => ['integer', Rule::in($branchIds)],
            'password' => [$employee ? 'nullable' : 'required', 'confirmed', Password::min(8)->letters()->numbers()],
            'is_active' => ['boolean'],
        ], ['branches.required' => 'Assign the employee to at least one branch.']);
    }

    private function branches(Request $request)
    {
        return $request->user()->accessibleBranches()->get();
    }
}
