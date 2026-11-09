@if(session('success'))<span hidden data-flash="{{ session('success') }}" data-type="success"></span>@endif
@if(session('error'))
    <div class="alert alert-danger d-flex gap-2 align-items-start" role="alert"><i class="bi bi-exclamation-octagon-fill mt-1"></i><div>{{ session('error') }}</div></div>
@endif
@if(session('credentials'))
    <div class="alert alert-warning d-flex gap-3 align-items-start" role="alert">
        <i class="bi bi-key-fill fs-4"></i>
        <div>
            <strong>Temporary credentials (shown only once)</strong>
            <div class="mt-1">E-mail: <code>{{ session('credentials')['email'] }}</code> &nbsp; Password: <code class="user-select-all">{{ session('credentials')['password'] }}</code></div>
            <div class="small mt-1">Share these securely. The user should change the password from <em>My profile</em> after first sign-in.</div>
        </div>
    </div>
@endif
@if($errors->any() && ! request()->routeIs('login'))
    <div class="alert alert-danger" role="alert">
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-circle me-1"></i>Please fix the following:</div>
        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
