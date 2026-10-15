@extends('layouts.app')

@section('title', 'Subscriber Details')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $subscriber->name }}</h4>
                    <a href="{{ route('super_admin.subscribers.edit', $subscriber) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Email:</strong> {{ $subscriber->email }}</p>
                            <p><strong>Phone:</strong> {{ $subscriber->phone ?? 'N/A' }}</p>
                            <p><strong>Address:</strong> {{ $subscriber->address ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Plan:</strong> <span class="badge bg-secondary">{{ ucfirst($subscriber->plan) }}</span></p>
                            <p>
                                <strong>Status:</strong>
                                @if($subscriber->isActive())
                                    <span class="badge bg-success">Active</span>
                                @elseif($subscriber->isExpired())
                                    <span class="badge bg-danger">Expired</span>
                                @else
                                    <span class="badge bg-warning">{{ ucfirst($subscriber->status) }}</span>
                                @endif
                            </p>
                            <p><strong>Subscription Period:</strong><br>
                                {{ $subscriber->subscription_start_date->format('M d, Y') }} - {{ $subscriber->subscription_end_date->format('M d, Y') }}
                            </p>
                            <p><strong>Days Remaining:</strong>
                                @if($subscriber->isExpired())
                                    <span class="text-danger">Expired</span>
                                @else
                                    <span class="text-success">{{ $subscriber->daysUntilExpiry() }} days</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Users ({{ $subscriber->users->count() }})</h5>
                    <a href="{{ route('super_admin.subscribers.users', $subscriber) }}" class="btn btn-sm btn-outline-secondary">Manage Users</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subscriber->users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if($user->is_subscriber_admin)
                                            <span class="badge bg-primary">Admin</span>
                                        @else
                                            <span class="badge bg-secondary">User</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No users found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('super_admin.subscribers.extend', $subscriber) }}" method="POST" class="mb-3">
                        @csrf
                        <label class="form-label">Extend Subscription</label>
                        <div class="input-group">
                            <input type="number" name="days" class="form-control" value="30" min="1">
                            <button type="submit" class="btn btn-success">Extend</button>
                        </div>
                    </form>

                    <hr>

                    @if($subscriber->status === 'active')
                    <form action="{{ route('super_admin.subscribers.suspend', $subscriber) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100 mb-2">Suspend Subscriber</button>
                    </form>
                    @else
                    <form action="{{ route('super_admin.subscribers.activate', $subscriber) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success w-100 mb-2">Activate Subscriber</button>
                    </form>
                    @endif

                    <form action="{{ route('super_admin.subscribers.destroy', $subscriber) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">Delete Subscriber</button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Subscription Info</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Created:</strong></p>
                    <p class="text-muted">{{ $subscriber->created_at->format('M d, Y H:i') }}</p>

                    <p class="mb-1"><strong>Last Updated:</strong></p>
                    <p class="text-muted mb-0">{{ $subscriber->updated_at->format('M d, Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
