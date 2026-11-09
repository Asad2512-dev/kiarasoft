<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#064e3b">
    <title>KiraSoft · Grocery Billing &amp; Inventory for Pakistani Kiryana Stores</title>
    <meta name="description" content="Fast POS billing, branch-wise stock, returns and real sales reports in Pakistani Rupees, built for Kiryana stores, mini marts and small retailers.">
    <meta property="og:title" content="KiraSoft · Grocery Billing & Inventory">
    <meta property="og:description" content="Fast POS billing, branch-wise stock and real reports for Pakistani grocery stores.">
    <meta property="og:image" content="{{ asset('images/screen-pos.jpg') }}">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%23059669'/><text x='16' y='22' font-size='17' text-anchor='middle' fill='white' font-family='Arial' font-weight='bold'>K</text></svg>">
    @vite('resources/js/app.js')
</head>
<body class="lp" x-data="{ menu: false, scrolled: false }" @scroll.window="scrolled = window.scrollY > 8">

<a href="#main" class="skip-link btn btn-primary btn-sm">Skip to content</a>

{{-- ============ NAV ============ --}}
<header class="lp-nav" :class="{ 'is-scrolled': scrolled || menu }">
    <div class="lp-container d-flex align-items-center gap-3">
        <a href="{{ url('/') }}" class="lp-brand" aria-label="KiraSoft home">
            <span class="lp-mark"><i class="bi bi-basket2-fill"></i></span> KiraSoft
        </a>
        <nav class="lp-links d-none d-lg-flex" aria-label="Main">
            <a href="#features">Features</a><a href="#pos">POS</a><a href="#stock">Inventory</a><a href="#reports">Reports</a><a href="#roles">Roles</a><a href="#faq">FAQ</a>
        </nav>
        <div class="ms-auto d-none d-sm-flex align-items-center gap-2">
            <a href="{{ route('login') }}" class="btn btn-white">Sign in</a>
            <a href="{{ route('signup.create') }}" class="btn btn-primary">Register your shop</a>
        </div>
        <button class="icon-btn d-lg-none ms-auto ms-sm-0" @click="menu = !menu" :aria-expanded="menu" aria-controls="lp-mobile" aria-label="Menu"><i class="bi" :class="menu ? 'bi-x-lg' : 'bi-list'"></i></button>
    </div>
    <div id="lp-mobile" class="lp-mobile d-lg-none" x-show="menu" x-cloak x-transition.opacity @click="menu = false">
        <a href="#features">Features</a><a href="#pos">POS</a><a href="#stock">Inventory</a><a href="#reports">Reports</a><a href="#roles">Roles</a><a href="#faq">FAQ</a>
        <div class="d-grid gap-2 mt-3"><a href="{{ route('signup.create') }}" class="btn btn-primary btn-lg">Register your shop</a><a href="{{ route('login') }}" class="btn btn-white btn-lg">Sign in</a></div>
    </div>
</header>

