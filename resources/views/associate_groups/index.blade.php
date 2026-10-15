@extends('layouts.app')

@section('content')

		<div class="page-title-head d-flex align-items-center ">
			<div class="flex-grow-1">
				<h4 class="fs-xl fw-bold m-0">Associate Groups</h4>
			</div>

			<div class="text-end">
				<ol class="breadcrumb m-0 py-0">
					<li class="breadcrumb-item"><a href="javascript: void(0);">Whole Trade</a></li>
					<li class="breadcrumb-item"><a href="javascript: void(0);">Home</a></li>
					<li class="breadcrumb-item active">Associate Groups</li>
				</ol>
			</div>
		</div>

        @if($canCreate)
        <div class="row mb-2">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <a href="{{ route('associate-groups.create') }}" class="btn btn-primary">Create New</a>
                    </div>
                </div>
            </div>
        </div>
        @endif

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
                                        <th>Name</th>
                                        <th>Status</th>
                                        @if($canEdit || $canDelete)
                                        <th>Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($groups as $group)
                                    <tr>
                                        <td>{{ $group->name }}</td>
                                        <td>
                                            @if($group->status)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        @if($canEdit || $canDelete)
                                        <td>
                                            @if($canEdit)
                                            <a href="{{ route('associate-groups.edit', $group->id) }}" class="action-icon btn btn-secondary bg-gradient btn-icon"><i data-lucide="pencil"></i></a>
                                            @endif
                                            @if($canDelete)
                                            <form action="{{ route('associate-groups.destroy', $group->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-icon btn btn-danger bg-gradient btn-icon"><i data-lucide="trash-2"></i></button>
                                            </form>
                                            @endif
                                        </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection
