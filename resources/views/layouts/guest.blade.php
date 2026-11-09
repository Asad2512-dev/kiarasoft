<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#064e3b">
    <title>{{ isset($title) ? $title.' · ' : '' }}KiraSoft</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%23059669'/><text x='16' y='22' font-size='17' text-anchor='middle' fill='white' font-family='Arial' font-weight='bold'>K</text></svg>">
    @vite('resources/js/app.js')
</head>
<body>
<div class="auth-wrap">
    <aside class="auth-aside">
        <div class="d-flex align-items-center gap-2 fw-bold fs-5">
            <span class="brand-mark d-grid rounded-3" style="width:40px;height:40px;place-items:center;background:linear-gradient(135deg,#10b981,#f59e0b)"><i class="bi bi-basket2-fill"></i></span>
            KiraSoft
        </div>
        <div style="max-width: 460px">
            <h2 class="mb-3">Billing &amp; inventory, made simple for every Kiryana store.</h2>
            <p class="mb-4" style="color:rgba(255,255,255,.75)">Fast checkout, live stock across all your branches and reports you can trust, in Pakistani Rupees.</p>
            <div class="feature"><i class="bi bi-lightning-charge"></i><div><strong class="d-block text-white">Keyboard-fast POS</strong>Search, scan and bill in seconds.</div></div>
            <div class="feature"><i class="bi bi-boxes"></i><div><strong class="d-block text-white">Branch-wise stock</strong>Every movement is recorded and auditable.</div></div>
            <div class="feature"><i class="bi bi-graph-up-arrow"></i><div><strong class="d-block text-white">Real reports</strong>Daily, monthly, branch and cashier sales.</div></div>
        </div>
        <div class="small" style="color:rgba(255,255,255,.5)">© {{ date('Y') }} KiraSoft · Grocery Billing &amp; Inventory Management System</div>
    </aside>
    <main class="auth-panel">
        <div class="auth-card">
            <div class="d-lg-none d-flex align-items-center gap-2 fw-bold fs-5 mb-4">
                <span class="d-grid rounded-3 text-white" style="width:40px;height:40px;place-items:center;background:linear-gradient(135deg,#10b981,#f59e0b)"><i class="bi bi-basket2-fill"></i></span> KiraSoft
            </div>
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
