@extends('layouts.app', ['title' => 'System health'])
@section('content')
@php $errors_ = collect($checks)->where('level', 'error')->count(); $warn = collect($checks)->where('level', 'warning')->count(); @endphp
<div class="page-head"><div><h1>System health</h1><p>Configuration and connectivity diagnostics. Also available as <span class="mono">php artisan kirasoft:health</span>.</p></div>
    <div class="actions"><a href="{{ route('admin.system') }}" class="btn btn-white"><i class="bi bi-arrow-clockwise"></i> Re-run checks</a></div></div>
<div class="alert {{ $errors_ ? 'alert-danger' : ($warn ? 'alert-warning' : 'alert-success') }} d-flex align-items-center gap-2"><i class="bi bi-{{ $errors_ ? 'x-octagon' : ($warn ? 'exclamation-triangle' : 'check-circle') }}-fill fs-5"></i>
    {{ $errors_ ? "$errors_ critical issue(s) need attention." : ($warn ? "System operational with $warn warning(s)." : 'All checks passed.') }}</div>
<div class="card table-card"><table class="table table-stack">
    <thead><tr><th>Check</th><th>Result</th><th>Details</th></tr></thead>
    <tbody>@foreach($checks as $c)
        <tr><td class="cell-primary cell-title">{{ $c['name'] }}</td>
            <td data-label="Result"><span class="badge-soft badge-soft-{{ ['ok' => 'success', 'warning' => 'warning', 'error' => 'danger'][$c['level']] }}"><i class="bi bi-{{ $c['ok'] ? 'check' : 'x' }}-lg"></i> {{ $c['ok'] ? 'OK' : ($c['level'] === 'error' ? 'Failed' : 'Warning') }}</span></td>
            <td data-label="Details" class="small text-muted">{{ $c['detail'] }}</td></tr>
    @endforeach</tbody>
</table></div>
<div class="card mt-3"><div class="card-body small text-muted">Application errors are logged to <span class="mono">storage/logs/laravel.log</span>. Health endpoint for monitoring: <a href="{{ url('/up') }}" class="mono">{{ url('/up') }}</a>.</div></div>
@endsection
