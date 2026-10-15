@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
             <h4 class="page-title">Edit {{ ucfirst(str_replace('-', ' ', $type)) }}</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('master-data.update', ['type' => $type, 'id' => $item->id]) }}" method="POST">
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

                   <!-- Common Name Field (Except Timezone) -->
                    @if($type !== 'timezones')
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $item->name) }}" required>
                    </div>
                    @endif

                    <!-- Currency Specific -->
                    @if($type === 'currencies')
                    <div class="mb-3">
                        <label for="code" class="form-label">Code</label>
                        <input type="text" class="form-control" id="code" name="code" value="{{ old('code', $item->code) }}" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_default" name="is_default" value="1" {{ old('is_default', $item->is_default) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_default">Set as Default</label>
                    </div>
                    @endif

                    <!-- Timezone Specific -->
                    @if($type === 'timezones')
                    <div class="mb-3">
                        <label for="timezone" class="form-label">Timezone</label>
                        <input type="text" class="form-control" id="timezone" name="timezone" value="{{ old('timezone', $item->timezone) }}" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_default" name="is_default" value="1" {{ old('is_default', $item->is_default) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_default">Set as Default</label>
                    </div>
                    @endif

                    <!-- Tax Specific -->
                    @if($type === 'taxes')
                    <div class="mb-3">
                        <label for="percentage" class="form-label">Percentage</label>
                        <input type="number" step="0.01" class="form-control" id="percentage" name="percentage" value="{{ old('percentage', $item->percentage) }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-control" id="type" name="type" required>
                            <option value="inclusive" {{ $item->type == 'inclusive' ? 'selected' : '' }}>Inclusive</option>
                            <option value="exclusive" {{ $item->type == 'exclusive' ? 'selected' : '' }}>Exclusive</option>
                        </select>
                    </div>
                    @endif

                    <!-- Terms/Incoterms Specific -->
                    @if($type === 'terms' || $type === 'incoterms')
                    <div class="mb-3">
                        <label for="no_of_days" class="form-label">No of Days</label>
                        <input type="number" class="form-control" id="no_of_days" name="no_of_days" value="{{ old('no_of_days', $item->no_of_days) }}" required>
                    </div>
                    @endif
                    
                    <!-- Common Status Field (Except Currency, Timezone) -->
                    @if($type !== 'currencies' && $type !== 'timezones')
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="status" name="status" value="1" {{ old('status', $item->status) ? 'checked' : '' }}>
                        <label class="form-check-label" for="status">Active</label>
                    </div>
                    @endif

                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('master-data.index', $type) }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