<main id="main">
{{-- ============ HERO ============ --}}
<section class="lp-hero">
    <div class="lp-container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5 lp-reveal">
                <span class="lp-eyebrow"><span class="lp-dot"></span> Made for Pakistani Kiryana stores</span>
                <h1 class="lp-h1">Bill faster. <span class="lp-grad">Never lose track</span> of stock again.</h1>
                <p class="lp-lead">KiraSoft is a complete billing and inventory system for grocery stores, mini marts and small retailers. Run every till, branch and report from one place, in Rupees.</p>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="{{ route('signup.create') }}" class="btn btn-primary btn-lg px-4">Register your shop <i class="bi bi-arrow-right"></i></a>
                    <a href="{{ route('login') }}" class="btn btn-white btn-lg px-4"><i class="bi bi-box-arrow-in-right"></i> Sign in</a>
                </div>
                <ul class="lp-checks">
                    <li><i class="bi bi-check-circle-fill"></i> Works in any browser</li>
                    <li><i class="bi bi-check-circle-fill"></i> Multi-branch</li>
                    <li><i class="bi bi-check-circle-fill"></i> PKR receipts</li>
                </ul>
            </div>
            <div class="col-lg-7 lp-reveal" style="--d:.1s">
                <div class="lp-shot-wrap">
                    <div class="lp-window">
                        <div class="lp-window-bar"><span></span><span></span><span></span><div class="lp-url"><i class="bi bi-lock-fill"></i> kirasoft · Point of Sale</div></div>
                        <img src="{{ asset('images/screen-pos.jpg') }}" alt="KiraSoft point-of-sale screen with products, cart and total" width="1440" height="900" fetchpriority="high">
                    </div>
                    <div class="lp-float lp-float-1"><span class="lp-float-ic tint-brand"><i class="bi bi-receipt"></i></span><div><div class="small text-muted">Invoice</div><strong class="mono">INV-G11-0042</strong></div></div>
                    <div class="lp-float lp-float-2"><span class="lp-float-ic tint-amber"><i class="bi bi-exclamation-triangle"></i></span><div><div class="small text-muted">Low stock</div><strong>Cooking Oil 5L</strong></div></div>
                    <img class="lp-phone d-none d-md-block" src="{{ asset('images/screen-mobile.jpg') }}" alt="KiraSoft dashboard on a phone" width="390" height="844" loading="lazy">
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============ STRIP ============ --}}
<section class="lp-strip" aria-label="Highlights">
    <div class="lp-container lp-strip-grid">
        <div><strong>18</strong><span>built-in modules</span></div>
        <div><strong>4</strong><span>user roles</span></div>
        <div><strong>0.001</strong><span>kg precision for loose items</span></div>
        <div><strong>100%</strong><span>of stock changes logged</span></div>
    </div>
</section>

{{-- ============ FEATURES ============ --}}
<section id="features" class="lp-section">
    <div class="lp-container">
        <div class="lp-head lp-reveal">
            <span class="lp-kicker">Everything your shop needs</span>
            <h2>One system from the counter to the back office</h2>
            <p>No more registers, loose parchis or guessing what's left on the shelf.</p>
        </div>
        <div class="row g-3 g-lg-4">
            @foreach([
                ['cart3', 'brand', 'Lightning-fast POS', 'Search, scan a barcode or type a SKU. Use keyboard shortcuts, then press F9 to take payment and see the change.'],
                ['boxes', 'blue', 'Branch-wise inventory', 'Live stock for every branch. Every sale, delivery and correction is recorded with who did it and when.'],
                ['receipt-cutoff', 'violet', 'Invoices & receipts', 'Unique invoice numbers per branch and clean 80mm thermal receipts with your logo, header and footer.'],
                ['arrow-counterclockwise', 'amber', 'Controlled returns', 'Refund part or all of an invoice. Quantities are validated, refunds stay fair after discounts and stock goes back to the shelf.'],
                ['bar-chart-line', 'brand', 'Sales reports', 'Daily, weekly, monthly or custom reports by branch and cashier, with charts, CSV export and print.'],
                ['bell', 'red', 'Low-stock alerts', 'Set thresholds per product or branch and see what to reorder before it runs out.'],
                ['people', 'blue', 'Staff & roles', 'Owner, manager and cashier accounts with exactly the access each one needs. Nothing more.'],
                ['shield-check', 'violet', 'Audit trail', 'Logins, price changes, stock adjustments and refunds are all logged and searchable.'],
                ['database-check', 'gray', 'Backups', 'One-click database backups with a verified test restore, so your records are safe.'],
            ] as $i => [$ic, $tint, $t, $d])
                <div class="col-sm-6 col-lg-4 lp-reveal" style="--d: {{ ($i % 3) * .06 }}s">
                    <div class="lp-card h-100">
                        <span class="lp-ic tint-{{ $tint }}"><i class="bi bi-{{ $ic }}"></i></span>
                        <h3>{{ $t }}</h3><p>{{ $d }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ POS SPOTLIGHT ============ --}}
