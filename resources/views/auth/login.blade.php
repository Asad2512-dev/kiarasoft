@extends('layouts.guest', ['title' => 'Sign in'])
@section('content')
    <h1 class="h3 mb-1" style="letter-spacing:-.02em">Welcome back</h1>
    <p class="text-muted mb-4">Sign in to your KiraSoft account.</p>

    @if(session('status'))<div class="alert alert-success small" role="status">{{ session('status') }}</div>@endif

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">E-mail address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg @error('email') is-invalid @enderror" required autofocus autocomplete="username" inputmode="email">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3" x-data="{ show: false }">
            <div class="d-flex justify-content-between"><label for="password" class="form-label">Password</label><a href="{{ route('password.request') }}" class="small">Forgot password?</a></div>
            <div class="input-group">
                <input id="password" :type="show ? 'text' : 'password'" type="password" name="password" class="form-control form-control-lg @error('password') is-invalid @enderror" required autocomplete="current-password">
                <button type="button" class="btn btn-white" @click="show = !show" :aria-label="show ? 'Hide password' : 'Show password'" data-no-lock><i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i></button>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
            <label class="form-check-label" for="remember">Keep me signed in on this device</label>
        </div>
        <button class="btn btn-primary btn-lg w-100" type="submit">Sign in <i class="bi bi-arrow-right"></i></button>
    </form>
    <p class="text-center text-muted small mt-4 mb-0">New shop? <a href="{{ route('signup.create') }}">Request a KiraSoft account</a></p>
@endsection
