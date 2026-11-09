@extends('layouts.app', ['title' => 'Categories'])
@section('content')
<div x-data="{ edit: null, open(c) { this.edit = c; $nextTick(() => bootstrap.Modal.getOrCreateInstance($refs.modal).show()) } }">
<div class="page-head">
    <div><h1>Categories</h1><p>Group products to make the POS faster to browse.</p></div>
    <div class="actions"><button class="btn btn-primary" @click="open({ id: null, name: '', description: '', is_active: true })"><i class="bi bi-plus-lg"></i> Add category</button></div>
</div>
<div class="card table-card">
    <div class="card-header">
        <ul class="nav nav-tabs-clean w-100 mb-2">
            @foreach(['' => 'Active & inactive', 'inactive' => 'Inactive', 'archived' => 'Archived'] as $k => $v)
                <li class="nav-item"><a class="nav-link {{ request('status', '') === $k ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => $k ?: null, 'page' => null]) }}">{{ $v }}</a></li>
            @endforeach
        </ul>
        <form class="filter-bar w-100" method="GET"><input type="hidden" name="status" value="{{ request('status') }}"><div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search categories" aria-label="Search categories"></div><button class="btn btn-white" data-no-lock>Search</button></form>
    </div>
    @if($categories->isEmpty())
        <x-empty icon="tags" title="No categories" text="Create categories such as Rice & Flour, Tea, Spices or Cleaning."/>
    @else
    <div class="table-responsive"><table class="table table-hover table-stack">
        <thead><tr><th>Name</th><th>Description</th><th class="text-center">Active products</th><th>Status</th><th></th></tr></thead>
        <tbody>@foreach($categories as $c)
            <tr>
                <td class="cell-primary cell-title">{{ $c->name }}</td>
                <td data-label="Description" class="text-muted">{{ $c->description ?: '—' }}</td>
                <td data-label="Products" class="text-md-center"><a href="{{ route('products.index', ['category' => $c->id]) }}">{{ $c->products_count }}</a></td>
                <td data-label="Status">@if($c->trashed())<span class="badge-soft badge-soft-secondary">Archived</span>@else<x-active-badge :active="$c->is_active"/>@endif</td>
                <td class="cell-actions text-end text-nowrap">
                    @if($c->trashed())
                        <form method="POST" action="{{ route('categories.restore', $c->id) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-soft-primary">Restore</button></form>
                    @else
                        <button class="btn btn-sm btn-white" @click="open({{ json_encode($c->only(['id','name','description','is_active'])) }})"><i class="bi bi-pencil"></i> Edit</button>
                        <form method="POST" action="{{ route('categories.archive', $c) }}" class="d-inline" data-confirm="Archive category {{ $c->name }}?">@csrf @method('DELETE')<button class="btn btn-sm btn-white btn-icon" title="Archive" aria-label="Archive {{ $c->name }}"><i class="bi bi-archive"></i></button></form>
                    @endif
                </td>
            </tr>
        @endforeach</tbody>
    </table></div>
    <div class="card-footer">{{ $categories->links() }}</div>
    @endif
</div>

<div class="modal fade" tabindex="-1" x-ref="modal" aria-labelledby="catTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form method="POST" :action="edit && edit.id ? @js(url('categories')) + '/' + edit.id : @js(route('categories.store'))">
            @csrf <template x-if="edit && edit.id"><input type="hidden" name="_method" value="PUT"></template>
            <div class="modal-header"><h2 class="modal-title h5" id="catTitle" x-text="edit && edit.id ? 'Edit category' : 'New category'"></h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label" for="cname">Name</label><input id="cname" name="name" class="form-control" :value="edit?.name" required maxlength="100"></div>
                <div class="mb-3"><label class="form-label" for="cdesc">Description <span class="text-muted fw-normal">(optional)</span></label><input id="cdesc" name="description" class="form-control" :value="edit?.description" maxlength="255"></div>
                <div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" role="switch" id="cactive" name="is_active" value="1" :checked="edit?.is_active"><label class="form-check-label" for="cactive">Active</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-white" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
        </form>
    </div></div>
</div>
</div>
@endsection
