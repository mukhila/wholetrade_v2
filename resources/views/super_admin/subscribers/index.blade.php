@extends('layouts.app')

@section('title', 'Manage Subscribers')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Subscribers</h1>
        <a href="{{ route('super_admin.subscribers.create') }}" class="btn btn-primary">Add Subscriber</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Plan</th>
                            <th>Users</th>
                            <th>Status</th>
                            <th>Expires</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscribers as $subscriber)
                        <tr>
                            <td>{{ $subscriber->id }}</td>
                            <td>
                                <a href="{{ route('super_admin.subscribers.show', $subscriber) }}">{{ $subscriber->name }}</a>
                            </td>
                            <td>{{ $subscriber->email }}</td>
                            <td><span class="badge bg-secondary">{{ ucfirst($subscriber->plan) }}</span></td>
                            <td>{{ $subscriber->users_count }}</td>
                            <td>
                                @if($subscriber->isActive())
                                    <span class="badge bg-success">Active</span>
                                @elseif($subscriber->isExpired())
                                    <span class="badge bg-danger">Expired</span>
                                @elseif($subscriber->status === 'suspended')
                                    <span class="badge bg-warning">Suspended</span>
                                @else
                                    <span class="badge bg-dark">{{ ucfirst($subscriber->status) }}</span>
                                @endif
                            </td>
                            <td>
                                {{ $subscriber->subscription_end_date->format('M d, Y') }}
                                @if($subscriber->daysUntilExpiry() <= 30 && $subscriber->daysUntilExpiry() > 0)
                                    <br><small class="text-warning">{{ $subscriber->daysUntilExpiry() }} days left</small>
                                @elseif($subscriber->isExpired())
                                    <br><small class="text-danger">Expired</small>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('super_admin.subscribers.edit', $subscriber) }}" class="btn btn-outline-primary">Edit</a>
                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('super_admin.subscribers.show', $subscriber) }}">View Details</a></li>
                                        <li><a class="dropdown-item" href="{{ route('super_admin.subscribers.users', $subscriber) }}">View Users</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        @if($subscriber->status === 'active')
                                        <li>
                                            <form action="{{ route('super_admin.subscribers.suspend', $subscriber) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-warning">Suspend</button>
                                            </form>
                                        </li>
                                        @else
                                        <li>
                                            <form action="{{ route('super_admin.subscribers.activate', $subscriber) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-success">Activate</button>
                                            </form>
                                        </li>
                                        @endif
                                        <li>
                                            <form action="{{ route('super_admin.subscribers.destroy', $subscriber) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this subscriber?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">Delete</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No subscribers found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($subscribers->hasPages())
        <div class="card-footer bg-white">
            {{ $subscribers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
