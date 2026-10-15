@extends('layouts.app')

@section('content')

		<div class="page-title-head d-flex align-items-center ">
			<div class="flex-grow-1">
				<h4 class="fs-xl fw-bold m-0">{{ ucfirst(str_replace('-', ' ', $type)) }}</h4>
			</div>

			<div class="text-end">
				<ol class="breadcrumb m-0 py-0">
					<li class="breadcrumb-item"><a href="javascript: void(0);">Whole Trade</a></li>
					
					<li class="breadcrumb-item"><a href="javascript: void(0);">Home</a></li>
					
					<li class="breadcrumb-item active">{{ ucfirst(str_replace('-', ' ', $type)) }}</li>
				</ol>
			</div>
		</div>
                 

<div class="row mb-2">
    <div class="col-12">
        <div class="page-title-box">
            <div class="page-title-right">
                @if($canCreate)
                <a href="{{ route('master-data.create', $type) }}" class="btn btn-primary">Create New</a>
                @endif            </div>
           
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
                                @if($type === 'timezones')
                                    <th>Timezone</th>
                                @else
                                    <th>Name</th>
                                @endif

                                @if($type === 'currencies')
                                    <th>Code</th>
                                    <th>Is Default</th>
                                @endif
                                
                                @if($type === 'timezones')
                                    <th>Is Default</th>
                                @endif

                                @if($type === 'taxes')
                                    <th>Percentage</th>
                                    <th>Type</th>
                                @endif

                                @if($type === 'terms' || $type === 'incoterms')
                                    <th>No of Days</th>
                                @endif

                                @if($type !== 'timezones' && $type !== 'currencies')
                                    <th>Status</th>
                                @endif

                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $item)
                            <tr>
                                @if($type === 'timezones')
                                    <td>{{ $item->timezone }}</td>
                                @else
                                    <td>{{ $item->name }}</td>
                                @endif

                                @if($type === 'currencies')
                                    <td>{{ $item->code }}</td>
                                    <td>
                                        @if($item->is_default)
                                            <span class="badge bg-success">Default</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endif

                                @if($type === 'timezones')
                                    <td>
                                        @if($item->is_default)
                                            <span class="badge bg-success">Default</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endif

                                @if($type === 'taxes')
                                    <td>{{ $item->percentage }}%</td>
                                    <td>{{ ucfirst($item->type) }}</td>
                                @endif

                                @if($type === 'terms' || $type === 'incoterms')
                                    <td>{{ $item->no_of_days }}</td>
                                @endif

                                @if($type !== 'timezones' && $type !== 'currencies')
                                    <td>
                                        @if($item->status)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                @endif

                                <td>
                                    @if(auth()->user()->isSuperAdmin() || $item->subscriber_id)
                                        @if($canEdit)
                                        <a href="{{ route('master-data.edit', ['type' => $type, 'id' => $item->id]) }}" class="action-icon btn btn-secondary bg-gradient btn-icon"><i data-lucide="pencil"></i>  </a>
                                        @endif
                                        @if($canDelete)
                                            <form action="{{ route('master-data.destroy', ['type' => $type, 'id' => $item->id]) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-icon btn btn-danger bg-gradient btn-icon"><i data-lucide="trash-2"></i></button>
                                        </form>
                                            @endif
                                    @else
                                        <span class="text-muted"><i data-lucide="lock"></i> Global</span>
                                    @endif
                                </td>
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
