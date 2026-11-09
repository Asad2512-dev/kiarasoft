@extends('layouts.app', ['title' => $employee->exists ? 'Edit employee' : 'Add employee'])
@section('content')
<div class="page-head"><div><a href="{{ route('employees.index') }}" class="small text-muted"><i class="bi bi-arrow-left"></i> Employees</a><h1 class="mt-1">{{ $employee->exists ? 'Edit '.$employee->name : 'Add an employee' }}</h1></div></div>
<form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}" class="card" @if($employee->exists) data-confirm="Update {{ $employee->name }}? Role and branch changes take effect on their next page load." data-confirm-title="Update employee?" data-confirm-button="Save changes" @endif>
    @csrf @if($employee->exists) @method('PUT') @endif
    <div class="card-body">
        <div class="form-section row g-3">
            <div class="col-lg-4"><div class="section-title">Personal details</div><div class="section-help">The e-mail is used to sign in.</div></div>
            <div class="col-lg-8"><div class="row g-3">
                <div class="col-12"><label class="form-label" for="name">Full name</label><input id="name" name="name" value="{{ old('name', $employee->name) }}" class="form-control @error('name') is-invalid @enderror" required maxlength="120">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-sm-7"><label class="form-label" for="email">E-mail</label><input id="email" type="email" name="email" value="{{ old('email', $employee->email) }}" class="form-control @error('email') is-invalid @enderror" required autocomplete="off">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-sm-5"><label class="form-label" for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone', $employee->phone) }}" class="form-control" maxlength="30" inputmode="tel"></div>
            </div></div>
        </div>
        <div class="form-section row g-3">
            <div class="col-lg-4"><div class="section-title">Role &amp; access</div><div class="section-help">Managers run branch operations and reports. Cashiers can only sell and view their own invoices.</div></div>
            <div class="col-lg-8"><div class="row g-3">
                <div class="col-12"><label class="form-label">Role</label><div class="row g-2">
                    @foreach(\App\Enums\Role::employeeRoles() as $r)
                        <div class="col-sm-6"><input type="radio" class="btn-check" name="role" id="role_{{ $r->value }}" value="{{ $r->value }}" @checked(old('role', $employee->role?->value) === $r->value)>
                            <label class="btn btn-white w-100 text-start p-3" for="role_{{ $r->value }}"><i class="bi bi-{{ $r->value === 'cashier' ? 'cart3' : 'person-gear' }} me-2"></i><span class="fw-semibold">{{ $r->label() }}</span></label></div>
                    @endforeach
                </div>@error('role')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label">Branches</label>
                    @php $sel = collect(old('branches', $employee->exists ? $employee->branches->pluck('id')->all() : []))->map(fn($i) => (int)$i); @endphp
                    <div class="row g-2">@foreach($branches as $b)
                        <div class="col-sm-6"><label class="d-flex align-items-center gap-2 border rounded-3 p-2 px-3" style="cursor:pointer"><input type="checkbox" class="form-check-input m-0" name="branches[]" value="{{ $b->id }}" @checked($sel->contains($b->id))><span class="fw-550 small">{{ $b->name }}</span>@unless($b->is_active)<span class="badge-soft badge-soft-secondary ms-auto">Inactive</span>@endunless</label></div>
                    @endforeach</div>@error('branches')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
                <div class="col-12"><div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $employee->is_active))><label class="form-check-label" for="is_active">Account active (can sign in)</label></div></div>
            </div></div>
        </div>
        @unless($employee->exists)
        <div class="form-section row g-3">
            <div class="col-lg-4"><div class="section-title">Initial password</div><div class="section-help">At least 8 characters with letters and numbers. Share it privately.</div></div>
            <div class="col-lg-8"><div class="row g-3">
                <div class="col-sm-6"><label class="form-label" for="password">Password</label><input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-sm-6"><label class="form-label" for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password"></div>
            </div></div>
        </div>
        @endunless
        <div class="sticky-actions"><a href="{{ route('employees.index') }}" class="btn btn-white">Cancel</a><button class="btn btn-primary"><i class="bi bi-check-lg"></i> {{ $employee->exists ? 'Save changes' : 'Create employee' }}</button></div>
    </div>
</form>
@endsection
