@extends('layouts.app', ['title' => 'Point of Sale', 'bodyClass' => 'pos-body'])
@section('content')
@if($branches->isEmpty())
    <div class="card"><x-empty icon="diagram-3" title="No active branch assigned" text="You need to be assigned to an active branch before you can make sales. Please contact your shop admin."/></div>
@else
<div x-data="pos({
        branches: @js($branches->map->only(['id', 'name'])),
        branchId: {{ $branches->first()->id }},
        categories: @js($categories),
        taxEnabled: @js($settings->tax_enabled),
        taxRate: @js($settings->tax_rate),
        maxDiscount: @js($settings->max_discount_percent),
        key: @js($checkoutKey),
        productsUrl: @js(route('pos.products')),
        checkoutUrl: @js(route('pos.checkout')),
        csrf: @js(csrf_token()),
    })" class="pos-layout">

    {{-- ================= Catalog ================= --}}
    <section class="pos-catalog" aria-label="Products">
        <div class="d-flex gap-2 align-items-stretch">
            <div class="pos-search flex-grow-1">
                <i class="bi bi-upc-scan"></i>
                <input x-ref="search" x-model="query" @keydown.enter.prevent="onSearchEnter()" type="search" class="form-control" placeholder="Search name, SKU or scan barcode…" aria-label="Search products" autocomplete="off" :disabled="step !== 'cart'">
                <kbd class="d-none d-md-inline">F2</kbd>
            </div>
            <template x-if="branches.length > 1">
                <select x-model.number="branchId" class="form-select" style="max-width: 200px; height: 52px; border-radius: var(--ks-radius)" aria-label="Selling branch">
                    <template x-for="b in branches" :key="b.id"><option :value="b.id" x-text="b.name" :selected="b.id === branchId"></option></template>
                </select>
            </template>
        </div>

        <div class="pos-cats" role="tablist" aria-label="Categories">
            <button type="button" :class="{ active: cat === null }" @click="cat = null" role="tab" :aria-selected="cat === null">All</button>
            <template x-for="c in categories" :key="c.id">
                <button type="button" :class="{ active: cat === c.id }" @click="cat = c.id" x-text="c.name" role="tab" :aria-selected="cat === c.id"></button>
            </template>
        </div>

        <div x-show="loading" class="pos-grid" aria-busy="true">
            <template x-for="i in 12"><div class="pos-product placeholder-glow"><span class="placeholder col-9 rounded"></span><span class="placeholder col-4 rounded"></span><span class="placeholder col-6 rounded mt-auto"></span></div></template>
        </div>
        <div x-show="loadError" x-cloak><div class="alert alert-danger d-flex justify-content-between align-items-center"><span x-text="loadError"></span><button class="btn btn-sm btn-white" @click="load()">Retry</button></div></div>
        <div x-show="!loading && !loadError" x-cloak class="pos-grid" role="list">
            <template x-for="(p, idx) in filtered" :key="p.id">
                <button type="button" role="listitem" class="pos-product" :class="{ 'kb-active': query && idx === kb }" @click="add(p)" :disabled="stockMilli(p) <= 0" :aria-label="`${p.name}, ${money(Math.round(p.price*100))}, stock ${p.stock} ${p.unit}`">
                    <span class="in-cart" x-show="inCart(p.id)" x-text="inCart(p.id)?.qty"></span>
                    <span class="p-name" x-text="p.name"></span>
                    <span class="p-sku" x-text="p.sku"></span>
                    <span class="p-price"><span x-text="money(Math.round(p.price * 100))"></span><small class="text-muted fw-normal" x-text="' / ' + p.unit"></small></span>
                    <span class="p-stock" :class="stockMilli(p) <= 0 ? 'text-danger' : (p.low ? 'text-warning' : 'text-success')"
                          x-text="stockMilli(p) <= 0 ? 'Out of stock' : `${p.stock} ${p.unit} in stock`"></span>
                </button>
            </template>
            <div x-show="!filtered.length" class="card" style="grid-column: 1 / -1">
                <x-empty icon="search" title="No products found" text="Try a different name or SKU, or choose another category."/>
            </div>
        </div>
    </section>

    {{-- ================= Cart ================= --}}
    <aside class="pos-cart" :class="{ open: cartOpen }" aria-label="Cart">
        <button type="button" class="pos-cart-handle btn w-100 border-0 justify-content-between px-3 py-3" @click="cartOpen = !cartOpen" :aria-expanded="cartOpen">
            <span class="d-flex align-items-center gap-2 fw-semibold"><i class="bi bi-cart3 fs-5"></i> Cart <span class="badge rounded-pill text-bg-primary" x-text="itemCount"></span></span>
            <span class="d-flex align-items-center gap-2"><strong class="tabular fs-5" x-text="money(total)"></strong><i class="bi" :class="cartOpen ? 'bi-chevron-down' : 'bi-chevron-up'"></i></span>
        </button>
        <div class="cart-head">
            <div><div class="fw-semibold">Current sale</div><div class="small text-muted"><span x-text="itemCount"></span> item(s)</div></div>
            <button type="button" class="btn btn-sm btn-soft-danger" @click="clear()" :disabled="!cart.length"><i class="bi bi-trash3"></i> Clear</button>
        </div>
        <div class="cart-items">
            <template x-if="!cart.length">
                <div class="empty-state py-5"><div class="empty-icon"><i class="bi bi-cart"></i></div><h3>Cart is empty</h3><p class="small mb-0">Tap a product or scan a barcode to start.</p></div>
            </template>
            <template x-for="line in cart" :key="line.id">
                <div class="cart-line" :class="{ 'has-error': lineErrors[line.id] }">
                    <div class="min-w-0">
                        <div class="l-name text-truncate" x-text="line.name"></div>
                        <div class="l-price"><span x-text="money(Math.round(line.price*100))"></span> / <span x-text="line.unit"></span> · <span x-text="line.stock"></span> avail.</div>
                    </div>
                    <div class="l-total" x-text="money(lineTotal(line))"></div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="qty-ctrl">
                            <button type="button" @click="inc(line, -1)" aria-label="Decrease quantity"><i class="bi bi-dash"></i></button>
                            <input type="text" :inputmode="line.decimal ? 'decimal' : 'numeric'" x-model="line.qty" @blur="normalize(line)" @keydown.enter.prevent="normalize(line); $refs.search.focus()" :aria-label="`Quantity of ${line.name}`">
                            <button type="button" @click="inc(line, 1)" aria-label="Increase quantity"><i class="bi bi-plus"></i></button>
                        </div>
                        <span class="small text-muted" x-text="line.unit"></span>
                    </div>
                    <div class="text-end"><button type="button" class="btn btn-sm btn-link text-danger p-0" @click="confirmRemove(line)" :aria-label="`Remove ${line.name}`"><i class="bi bi-x-circle"></i> Remove</button></div>
                    <div class="small text-danger" style="grid-column: 1 / -1" x-show="lineErrors[line.id]" x-text="lineErrors[line.id]"></div>
                </div>
            </template>
        </div>
        <div class="cart-summary">
            <div class="row g-2 mb-2">
                <div class="col-7">
                    <div class="input-group input-group-sm">
                        <select x-model="discountType" class="form-select discount-type" aria-label="Discount type"><option value="fixed">Rs</option><option value="percent">%</option></select>
                        <input x-model="discountValue" type="text" inputmode="decimal" class="form-control" placeholder="Discount" aria-label="Discount value" :class="{ 'is-invalid': discountInvalid }">
                    </div>
                </div>
                <div class="col-5"><input x-model="customer" type="text" class="form-control form-control-sm" placeholder="Customer (opt.)" aria-label="Customer name" maxlength="120"></div>
                <div class="col-12 small text-danger" x-show="discountInvalid" x-text="discountInvalid"></div>
            </div>
            <div class="row-line"><span>Subtotal</span><span x-text="money(subtotal)"></span></div>
            <div class="row-line" x-show="discount > 0"><span>Discount</span><span class="text-danger" x-text="'-' + money(discount)"></span></div>
            <div class="row-line" x-show="taxEnabled"><span>Tax (<span x-text="taxRate/100"></span>%)</span><span x-text="money(tax)"></span></div>
            <div class="row-line grand"><span>Total</span><span x-text="money(total)"></span></div>
            <button type="button" class="btn btn-primary w-100 pay-btn mt-3" @click="openPay()" :disabled="!cart.length">
                <i class="bi bi-cash-coin"></i> Charge <span x-text="money(total)"></span> <kbd class="ms-1 d-none d-md-inline bg-transparent border border-light-subtle">F9</kbd>
            </button>
            <div class="kbd-hint small text-muted text-center mt-2 d-none d-lg-block"><kbd>F2</kbd> search · <kbd>↑↓</kbd> select · <kbd>Enter</kbd> add · <kbd>F8</kbd> clear · <kbd>F9</kbd> pay</div>
        </div>
    </aside>

    {{-- ================= Payment modal ================= --}}
    <div x-show="step !== 'cart'" x-cloak class="modal pos-overlay" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="payTitle" style="background: rgba(16,24,40,.55); backdrop-filter: blur(3px)">
        <div class="modal-dialog modal-dialog-centered" @click.outside="step === 'pay' && !submitting && (step = 'cart')">
            <div class="modal-content border-0 shadow-lg">
                <template x-if="step === 'pay'">
                    <div>
                        <div class="modal-header border-0 pb-0"><h2 class="modal-title h5" id="payTitle">Cash payment</h2><button type="button" class="btn-close" @click="step = 'cart'" :disabled="submitting" aria-label="Close"></button></div>
                        <div class="modal-body">
                            <div class="text-center mb-3"><div class="small-caps">Amount due</div><div class="display-6 fw-bold tabular" x-text="money(total)"></div><div class="small text-muted"><span x-text="itemCount"></span> item(s)<span x-show="discount > 0"> · discount <span x-text="money(discount)"></span></span></div></div>
                            <label for="tendered" class="form-label">Cash received (Rs)</label>
                            <input id="tendered" x-ref="tendered" x-model="tendered" @keydown.enter.prevent="checkout()" type="text" inputmode="decimal" class="form-control form-control-lg tabular text-end fs-3" placeholder="0" autocomplete="off">
                            <div class="quick-cash mt-2">
                                <template x-for="v in quickCash" :key="v"><button type="button" class="btn btn-white btn-sm" @click="tendered = String(v)" x-text="'Rs ' + v.toLocaleString('en-PK')"></button></template>
                            </div>
                            <div class="change-display mt-3" :class="{ short: tendered !== '' && change < 0 }">
                                <span x-text="change < 0 ? 'Remaining' : 'Change to return'"></span><span class="amt" x-text="tendered === '' ? '—' : money(Math.abs(change))"></span>
                            </div>
                            <div x-show="error" class="alert alert-danger mt-3 mb-0 small" role="alert" x-text="error"></div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-white" @click="step = 'cart'" :disabled="submitting">Back</button>
                            <button type="button" class="btn btn-primary btn-lg flex-grow-1" @click="checkout()" :disabled="submitting || tendered === '' || change < 0">
                                <span x-show="submitting" class="spinner-border spinner-border-sm"></span>
                                <span x-text="submitting ? 'Processing…' : 'Complete sale'"></span>
                            </button>
                        </div>
                    </div>
                </template>
                <template x-if="step === 'done' && result">
                    <div class="modal-body text-center p-4">
                        <div class="success-check"><i class="bi bi-check-lg"></i></div>
                        <h2 class="h4 mt-3 mb-1">Sale completed</h2>
                        <div class="text-muted mono" x-text="result.sale.invoice_no"></div>
                        <div class="row g-2 my-4 text-start">
                            <div class="col-6"><div class="p-3 rounded-3 bg-light"><div class="small text-muted">Total</div><div class="fw-bold fs-5 tabular" x-text="money(Math.round(result.sale.total*100))"></div></div></div>
                            <div class="col-6"><div class="p-3 rounded-3" style="background: var(--ks-brand-50)"><div class="small text-muted">Change</div><div class="fw-bold fs-5 tabular" style="color: var(--ks-brand-700)" x-text="money(Math.round(result.sale.change_due*100))"></div></div></div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-white btn-lg" @click="printReceipt()"><i class="bi bi-printer"></i> Print receipt <kbd class="bg-transparent text-muted border">F8</kbd></button>
                            <button type="button" class="btn btn-primary btn-lg" @click="newSale()"><i class="bi bi-plus-lg"></i> New sale <kbd class="bg-transparent border border-light-subtle">Esc</kbd></button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
