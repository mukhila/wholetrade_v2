@extends('layouts.app')

@section('content')

		<div class="page-title-head d-flex align-items-center ">
            <div class="flex-grow-1">
                <h4 class="fs-xl fw-bold m-0">Product Labels</h4>
            </div>

            <div class="text-end">
                <ol class="breadcrumb m-0 py-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">Whole Trade</a></li>
                    <li class="breadcrumb-item active">Product Labels</li>
                </ol>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        @if($canCreate)
                <a href="{{ route('product-labels.create') }}" class="btn btn-primary">Create New</a>
                @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-centered table-nowrap mb-0">
                                <thead>
                                    <tr>
                                        <th>Label Name</th>
                                        <th>Dimensions</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($labels as $label)
                                    <tr>
                                        <td>{{ $label->label_name }}</td>
                                        <td>{{ $label->width }} x {{ $label->height }} {{ $label->unit }}</td>
                                        <td>
                                            @if($label->status)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($canEdit)
                                            <a href="{{ route('product-labels.edit', $label->id) }}" class="action-icon btn btn-secondary bg-gradient btn-icon"><i data-lucide="pencil"></i></a>
                                            @endif
                                            @if($canDelete)
                                            <form action="{{ route('product-labels.destroy', $label->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-icon btn btn-danger bg-gradient btn-icon"><i data-lucide="trash-2"></i></button>
                                            </form>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $labels->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection
