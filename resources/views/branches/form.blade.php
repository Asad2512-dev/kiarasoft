@extends('layouts.app', ['title' => $branch->exists ? 'Edit branch' : 'Add branch'])
@section('content')
<div class="page-head"><div><a href="{{ $branch->exists ? route('branches.show', $branch) : route('branches.index') }}" class="small text-muted"><i class="bi bi-arrow-left"></i> Back</a><h1 class="mt-1">{{ $branch->exists ? 'Edit '.$branch->name : 'Add a branch' }}</h1></div></div>
<form method="POST" action="{{ $branch->exists ? route('branches.update', $branch) : route('branches.store') }}" class="card">
    @csrf @if($branch->exists) @method('PUT') @endif
    <div class="card-body">
        <div class="form-section row g-3">
            <div class="col-lg-4"><div class="section-title">Branch details</div><div class="section-help">The code appears in invoice numbers (e.g. INV-<strong>G11</strong>-20261109-0001).</div></div>
            <div class="col-lg-8"><div class="row g-3">
                <div class="col-sm-8"><label class="form-label" for="name">Branch name</label><input id="name" name="name" value="{{ old('name', $branch->name) }}" class="form-control @error('name') is-invalid @enderror" required maxlength="120" placeholder="e.g. G-11 Markaz">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-sm-4"><label class="form-label" for="code">Code</label><input id="code" name="code" value="{{ old('code', $branch->code) }}" class="form-control mono text-uppercase @error('code') is-invalid @enderror" required maxlength="10" placeholder="G11">@error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-sm-8"><label class="form-label" for="address">Address</label><input id="address" name="address" value="{{ old('address', $branch->address) }}" class="form-control" maxlength="255"></div>
                <div class="col-sm-4"><label class="form-label" for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone', $branch->phone) }}" class="form-control" maxlength="30" inputmode="tel"></div>
                <div class="col-12"><div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $branch->is_active))><label class="form-check-label" for="is_active">Branch is active</label></div></div>
            </div></div>
        </div>
        <div class="form-section row g-3">
            <div class="col-lg-4"><div class="section-title">People</div><div class="section-help">Assign a manager and the staff who may work at this branch.</div></div>
            <div class="col-lg-8"><div class="row g-3">
                <div class="col-sm-6"><label class="form-label" for="manager_id">Branch manager</label><select id="manager_id" name="manager_id" class="form-select"><option value="">Unassigned</option>@foreach($managers as $m)<option value="{{ $m->id }}" @selected(old('manager_id', $branch->manager_id) == $m->id)>{{ $m->name }}</option>@endforeach</select>
                    @if($managers->isEmpty())<div class="form-text">Create an employee with the Branch Manager role first.</div>@endif</div>
                <div class="col-12"><label class="form-label">Assigned staff</label>
                    @php $assigned = collect(old('staff', $branch->exists ? $branch->users->pluck('id')->all() : []))->map(fn($i) => (int)$i); @endphp
                    @if($staff->isEmpty())<div class="text-muted small">No employees yet. <a href="{{ route('employees.create') }}">Add an employee</a>.</div>@endif
                    <div class="row g-2">@foreach($staff as $s)
                        <div class="col-sm-6"><label class="d-flex align-items-center gap-2 border rounded-3 p-2 px-3" style="cursor:pointer">
                            <input type="checkbox" class="form-check-input m-0" name="staff[]" value="{{ $s->id }}" @checked($assigned->contains($s->id))>
                            <span class="avatar avatar-sm">{{ $s->initials() }}</span><span class="min-w-0"><span class="d-block fw-550 small text-truncate">{{ $s->name }}</span><span class="d-block small text-muted">{{ $s->role->label() }}</span></span>
                        </label></div>
                    @endforeach</div>
                </div>
            </div></div>
        </div>
        <div class="sticky-actions"><a href="{{ route('branches.index') }}" class="btn btn-white">Cancel</a><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save branch</button></div>
    </div>
</form>
@endsection
