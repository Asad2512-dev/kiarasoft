@extends('layouts.app', ['title' => 'Shop settings'])
@section('content')
<div class="page-head"><div><h1>Shop settings</h1><p>Business details, receipts, tax and stock defaults.</p></div></div>
<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="card" data-confirm="These settings affect receipts, invoice numbers, tax and stock alerts for all branches." data-confirm-title="Save shop settings?" data-confirm-button="Save settings">
    @csrf @method('PUT')
    <div class="card-body">
        <div class="form-section row g-3">
            <div class="col-lg-4"><div class="section-title">Business profile</div><div class="section-help">Shown on receipts and reports.</div></div>
            <div class="col-lg-8"><div class="row g-3">
                <div class="col-sm-7"><label class="form-label" for="name">Shop name</label><input id="name" name="name" value="{{ old('name', $shop->name) }}" class="form-control" required maxlength="150"></div>
                <div class="col-sm-5"><label class="form-label" for="owner_name">Owner name</label><input id="owner_name" name="owner_name" value="{{ old('owner_name', $shop->owner_name) }}" class="form-control" required maxlength="120"></div>
                <div class="col-sm-6"><label class="form-label" for="email">E-mail</label><input id="email" type="email" name="email" value="{{ old('email', $shop->email) }}" class="form-control"></div>
                <div class="col-sm-6"><label class="form-label" for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone', $shop->phone) }}" class="form-control" inputmode="tel"></div>
                <div class="col-sm-8"><label class="form-label" for="address">Address</label><input id="address" name="address" value="{{ old('address', $shop->address) }}" class="form-control"></div>
                <div class="col-sm-4"><label class="form-label" for="city">City</label><input id="city" name="city" value="{{ old('city', $shop->city) }}" class="form-control"></div>
                <div class="col-12"><label class="form-label">Logo</label>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        @if($settings->logo_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings->logo_path) }}" alt="Current logo" style="height:56px;max-width:160px;object-fit:contain" class="border rounded p-1">
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo"><label class="form-check-label" for="remove_logo">Remove</label></div>@endif
                        <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="form-control" style="max-width: 320px" aria-label="Upload logo">
                    </div></div>
            </div></div>
        </div>
        <div class="form-section row g-3">
            <div class="col-lg-4"><div class="section-title">Receipts &amp; invoices</div><div class="section-help">Invoice numbers look like <span class="mono">{{ $settings->invoice_prefix }}-BRANCH-YYYYMMDD-0001</span>.</div></div>
            <div class="col-lg-8"><div class="row g-3">
                <div class="col-sm-4"><label class="form-label" for="currency">Currency</label><select id="currency" name="currency" class="form-select"><option value="PKR">PKR — Pakistani Rupee</option></select></div>
                <div class="col-sm-4"><label class="form-label" for="invoice_prefix">Invoice prefix</label><input id="invoice_prefix" name="invoice_prefix" value="{{ old('invoice_prefix', $settings->invoice_prefix) }}" class="form-control mono text-uppercase" required maxlength="6"></div>
                <div class="col-12"><label class="form-label" for="receipt_header">Receipt header</label><input id="receipt_header" name="receipt_header" value="{{ old('receipt_header', $settings->receipt_header) }}" class="form-control" maxlength="255"></div>
                <div class="col-12"><label class="form-label" for="receipt_footer">Receipt footer</label><input id="receipt_footer" name="receipt_footer" value="{{ old('receipt_footer', $settings->receipt_footer) }}" class="form-control" maxlength="255" placeholder="Thank you for shopping with us!"></div>
            </div></div>
        </div>
        <div class="form-section row g-3">
            <div class="col-lg-4"><div class="section-title">Tax, discounts &amp; stock</div><div class="section-help">Tax is applied to the subtotal after discount.</div></div>
            <div class="col-lg-8"><div class="row g-3">
                <div class="col-sm-4 d-flex align-items-end"><div class="form-check form-switch mb-2"><input type="hidden" name="tax_enabled" value="0"><input class="form-check-input" type="checkbox" role="switch" id="tax_enabled" name="tax_enabled" value="1" @checked(old('tax_enabled', $settings->tax_enabled))><label class="form-check-label" for="tax_enabled">Charge sales tax</label></div></div>
                <div class="col-sm-4"><label class="form-label" for="tax_rate">Tax rate</label><div class="input-group"><input id="tax_rate" name="tax_rate" type="number" step="0.01" min="0" max="50" value="{{ old('tax_rate', (float)$settings->tax_rate) }}" class="form-control"><span class="input-group-text">%</span></div></div>
                <div class="col-sm-4"><label class="form-label" for="max_discount_percent">Max discount</label><div class="input-group"><input id="max_discount_percent" name="max_discount_percent" type="number" step="0.01" min="0" max="100" value="{{ old('max_discount_percent', (float)$settings->max_discount_percent) }}" class="form-control"><span class="input-group-text">%</span></div></div>
                <div class="col-sm-6"><label class="form-label" for="default_low_stock_threshold">Default low-stock threshold</label><input id="default_low_stock_threshold" name="default_low_stock_threshold" type="number" step="0.001" min="0" value="{{ old('default_low_stock_threshold', (float)$settings->default_low_stock_threshold) }}" class="form-control"><div class="form-text">Used when a product has no own threshold.</div></div>
            </div></div>
        </div>
        <div class="sticky-actions"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save settings</button></div>
    </div>
</form>
@endsection
