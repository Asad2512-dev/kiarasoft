@php
    $u = auth()->user();
    $link = function ($route, $icon, $label, $active = null, $badge = null) {
        $is = request()->routeIs($active ?? $route);
        $b = $badge ? '<span class="badge rounded-pill text-bg-danger">'.e($badge).'</span>' : '';
        return '<a href="'.route($route).'" class="nav-link'.($is ? ' active' : '').'"'.($is ? ' aria-current="page"' : '').' title="'.e($label).'"><i class="bi bi-'.$icon.'"></i><span>'.e($label).'</span>'.$b.'</a>';
    };
@endphp
<aside class="ks-sidebar" aria-label="Main navigation">
    <a href="{{ route($u->homeRoute()) }}" class="brand">
        <span class="brand-mark"><i class="bi bi-basket2-fill"></i></span>
        <span class="brand-text">KiraSoft<small>Billing &amp; Inventory</small></span>
    </a>
    <nav>
        @if($u->isSuperAdmin())
            <div class="nav-section">Platform</div>
            {!! $link('admin.dashboard', 'grid-1x2', 'Overview') !!}
            {!! $link('admin.shops.index', 'shop', 'Shops', 'admin.shops.*') !!}
            {!! $link('admin.signups.index', 'person-plus', 'Signup requests', 'admin.signups.*', \App\Models\SignupRequest::where('status','pending')->count() ?: null) !!}
            {!! $link('admin.users.index', 'people', 'Users') !!}
            {!! $link('admin.activity', 'clock-history', 'Activity logs') !!}
            <div class="nav-section">System</div>
            {!! $link('admin.backups.index', 'database-down', 'Backups', 'admin.backups.*') !!}
            {!! $link('admin.system', 'heart-pulse', 'System health') !!}
        @else
            <div class="nav-section">Main</div>
            @can('dashboard.view'){!! $link('dashboard', 'grid-1x2', 'Dashboard') !!}@endcan
            @can('pos.use'){!! $link('pos.index', 'cart3', 'Point of Sale', 'pos.*') !!}@endcan
            @can('sales.view'){!! $link('sales.index', 'receipt', $u->isCashier() ? 'My sales' : 'Sales & invoices', 'sales.*') !!}@endcan
            @can('returns.create'){!! $link('returns.index', 'arrow-counterclockwise', 'Returns', 'returns.*') !!}@endcan

            @canany(['products.manage', 'inventory.view'])<div class="nav-section">Catalog &amp; stock</div>@endcanany
            @can('products.manage'){!! $link('products.index', 'box-seam', 'Products', 'products.*') !!}@endcan
            @can('categories.manage'){!! $link('categories.index', 'tags', 'Categories', 'categories.*') !!}@endcan
            @can('inventory.view'){!! $link('inventory.index', 'boxes', 'Inventory', 'inventory.index') !!}@endcan
            @can('inventory.adjust'){!! $link('inventory.create', 'plus-slash-minus', 'Stock adjustment', 'inventory.create') !!}@endcan
            @can('inventory.view'){!! $link('inventory.movements', 'arrow-left-right', 'Stock movements', 'inventory.movements') !!}@endcan
            @can('alerts.view'){!! $link('alerts.index', 'bell', 'Stock alerts', 'alerts.*', $alertCounts['total'] ?: null) !!}@endcan

            @canany(['reports.view', 'branches.view', 'employees.view'])<div class="nav-section">Business</div>@endcanany
            @can('reports.view'){!! $link('reports.index', 'bar-chart-line', 'Reports', 'reports.*') !!}@endcan
            @can('branches.view'){!! $link('branches.index', 'diagram-3', 'Branches', 'branches.*') !!}@endcan
            @can('employees.view'){!! $link('employees.index', 'person-badge', 'Employees', 'employees.*') !!}@endcan
            @can('logs.view'){!! $link('activity.index', 'clock-history', 'Activity log', 'activity.*') !!}@endcan
            @can('settings.manage'){!! $link('settings.edit', 'gear', 'Settings', 'settings.*') !!}@endcan
        @endif
    </nav>
    <div class="sidebar-foot">
        <div class="shop-chip">
            <i class="bi {{ $u->isSuperAdmin() ? 'bi-shield-lock' : 'bi-shop-window' }}"></i>
            <span class="chip-text text-truncate">{{ $u->isSuperAdmin() ? 'Platform administration' : $u->shop?->name }}</span>
        </div>
    </div>
</aside>
