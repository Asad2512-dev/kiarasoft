@extends('layouts.guest', ['title' => 'Forgot password'])
@section('content')
    <a href="{{ route('login') }}" class="small text-muted d-inline-block mb-3"><i class="bi bi-arrow-left"></i> Back to sign in</a>
    <h1 class="h3 mb-1">Reset your password</h1>
    <p class="text-muted mb-4">Enter your account e-mail and we'll send you a reset link. Cashiers can also ask their shop admin to reset it.</p>
    @if(session('status'))<div class="alert alert-success small" role="status">{{ session('status') }}</div>@endif
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-4">
            <label for="email" class="form-label">E-mail address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg @error('email') is-invalid @enderror" required autofocus>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button class="btn btn-primary btn-lg w-100">Send reset link</button>
    </form>
@endsection
