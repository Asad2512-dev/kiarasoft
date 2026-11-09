<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Receipt {{ $sale->invoice_no }}</title>
<style>
    /* Thermal-receipt stylesheet: 80mm paper (works on 58mm too) */
    @page { size: 80mm auto; margin: 3mm; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #eef1f5; font: 12px/1.45 ui-monospace, 'SF Mono', Menlo, Consolas, 'Courier New', monospace; color: #000; }
    .paper { width: 80mm; max-width: 100%; margin: 16px auto; background: #fff; padding: 14px 12px 18px; box-shadow: 0 8px 30px rgba(0,0,0,.12); }
    .c { text-align: center; } .r { text-align: right; } .b { font-weight: 700; }
    .shop { font-size: 16px; font-weight: 800; letter-spacing: .02em; }
    .logo { max-width: 46mm; max-height: 18mm; display: block; margin: 0 auto 4px; filter: grayscale(1); }
    hr { border: 0; border-top: 1px dashed #000; margin: 7px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { vertical-align: top; padding: 1px 0; }
    .item td { padding-top: 3px; }
    .total { font-size: 15px; font-weight: 800; }
    .muted { color: #333; font-size: 11px; }
    .actions { text-align: center; margin: 12px auto 24px; font-family: system-ui, sans-serif; }
    .actions button, .actions a { font: 600 14px system-ui, sans-serif; padding: 9px 18px; border-radius: 8px; border: 1px solid #cfd6df; background: #fff; color: #1d2939; cursor: pointer; text-decoration: none; margin: 0 4px; }
    .actions .primary { background: #059669; border-color: #059669; color: #fff; }
    @media print { body { background: #fff; } .paper { margin: 0; box-shadow: none; width: auto; padding: 0; } .actions { display: none; } }
</style>
</head>
<body>
<div class="paper">
    <div class="c">
        @if($settings->logo_path)<img class="logo" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings->logo_path) }}" alt="">@endif
        <div class="shop">{{ $settings->receipt_header ?: $sale->shop->name }}</div>
        <div class="muted">{{ $sale->branch->name }}@if($sale->branch->address)<br>{{ $sale->branch->address }}@endif
            @if($sale->branch->phone ?: $sale->shop->phone)<br>Tel: {{ $sale->branch->phone ?: $sale->shop->phone }}@endif</div>
    </div>
    <hr>
    <table class="muted">
        <tr><td>Invoice</td><td class="r b">{{ $sale->invoice_no }}</td></tr>
        <tr><td>Date</td><td class="r">{{ $sale->sold_at->format('d/m/Y h:i A') }}</td></tr>
        <tr><td>Cashier</td><td class="r">{{ $sale->cashier->name }}</td></tr>
        @if($sale->customer_name)<tr><td>Customer</td><td class="r">{{ $sale->customer_name }}</td></tr>@endif
    </table>
    <hr>
    <table>
        @foreach($sale->items as $i)
            <tr class="item"><td colspan="2" class="b">{{ $i->product_name }}</td></tr>
            <tr><td class="muted">@qty($i->quantity) {{ $i->unit }} × {{ number_format((float)$i->unit_price, 2) }}</td><td class="r">{{ number_format((float)$i->line_total, 2) }}</td></tr>
        @endforeach
    </table>
    <hr>
    <table>
        <tr><td>Subtotal</td><td class="r">{{ number_format((float)$sale->subtotal, 2) }}</td></tr>
        @if((float)$sale->discount_amount > 0)<tr><td>Discount{{ $sale->discount_type === 'percent' ? ' ('.(float)$sale->discount_value.'%)' : '' }}</td><td class="r">-{{ number_format((float)$sale->discount_amount, 2) }}</td></tr>@endif
        @if((float)$sale->tax_amount > 0)<tr><td>Tax ({{ (float)$sale->tax_rate }}%)</td><td class="r">{{ number_format((float)$sale->tax_amount, 2) }}</td></tr>@endif
        <tr class="total"><td>TOTAL ({{ $settings->currency }})</td><td class="r">{{ number_format((float)$sale->total, 2) }}</td></tr>
        <tr><td>Cash</td><td class="r">{{ number_format((float)$sale->payment->amount_tendered, 2) }}</td></tr>
        <tr><td>Change</td><td class="r">{{ number_format((float)$sale->payment->change_due, 2) }}</td></tr>
        @if((float)$sale->refunded_total > 0)<tr><td>Refunded</td><td class="r">-{{ number_format((float)$sale->refunded_total, 2) }}</td></tr>@endif
    </table>
    <hr>
    <div class="c muted">{{ $sale->items->count() }} item(s)<br>{{ $settings->receipt_footer ?: 'Thank you for shopping with us!' }}</div>
    <div class="c muted" style="margin-top:6px; font-size:10px">Powered by KiraSoft</div>
</div>
<div class="actions">
    <button class="primary" onclick="window.print()">Print</button>
    <a href="{{ route('sales.show', $sale) }}">View invoice</a>
</div>
@if($autoprint)<script>window.addEventListener('load', () => setTimeout(() => window.print(), 250));</script>@endif
</body>
</html>
