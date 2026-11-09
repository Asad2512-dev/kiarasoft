<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#064e3b">
    <title>{{ isset($title) ? $title.' · ' : '' }}KiraSoft</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%23059669'/><text x='16' y='22' font-size='17' text-anchor='middle' fill='white' font-family='Arial' font-weight='bold'>K</text></svg>">
    @vite('resources/js/app.js')
    @stack('head')
</head>
<body class="{{ $bodyClass ?? '' }}">
<a href="#main" class="skip-link btn btn-primary btn-sm">Skip to content</a>
<div class="ks-shell">
    @include('partials.sidebar')
    <div class="ks-backdrop"></div>
    <div class="ks-main">
        <header class="ks-topbar">
            <button class="icon-btn" type="button" data-sidebar-toggle aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
            <h1 class="page-title">{{ $title ?? 'KiraSoft' }}</h1>
            <div class="ms-auto d-flex align-items-center gap-1">
                @can('pos.use')
                    @unless(request()->routeIs('pos.*'))
                        <a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm d-none d-sm-inline-flex"><i class="bi bi-cart3"></i> New sale</a>
                    @endunless
                @endcan
                @can('alerts.view')
                    <div class="dropdown">
                        <button class="icon-btn" data-bs-toggle="dropdown" aria-label="Stock alerts ({{ $alertCounts['total'] }})" aria-expanded="false">
                            <i class="bi bi-bell"></i>
                            @if($alertCounts['total'])<span class="dot">{{ $alertCounts['total'] > 99 ? '99+' : $alertCounts['total'] }}</span>@endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0" style="width: min(340px, 92vw)">
                            <div class="px-3 py-3 border-bottom d-flex justify-content-between align-items-center">
                                <strong>Stock alerts</strong>
                                <span class="small text-muted">{{ $alertCounts['out'] }} out · {{ $alertCounts['low'] }} low</span>
                            </div>
                            <div class="p-2">
                                <a class="dropdown-item rounded d-flex gap-2 align-items-center py-2" href="{{ route('alerts.index', ['status' => 'out']) }}">
                                    <span class="stat-icon tint-red rounded-3 d-grid" style="width:34px;height:34px;place-items:center"><i class="bi bi-x-octagon"></i></span>
                                    <span><span class="fw-550 d-block">{{ $alertCounts['out'] }} out of stock</span><span class="small text-muted">Cannot be sold until restocked</span></span>
                                </a>
                                <a class="dropdown-item rounded d-flex gap-2 align-items-center py-2" href="{{ route('alerts.index', ['status' => 'low']) }}">
                                    <span class="stat-icon tint-amber rounded-3 d-grid" style="width:34px;height:34px;place-items:center"><i class="bi bi-exclamation-triangle"></i></span>
                                    <span><span class="fw-550 d-block">{{ $alertCounts['low'] }} running low</span><span class="small text-muted">At or below threshold</span></span>
                                </a>
                            </div>
                            <div class="border-top p-2"><a href="{{ route('alerts.index') }}" class="btn btn-sm btn-white w-100">Open notification center</a></div>
                        </div>
                    </div>
                @endcan
                <div class="dropdown">
                    <button class="btn p-1 d-flex align-items-center gap-2 border-0" data-bs-toggle="dropdown" aria-label="Account menu" aria-expanded="false">
                        <span class="avatar">{{ auth()->user()->initials() }}</span>
                        <span class="d-none d-md-block text-start lh-sm">
                            <span class="d-block fw-550 small text-dark">{{ auth()->user()->name }}</span>
                            <span class="d-block text-muted" style="font-size:.72rem">{{ auth()->user()->role->label() }}</span>
                        </span>
                        <i class="bi bi-chevron-down small text-muted d-none d-md-block"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-2 small text-muted d-md-none">{{ auth()->user()->name }} · {{ auth()->user()->role->label() }}</li>
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>My profile</a></li>
                        @can('settings.manage')<li><a class="dropdown-item" href="{{ route('settings.edit') }}"><i class="bi bi-gear me-2"></i>Shop settings</a></li>@endcan
                        <li><hr class="dropdown-divider"></li>
                        <li><form method="POST" action="{{ route('logout') }}" data-no-lock>@csrf<button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button></form></li>
                    </ul>
                </div>
            </div>
        </header>
        <main id="main" class="ks-content" tabindex="-1">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