<section id="pos" class="lp-section lp-alt">
    <div class="lp-container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5 lp-reveal">
                <span class="lp-kicker">Point of sale</span>
                <h2 class="lp-h2">Built for a busy counter</h2>
                <p class="lp-p">Long queues at Eid or the evening rush? Every step of the KiraSoft POS is designed to save seconds.</p>
                <div class="lp-keys">
                    <div><kbd>F2</kbd><span>Jump to search or scan</span></div>
                    <div><kbd>Enter</kbd><span>Add the highlighted item</span></div>
                    <div><kbd>F9</kbd><span>Take payment</span></div>
                    <div><kbd>F8</kbd><span>Print the receipt</span></div>
                </div>
                <ul class="lp-list">
                    <li><i class="bi bi-check2"></i> Sell loose items by weight: 1.25&nbsp;kg daal or 0.5&nbsp;L milk</li>
                    <li><i class="bi bi-check2"></i> Quick-cash buttons and instant change calculation</li>
                    <li><i class="bi bi-check2"></i> Can't sell what isn't in stock, and a double-click never bills twice</li>
                    <li><i class="bi bi-check2"></i> Works on a tablet or phone, with a slide-up cart</li>
                </ul>
            </div>
            <div class="col-lg-7 lp-reveal" style="--d:.1s">
                <div class="lp-receipt-demo">
                    <div class="lp-receipt" aria-label="Sample receipt">
                        <div class="text-center"><strong class="d-block fs-6">Al-Noor Kiryana Store</strong><small>G-11 Markaz, Islamabad</small></div>
                        <hr><div class="d-flex justify-content-between"><span>Invoice</span><b>INV-G11-20261109-0042</b></div><hr>
                        <div class="lp-r-line"><span>Basmati Rice 5kg</span><span>2,150.00</span></div><small>1 pack × 2,150.00</small>
                        <div class="lp-r-line"><span>Daal Masoor (loose)</span><span>400.00</span></div><small>1.25 kg × 320.00</small>
                        <div class="lp-r-line"><span>Cooking Oil 1L</span><span>1,090.00</span></div><small>2 pack × 545.00</small>
                        <hr><div class="lp-r-line"><span>Discount (5%)</span><span>-182.00</span></div>
                        <div class="lp-r-line lp-r-total"><span>TOTAL (PKR)</span><span>3,458.00</span></div>
                        <div class="lp-r-line"><span>Cash</span><span>4,000.00</span></div>
                        <div class="lp-r-line"><span>Change</span><span>542.00</span></div>
                        <hr><div class="text-center"><small>Shukriya! Please visit again.</small></div>
                    </div>
                    <div class="lp-change"><span class="small">Change to return</span><strong>Rs 542.00</strong></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============ INVENTORY ============ --}}
<section id="stock" class="lp-section">
    <div class="lp-container">
        <div class="row align-items-center g-5 flex-lg-row-reverse">
            <div class="col-lg-5 lp-reveal">
                <span class="lp-kicker">Inventory</span>
                <h2 class="lp-h2">Know exactly what's on every shelf</h2>
                <p class="lp-p">Each branch has its own live stock. Deliveries, damages, count corrections, sales and returns are each recorded as a movement, so the numbers always add up.</p>
                <div class="lp-mini-ledger">
                    <div class="lp-ledger-row"><span class="badge-soft badge-soft-success">Stock in</span><span>Supplier delivery</span><b class="text-success">+40</b></div>
                    <div class="lp-ledger-row"><span class="badge-soft badge-soft-danger">Sale</span><span>INV-G11-…-0042</span><b class="text-danger">−2</b></div>
                    <div class="lp-ledger-row"><span class="badge-soft badge-soft-warning">Adjustment</span><span>Damaged packaging</span><b class="text-danger">−1</b></div>
                    <div class="lp-ledger-row"><span class="badge-soft badge-soft-success">Return</span><span>RET-G11-…-0003</span><b class="text-success">+1</b></div>
                </div>
                <p class="small text-muted mt-3 mb-0"><i class="bi bi-patch-check"></i> One-click reconciliation confirms that stock equals the sum of all movements.</p>
            </div>
            <div class="col-lg-7 lp-reveal" style="--d:.1s">
                <div class="lp-window"><div class="lp-window-bar"><span></span><span></span><span></span><div class="lp-url"><i class="bi bi-lock-fill"></i> kirasoft · Dashboard</div></div>
                    <img src="{{ asset('images/screen-dashboard.jpg') }}" alt="KiraSoft dashboard with today's sales, sales trend chart, branch comparison and low-stock list" width="1440" height="900" loading="lazy"></div>
            </div>
        </div>
    </div>
