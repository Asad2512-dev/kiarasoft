@extends('layouts.app', ['title' => 'My profile'])
@section('content')
<div class="page-head"><div class="d-flex align-items-center gap-3"><span class="avatar" style="width:56px;height:56px;font-size:1.1rem">{{ $user->initials() }}</span><div><h1>{{ $user->name }}</h1><p>{{ $user->role->label() }}{{ $user->shop ? ' · '.$user->shop->name : '' }}{{ $user->branches->isNotEmpty() && ! $user->isShopAdmin() ? ' · '.$user->branches->pluck('name')->implode(', ') : '' }}</p></div></div></div>
<div class="row g-3">
    <div class="col-lg-6"><form method="POST" action="{{ route('profile.update') }}" class="card h-100" data-confirm="Your name, e-mail and phone will be updated. If you change your e-mail, use the new one to sign in." data-confirm-title="Update profile?">@csrf @method('PUT')
        <div class="card-header"><h2>Profile details</h2></div>
        <div class="card-body">
            <div class="mb-3"><label class="form-label" for="name">Name</label><input id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control" required></div>
            <div class="mb-3"><label class="form-label" for="email">E-mail</label><input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required></div>
            <div class="mb-3"><label class="form-label" for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control"></div>
            <button class="btn btn-primary">Save profile</button>
        </div></form></div>
    <div class="col-lg-6"><form method="POST" action="{{ route('profile.password') }}" class="card h-100" data-confirm="Your password will change and all your other devices will be signed out." data-confirm-title="Change password?" data-confirm-button="Change password">@csrf @method('PUT')
        <div class="card-header"><h2>Change password</h2></div>
        <div class="card-body">
            <div class="mb-3"><label class="form-label" for="current_password">Current password</label><input id="current_password" type="password" name="current_password" class="form-control" required autocomplete="current-password"></div>
            <div class="mb-3"><label class="form-label" for="password">New password</label><input id="password" type="password" name="password" class="form-control" required autocomplete="new-password"><div class="form-text">8+ characters with letters and numbers.</div></div>
            <div class="mb-3"><label class="form-label" for="password_confirmation">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password"></div>
            <button class="btn btn-primary">Update password</button>
        </div></form></div>
</div>
@endsection
