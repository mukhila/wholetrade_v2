@extends('layouts.app')

@section('content')

		<div class="page-title-head d-flex align-items-center">
			<div class="flex-grow-1">
				<h4 class="fs-xl fw-bold m-0">Edit Associate Group</h4>
			</div>

			<div class="text-end">
				<ol class="breadcrumb m-0 py-0">
					<li class="breadcrumb-item"><a href="javascript: void(0);">Whole Trade</a></li>
					
					<li class="breadcrumb-item"><a href="javascript: void(0);">Home</a></li>
					
					<li class="breadcrumb-item active">Associate Groups</li>
				</ol>
			</div>
		</div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('associate-groups.update', $group->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label for="name" class="form-label">Associate Group Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $group->name) }}" required>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="status" name="status" value="1" {{ old('status', $group->status) ? 'checked' : '' }}>
                                <label class="form-check-label" for="status">Active</label>
                            </div>

                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="{{ route('associate-groups.index') }}" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
@endsection
