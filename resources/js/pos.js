// Point-of-sale Alpine component. All amounts here are DISPLAY ONLY:
// the server re-reads prices and stock and recalculates every total at checkout.

const toPaisa = (v) => {
    const s = String(v ?? '').trim(); if (!/^\d*(\.\d*)?$/.test(s) || s === '' || s === '.') return 0;
    const [i, f = ''] = s.split('.'); return parseInt(i || '0', 10) * 100 + parseInt((f + '00').slice(0, 2), 10) + ((f[2] ?? '0') >= '5' ? 1 : 0);
};
const toMilli = (v) => {
    const s = String(v ?? '').trim(); if (!/^\d*(\.\d*)?$/.test(s) || s === '' || s === '.') return 0;
    const [i, f = ''] = s.split('.'); return parseInt(i || '0', 10) * 1000 + parseInt((f + '000').slice(0, 3), 10);
};
const divRound = (n, d) => Math.round(n / d);
const fmtQty = (m) => { const s = (m / 1000).toFixed(3); return s.replace(/\.?0+$/, ''); };

export default (config) => ({
    branches: config.branches,
    branchId: config.branchId,
    categories: config.categories,
    taxEnabled: config.taxEnabled,
    taxRate: toPaisa(config.taxRate), // hundredths of a percent
    maxDiscount: toPaisa(config.maxDiscount),
    products: [],
    loading: true,
    loadError: null,
    query: '',
    cat: null,
    kb: 0,
    cart: [],
    lineErrors: {},
    discountType: 'fixed',
    discountValue: '',
    tendered: '',
    customer: '',
    key: config.key,
    step: 'cart',
    submitting: false,
    result: null,
    cartOpen: false,
    error: null,

    init() {
        this.load();
        this.$watch('branchId', () => { if (this.cart.length && !confirm('Switching branch clears the current cart. Continue?')) return; this.cart = []; this.load(); });
        this.$watch('query', () => (this.kb = 0));
        window.addEventListener('keydown', (e) => this.hotkeys(e));
        window.addEventListener('beforeunload', (e) => { if (this.cart.length && this.step !== 'done') { e.preventDefault(); e.returnValue = ''; } });
    },

    async load() {
        this.loading = true; this.loadError = null;
        try {
            const r = await fetch(`${config.productsUrl}?branch=${this.branchId}`, { headers: { Accept: 'application/json' } });
            if (!r.ok) throw new Error('Could not load products (' + r.status + ')');
            this.products = (await r.json()).products;
        } catch (e) { this.loadError = e.message; }
        this.loading = false;
        this.$nextTick(() => this.$refs.search?.focus());
    },

    get filtered() {
        const q = this.query.trim().toLowerCase();
        let list = this.products;
        if (this.cat) list = list.filter((p) => p.category_id === this.cat);
        if (q) list = list.filter((p) => p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q) || (p.barcode && p.barcode === q));
        return list.slice(0, 120);
    },

    inCart(id) { return this.cart.find((l) => l.id === id); },
    stockMilli(p) { return toMilli(p.stock); },

    add(p, qty = null) {
        if (!p || this.stockMilli(p) <= 0) { window.toast(`${p?.name ?? 'Product'} is out of stock`, 'error'); return; }
        const line = this.inCart(p.id);
        const step = qty ?? 1;
        if (line) {
            const next = toMilli(line.qty) + step * 1000;
            if (next > this.stockMilli(p)) { window.toast(`Only ${p.stock} ${p.unit} of ${p.name} available`, 'error'); return; }
            line.qty = fmtQty(next);
        } else {
            this.cart.push({ id: p.id, name: p.name, sku: p.sku, unit: p.unit, decimal: p.decimal, price: p.price, stock: p.stock, qty: '1' });
        }
        delete this.lineErrors[p.id];
        this.query = '';
        this.$refs.search?.focus();
    },

    inc(line, d) {
        const next = toMilli(line.qty) + d * 1000;
        if (next <= 0) return this.remove(line);
        if (next > toMilli(line.stock)) { window.toast(`Only ${line.stock} ${line.unit} available`, 'error'); return; }
        line.qty = fmtQty(next);
    },

    normalize(line) {
        let m = toMilli(line.qty);
        if (!line.decimal) m = Math.round(m / 1000) * 1000;
        if (m <= 0) { this.remove(line); return; }
        if (m > toMilli(line.stock)) { window.toast(`Only ${line.stock} ${line.unit} of ${line.name} available`, 'error'); m = toMilli(line.stock); }
        line.qty = fmtQty(m);
    },

    remove(line) { this.cart = this.cart.filter((l) => l !== line); delete this.lineErrors[line.id]; },
    clear() { if (!this.cart.length || confirm('Clear all items from the cart?')) { this.cart = []; this.lineErrors = {}; this.discountValue = ''; this.customer = ''; } },

    lineTotal(l) { return divRound(toMilli(l.qty) * toPaisa(l.price), 1000); },
    get subtotal() { return this.cart.reduce((s, l) => s + this.lineTotal(l), 0); },
    get discount() {
        const v = toPaisa(this.discountValue); if (!v) return 0;
        return this.discountType === 'percent' ? divRound(this.subtotal * Math.min(v, this.maxDiscount), 10000) : Math.min(v, this.subtotal);
    },
    get discountInvalid() {
        const v = toPaisa(this.discountValue); if (!v) return null;
        if (this.discountType === 'percent' && v > this.maxDiscount) return `Max discount is ${this.maxDiscount / 100}%`;
        if (this.discountType === 'fixed' && v * 10000 > this.subtotal * this.maxDiscount) return `Max discount is ${this.maxDiscount / 100}% of subtotal`;
        return null;
    },
    get tax() { return this.taxEnabled ? divRound((this.subtotal - this.discount) * this.taxRate, 10000) : 0; },
    get total() { return this.subtotal - this.discount + this.tax; },
    get itemCount() { return this.cart.length; },
    get tenderedPaisa() { return toPaisa(this.tendered); },
    get change() { return this.tenderedPaisa - this.total; },
    get quickCash() {
        const t = this.total / 100; const out = new Set([Math.ceil(t)]);
        [100, 500, 1000, 5000].forEach((n) => out.add(Math.ceil(t / n) * n));
        return [...out].filter((v) => v >= t).sort((a, b) => a - b).slice(0, 4);
    },

    money(p) { return window.ksMoney(p); },

    openPay() {
        if (!this.cart.length) { window.toast('Add products to the cart first', 'error'); return; }
        if (this.discountInvalid) { window.toast(this.discountInvalid, 'error'); return; }
        this.cart.forEach((l) => this.normalize(l));
        this.tendered = ''; this.error = null; this.step = 'pay';
        this.$nextTick(() => this.$refs.tendered?.focus());
    },

    async checkout() {
        if (this.submitting) return; // duplicate-submission guard (server also enforces idempotency)
        if (this.change < 0) { this.error = 'Cash received is less than the total.'; return; }
        this.submitting = true; this.error = null;
        try {
            const r = await fetch(config.checkoutUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': config.csrf },
                body: JSON.stringify({
                    branch_id: this.branchId, idempotency_key: this.key,
                    items: this.cart.map((l) => ({ product_id: l.id, quantity: l.qty })),
                    discount_type: toPaisa(this.discountValue) ? this.discountType : null,
                    discount_value: this.discountValue || 0, amount_tendered: this.tendered || 0, customer_name: this.customer || null,
                }),
            });
            const data = await r.json().catch(() => ({}));
            if (r.status === 419) throw new Error('Your session expired. Please refresh the page and sign in again.');
            if (!r.ok) {
                this.lineErrors = data.errors && !Array.isArray(data.errors) ? Object.fromEntries(Object.entries(data.errors).filter(([k]) => /^\d+$/.test(k))) : {};
                const first = data.errors ? Object.values(data.errors).flat()[0] : null;
                throw new Error(data.message && !data.message.startsWith('The ') ? data.message : (first || data.message || 'Checkout failed'));
            }
            this.result = data;
            this.key = data.next_key;
            this.step = 'done';
            // reflect the deducted stock locally
            this.cart.forEach((l) => { const p = this.products.find((x) => x.id === l.id); if (p) p.stock = fmtQty(toMilli(p.stock) - toMilli(l.qty)); });
        } catch (e) {
            this.error = e.message;
            if (Object.keys(this.lineErrors).length) { this.step = 'cart'; window.toast(e.message, 'error'); this.load(); }
        } finally {
            this.submitting = false;
        }
    },

    printReceipt() { window.open(this.result.receipt_url + '?print=1', 'receipt', 'width=420,height=720'); },

    newSale() {
        this.cart = []; this.lineErrors = {}; this.discountValue = ''; this.customer = ''; this.tendered = '';
        this.result = null; this.step = 'cart'; this.cartOpen = false;
        this.$nextTick(() => this.$refs.search?.focus());
    },

    onSearchEnter() {
        const q = this.query.trim().toLowerCase();
        const exact = this.products.find((p) => p.sku.toLowerCase() === q || (p.barcode && p.barcode.toLowerCase() === q));
        const target = exact || this.filtered[this.kb];
        if (target) this.add(target);
    },

    hotkeys(e) {
        const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);
        if (e.key === 'F2' || (e.key === '/' && !typing)) { e.preventDefault(); this.step === 'cart' && this.$refs.search?.focus(); }
        else if (e.key === 'F9' || (e.ctrlKey && e.key === 'Enter')) { e.preventDefault(); this.step === 'cart' ? this.openPay() : this.step === 'pay' && this.checkout(); }
        else if (e.key === 'Escape') { if (this.step === 'pay' && !this.submitting) this.step = 'cart'; else if (this.step === 'done') this.newSale(); else this.cartOpen = false; }
        else if (e.key === 'F8') { e.preventDefault(); this.step === 'done' ? this.printReceipt() : this.clear(); }
        else if (document.activeElement === this.$refs.search) {
            if (e.key === 'ArrowDown') { e.preventDefault(); this.kb = Math.min(this.kb + 1, this.filtered.length - 1); }
            if (e.key === 'ArrowUp') { e.preventDefault(); this.kb = Math.max(this.kb - 1, 0); }
        }
    },
});
