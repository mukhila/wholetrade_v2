@extends('layouts.app')

@section('content')

<div class="page-title-head d-flex align-items-center">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">Product Aliases</h4>
    </div>
    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item active">Product Aliases</li>
        </ol>
    </div>
</div>

<div class="row mb-2">
    <div class="col-12">
        <div class="page-title-box">
            <div class="page-title-right">
                @if($canCreate)
                <a href="{{ route('product-aliases.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> Add Alias
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('product-aliases.index') }}" class="row g-2 mb-4">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search"
                            value="{{ request('search') }}"
                            placeholder="Search alias name, SKU or product name…">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-secondary">Search</button>
                        @if(request('search'))
                            <a href="{{ route('product-aliases.index') }}" class="btn btn-outline-secondary ms-1">Clear</a>
                        @endif
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Alias Name</th>
                                <th>Alias SKU</th>
                                <th>Parent Product</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($aliases as $alias)
                            <tr>
                                <td>{{ $aliases->firstItem() + $loop->index }}</td>
                                <td><strong>{{ $alias->alias_name }}</strong></td>
                                <td>{{ $alias->alias_sku ?? '—' }}</td>
                                <td>
                                    @if($alias->product)
                                        <a href="{{ route('products.edit', $alias->product_id) }}">
                                            {{ $alias->product->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $alias->description ?? '—' }}</td>
                                <td>
                                    @if($alias->status)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('product-aliases.edit', $alias->id) }}"
                                       class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                    <form method="POST"
                                          action="{{ route('product-aliases.destroy', $alias->id) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete alias \'{{ addslashes($alias->alias_name) }}\'?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No aliases found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $aliases->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
