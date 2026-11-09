@extends('layouts.app', ['title' => 'Signup requests'])
@section('content')
<div class="page-head"><div><h1>Signup requests</h1><p>Shops that requested an account through the public registration form.</p></div></div>
<div class="card table-card">
    <div class="card-header"><ul class="nav nav-tabs-clean w-100">@foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $v)<li class="nav-item"><a class="nav-link {{ $status === $k ? 'active' : '' }}" href="{{ route('admin.signups.index', ['status' => $k]) }}">{{ $v }}</a></li>@endforeach</ul></div>
    @if($requests->isEmpty())<x-empty icon="inbox" title="No {{ $status }} requests"/>@else
    <div class="table-responsive"><table class="table table-stack">
        <thead><tr><th>Shop</th><th>Contact</th><th>City</th><th>Message</th><th>Received</th><th></th></tr></thead>
        <tbody>@foreach($requests as $r)
            <tr>
                <td class="cell-primary"><span class="cell-title">{{ $r->shop_name }}</span><div class="cell-sub">{{ $r->owner_name }}</div></td>
                <td data-label="Contact" class="small">{{ $r->email }}<br class="d-none d-md-inline"> {{ $r->phone }}</td>
                <td data-label="City">{{ $r->city ?: '—' }}</td>
                <td data-label="Message" class="small text-muted" style="max-width: 260px">{{ $r->message ?: '—' }}</td>
                <td data-label="Received" class="small">{{ $r->created_at->diffForHumans() }}@if($r->reviewer)<div class="text-muted">by {{ $r->reviewer->name }}</div>@endif</td>
                <td class="cell-actions text-end text-nowrap">
                    @if($r->status === 'pending')
                        <form method="POST" action="{{ route('admin.signups.approve', $r) }}" class="d-inline" data-confirm="Approve and create {{ $r->shop_name }}?">@csrf<button class="btn btn-sm btn-primary"><i class="bi bi-check-lg"></i> Approve</button></form>
                        <form method="POST" action="{{ route('admin.signups.reject', $r) }}" class="d-inline" data-confirm="Reject this request?">@csrf<button class="btn btn-sm btn-soft-danger">Reject</button></form>
                    @elseif($r->shop_id)<a href="{{ route('admin.shops.show', $r->shop_id) }}" class="btn btn-sm btn-white">View shop</a>
                    @else<span class="small text-muted">{{ $r->review_note }}</span>@endif
                </td>
            </tr>
        @endforeach</tbody>
    </table></div>
    <div class="card-footer">{{ $requests->links() }}</div>@endif
</div>
@endsection
