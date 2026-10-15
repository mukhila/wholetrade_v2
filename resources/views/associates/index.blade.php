@extends('layouts.app')

@section('content')

		<div class="page-title-head d-flex align-items-center ">
			<div class="flex-grow-1">
				<h4 class="fs-xl fw-bold m-0">Associates</h4>
			</div>

			<div class="text-end">
				<ol class="breadcrumb m-0 py-0">
					<li class="breadcrumb-item"><a href="javascript: void(0);">Whole Trade</a></li>
					
					<li class="breadcrumb-item"><a href="javascript: void(0);">Home</a></li>
					
					<li class="breadcrumb-item active">Associates</li>
				</ol>
			</div>
		</div>

<div class="row mb-2">
    <div class="col-sm-4">
        <form action="{{ route('associates.index') }}" method="GET">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search by name, code, email..." value="{{ request('search') }}">
                <button class="btn btn-primary" type="submit"><i data-lucide="search" class="icon-sm"></i></button>
                @if(request('search'))
                    <a href="{{ route('associates.index') }}" class="btn btn-secondary"><i data-lucide="x" class="icon-sm"></i></a>
                @endif
            </div>
        </form>
    </div>
    <div class="col-sm-8">
        <div class="text-sm-end d-flex gap-2 justify-content-end">
            <a href="{{ route('associates.export') }}" class="btn btn-success"><i data-lucide="download" class="icon-sm me-1"></i> Export</a>
            @if($canCreate)
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#importModal"><i data-lucide="upload" class="icon-sm me-1"></i> Import</button>
            <a href="{{ route('associates.create') }}" class="btn btn-primary">Create New</a>
            @endif
        </div>
    </div>
</div>

{{-- Import Modal --}}
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import Associates</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('associates.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3">Upload a CSV file to bulk-import associates. Download the template to see the required format.</p>
                    <div class="mb-3">
                        <label for="csv_file" class="form-label fw-semibold">CSV File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv,.txt" required>
                    </div>
                    <a href="{{ route('associates.import.template') }}" class="btn btn-outline-secondary btn-sm"><i data-lucide="file-down" class="icon-sm me-1"></i> Download Template</a>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i data-lucide="upload" class="icon-sm me-1"></i> Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-centered table-nowrap mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Group</th>
                                <th>Salesman</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($associates as $associate)
                            <tr>
                                <td>{{ $associate->code }}</td>
                                <td>{{ $associate->name }}</td>
                                <td><span class="badge bg-{{ $associate->type == 'Customer' ? 'info' : 'warning' }}">{{ $associate->type }}</span></td>
                                <td>{{ $associate->email }}</td>
                                <td>{{ $associate->phone }}</td>
                                <td>{{ $associate->associateGroup->name ?? '-' }}</td>
                                <td>{{ $associate->salesman->name ?? '-' }}</td>
                                <td>
                                    @if($associate->status)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('associates.show', $associate->id) }}" class="action-icon btn btn-info bg-gradient btn-icon"><i data-lucide="eye"></i></a>
                                    @if($canEdit)
                                    <a href="{{ route('associates.edit', $associate->id) }}" class="action-icon btn btn-secondary bg-gradient btn-icon"><i data-lucide="pencil"></i></a>
                                    @endif
                                    @if($canEdit)
                                    <form action="{{ route('associates.toggle-status', $associate->id) }}" method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('PATCH')
                                        @if($associate->status)
                                            <button type="submit" class="action-icon btn btn-warning bg-gradient btn-icon" title="Set Inactive"><i data-lucide="toggle-right"></i></button>
                                        @else
                                            <button type="submit" class="action-icon btn btn-success bg-gradient btn-icon" title="Set Active"><i data-lucide="toggle-left"></i></button>
                                        @endif
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $associates->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