</section>

{{-- ============ REPORTS ============ --}}
<section id="reports" class="lp-section lp-dark">
    <div class="lp-container">
        <div class="lp-head lp-reveal">
            <span class="lp-kicker">Reports</span>
            <h2>Numbers you can actually trust</h2>
            <p>Every figure is calculated from your saved invoices and refunds, and each report shows exactly how it was worked out.</p>
        </div>
        <div class="lp-reveal" style="--d:.1s">
            <div class="lp-window lp-window-dark"><div class="lp-window-bar"><span></span><span></span><span></span><div class="lp-url"><i class="bi bi-lock-fill"></i> kirasoft · Sales reports</div></div>
                <img src="{{ asset('images/screen-reports.jpg') }}" alt="KiraSoft sales report with gross, discounts, returns, net sales and a daily chart" width="1440" height="900" loading="lazy"></div>
        </div>
        <div class="row g-3 mt-4 text-center">
            @foreach([['calendar-week', 'Daily · weekly · monthly · custom'], ['diagram-3', 'Branch comparison'], ['person-badge', 'Cashier performance'], ['filetype-csv', 'CSV export & print']] as [$ic, $t])
                <div class="col-6 col-lg-3"><div class="lp-pill"><i class="bi bi-{{ $ic }}"></i> {{ $t }}</div></div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ ROLES ============ --}}
<section id="roles" class="lp-section">
    <div class="lp-container">
        <div class="lp-head lp-reveal">
            <span class="lp-kicker">Roles &amp; security</span>
            <h2>The right access for every person</h2>
            <p>Permissions are checked on the server for every request. One branch's staff can never see another branch's data.</p>
        </div>
        <div class="row g-3 g-lg-4">
            @foreach([
                ['person-gear', 'brand', 'Shop Admin', 'The owner', ['All branches & reports', 'Employees & roles', 'Prices, tax & receipts']],
                ['diagram-3', 'blue', 'Branch Manager', 'Runs a branch', ['Stock & adjustments', 'Returns & refunds', 'Branch reports']],
                ['cart3', 'violet', 'Cashier', 'At the counter', ['Fast POS billing', 'Print receipts', 'Own sales only']],
                ['shield-lock', 'gray', 'Super Admin', 'Platform', ['Approves new shops', 'Backups & health', 'Platform audit log']],
            ] as $i => [$ic, $tint, $t, $sub, $items])
                <div class="col-sm-6 col-lg-3 lp-reveal" style="--d: {{ $i * .05 }}s">
                    <div class="lp-card h-100">
                        <span class="lp-ic tint-{{ $tint }}"><i class="bi bi-{{ $ic }}"></i></span>
                        <h3 class="mb-0">{{ $t }}</h3><div class="small text-muted mb-3">{{ $sub }}</div>
                        <ul class="lp-list small mb-0">@foreach($items as $it)<li><i class="bi bi-check2"></i> {{ $it }}</li>@endforeach</ul>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="lp-sec-row lp-reveal">
            @foreach(['Encrypted passwords', 'CSRF & XSS protection', 'Login rate-limiting', 'Shop & branch isolation', 'Full activity log', 'Private backups'] as $s)
                <span><i class="bi bi-shield-fill-check"></i> {{ $s }}</span>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ HOW IT WORKS ============ --}}
<section class="lp-section lp-alt">
    <div class="lp-container">
        <div class="lp-head lp-reveal"><span class="lp-kicker">Getting started</span><h2>Up and running in an afternoon</h2></div>
        <div class="row g-4 lp-steps">
            @foreach([['Register your shop', 'Send a short request. Once approved, you get your Shop Admin login.'], ['Add branches & products', 'Create branches, categories and products, then enter your opening stock.'], ['Add your staff', 'Give managers and cashiers their own accounts and branches.'], ['Start selling', 'Open the POS and bill. Stock, invoices and reports update automatically.']] as $i => [$t, $d])
                <div class="col-sm-6 col-lg-3 lp-reveal" style="--d: {{ $i * .06 }}s"><div class="lp-step"><span>{{ $i + 1 }}</span><h3>{{ $t }}</h3><p>{{ $d }}</p></div></div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ FAQ ============ --}}
