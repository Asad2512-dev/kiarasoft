@extends('layouts.guest', ['title' => 'Register your shop'])
@section('content')
    <a href="{{ route('login') }}" class="small text-muted d-inline-block mb-3"><i class="bi bi-arrow-left"></i> Back to sign in</a>
    <h1 class="h3 mb-1">Register your shop</h1>
    <p class="text-muted mb-4">Send a request and the KiraSoft team will set up your account.</p>
    @if(session('error'))<div class="alert alert-danger small">{{ session('error') }}</div>@endif
    <form method="POST" action="{{ route('signup.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-12"><label class="form-label" for="shop_name">Shop name</label><input id="shop_name" name="shop_name" value="{{ old('shop_name') }}" class="form-control @error('shop_name') is-invalid @enderror" required>@error('shop_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-6"><label class="form-label" for="owner_name">Owner name</label><input id="owner_name" name="owner_name" value="{{ old('owner_name') }}" class="form-control @error('owner_name') is-invalid @enderror" required>@error('owner_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-6"><label class="form-label" for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="03xx-xxxxxxx" required inputmode="tel">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-7"><label class="form-label" for="email">E-mail</label><input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-5"><label class="form-label" for="city">City</label><input id="city" name="city" value="{{ old('city') }}" class="form-control"></div>
            <div class="col-12"><label class="form-label" for="message">Anything we should know? <span class="text-muted fw-normal">(optional)</span></label><textarea id="message" name="message" rows="2" class="form-control">{{ old('message') }}</textarea></div>
        </div>
        <button class="btn btn-primary btn-lg w-100 mt-4">Submit request</button>
    </form>
@endsection
