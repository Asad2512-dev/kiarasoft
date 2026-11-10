@extends('layouts.app', ['title' => 'Backups'])
@section('content')
<div class="page-head"><div><h1>Database backups</h1><p>Backups are stored privately in <span class="mono">storage/app/private/backups</span> and never served publicly.</p></div></div>
<div class="row g-3">
    <div class="col-lg-4">
        <form method="POST" action="{{ route('admin.backups.store') }}" class="card mb-3">@csrf
            <div class="card-header"><h2>Create backup</h2></div>
            <div class="card-body"><label class="form-label" for="notes">Note <span class="text-muted fw-normal">(optional)</span></label><input id="notes" name="notes" class="form-control mb-3" maxlength="255" placeholder="e.g. Before month-end"><button class="btn btn-primary w-100"><i class="bi bi-database-down"></i> Back up now</button></div>
        </form>
        <div class="card"><div class="card-body small text-muted">
            <strong class="text-dark d-block mb-1">Restore testing</strong>
            "Test restore" loads the backup into the separate <span class="mono">{{ config('kirasoft.restore_test_database') }}</span> database and verifies row counts. It never touches the live database.
        </div></div>
    </div>
    <div class="col-lg-8"><div class="card table-card">
        @if($backups->isEmpty())<x-empty icon="database" title="No backups yet" text="Create your first backup."/>@else
        <div class="table-responsive"><table class="table table-stack">
            <thead><tr><th>File</th><th>Created</th><th class="text-end">Size</th><th>Restore test</th><th></th></tr></thead>
            <tbody>@foreach($backups as $b)
                <tr>
                    <td class="cell-primary"><span class="mono small cell-title">{{ $b->filename }}</span>@if($b->notes)<div class="cell-sub">{{ $b->notes }}</div>@endif<div class="cell-sub mono" title="SHA-256">{{ substr($b->checksum, 0, 16) }}…</div></td>
                    <td data-label="Created" class="small">{{ $b->created_at->format('d M Y, h:i A') }}<div class="text-muted">{{ $b->creator?->name }}</div></td>
                    <td data-label="Size" class="text-end small">{{ number_format($b->size_bytes / 1024, 1) }} KB</td>
                    <td data-label="Restore test">@if($b->restore_tested_at)<span class="badge-soft badge-soft-success">Passed {{ $b->restore_tested_at->diffForHumans() }}</span>@else<span class="badge-soft badge-soft-secondary">Not tested</span>@endif</td>
                    <td class="cell-actions text-end text-nowrap">
                        <form method="POST" action="{{ route('admin.backups.test', $b) }}" class="d-inline" data-confirm="Restore this backup into the test database {{ config('kirasoft.restore_test_database') }}?">@csrf<button class="btn btn-sm btn-white">Test restore</button></form>
                        <a href="{{ route('admin.backups.download', $b) }}" class="btn btn-sm btn-white btn-icon" title="Download" aria-label="Download {{ $b->filename }}"><i class="bi bi-download"></i></a>
                    </td>
                </tr>
            @endforeach</tbody>
        </table></div>
        <div class="card-footer">{{ $backups->links() }}</div>@endif
    </div></div>
</div>
@endsection