<section id="faq" class="lp-section">
    <div class="lp-container" style="max-width: 860px">
        <div class="lp-head lp-reveal"><span class="lp-kicker">FAQ</span><h2>Questions shopkeepers ask</h2></div>
        <div class="accordion lp-faq lp-reveal" id="faqAcc">
            @foreach([
                ['Do I need to install anything?', 'No. KiraSoft runs in a web browser on a computer, tablet or phone. It can also run on a local computer in the shop using MAMP or XAMPP.'],
                ['Can I sell loose items like daal, sugar or milk?', 'Yes. Products can be sold by kg, gram, litre or millilitre with decimal quantities (for example 1.25 kg). Packs, pieces, bottles and dozens are sold in whole units.'],
                ['I have more than one branch. Is that supported?', 'Yes. Each branch has its own stock, staff, invoice numbers and reports, and the owner can see all of them together or one at a time.'],
                ['What if a cashier makes a mistake or a customer returns an item?', 'Managers can process a full or partial return against the original invoice. The refund is calculated fairly (including any discount) and the item can go back into stock.'],
                ['What payment methods are supported?', 'Cash payments with automatic change calculation. Every invoice records the amount received and the change given.'],
                ['Are alerts sent by SMS?', 'Low-stock and out-of-stock alerts are shown inside KiraSoft, in the notification bell and on the alerts page. SMS and e-mail alerts are not included.'],
                ['Is my data safe?', 'Each shop\'s data is isolated, passwords are encrypted, every important action is logged and the administrator can take verified database backups.'],
            ] as $i => [$q, $a])
                <div class="accordion-item">
                    <h3 class="accordion-header"><button class="accordion-button {{ $i ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}" aria-expanded="{{ $i ? 'false' : 'true' }}" aria-controls="faq{{ $i }}">{{ $q }}</button></h3>
                    <div id="faq{{ $i }}" class="accordion-collapse collapse {{ $i ? '' : 'show' }}" data-bs-parent="#faqAcc"><div class="accordion-body">{{ $a }}</div></div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ CTA ============ --}}
<section class="lp-section pt-0">
    <div class="lp-container">
        <div class="lp-cta lp-reveal">
            <div>
                <h2>Ready to modernise your Kiryana store?</h2>
                <p>Register your shop today and start billing with KiraSoft.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('signup.create') }}" class="btn btn-light btn-lg px-4 fw-semibold">Register your shop <i class="bi bi-arrow-right"></i></a>
                <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg px-4">Sign in</a>
            </div>
        </div>
    </div>
</section>
</main>

<footer class="lp-footer">
    <div class="lp-container d-flex flex-wrap gap-3 justify-content-between align-items-center">
        <a href="{{ url('/') }}" class="lp-brand"><span class="lp-mark"><i class="bi bi-basket2-fill"></i></span> KiraSoft</a>
        <nav class="d-flex flex-wrap gap-3 small" aria-label="Footer"><a href="#features">Features</a><a href="#faq">FAQ</a><a href="{{ route('login') }}">Sign in</a><a href="{{ route('signup.create') }}">Register</a></nav>
        <div class="small text-muted w-100 w-md-auto">© {{ date('Y') }} KiraSoft · Grocery Billing &amp; Inventory Management System</div>
    </div>
</footer>

<script>
    // Reveal-on-scroll (progressive enhancement; content is visible without JS)
    document.documentElement.classList.add('lp-js');
    const io = 'IntersectionObserver' in window ? new IntersectionObserver((es) => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } }), { threshold: .12 }) : null;
    document.querySelectorAll('.lp-reveal').forEach(el => io ? io.observe(el) : el.classList.add('is-in'));
</script>
</body>
</html>
