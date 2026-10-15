@extends('layouts.app')

@section('title', 'Super Admin Dashboard')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Super Admin Dashboard</h1>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Subscribers</h6>
                    <h2 class="mb-0">{{ $stats['total'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100 bg-success text-white">
                <div class="card-body">
                    <h6 class="mb-2 opacity-75">Active Subscribers</h6>
                    <h2 class="mb-0">{{ $stats['active'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100 bg-danger text-white">
                <div class="card-body">
                    <h6 class="mb-2 opacity-75">Expired</h6>
                    <h2 class="mb-0">{{ $stats['expired'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100 bg-warning">
                <div class="card-body">
                    <h6 class="mb-2 opacity-75">Expiring in 7 Days</h6>
                    <h2 class="mb-0">{{ $stats['expiring_7_days'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Expiring Soon -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Expiring in 30 Days</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Plan</th>
                                    <th>Expires</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expiringSubscribers as $subscriber)
                                <tr>
                                    <td>{{ $subscriber->name }}</td>
                                    <td><span class="badge bg-secondary">{{ ucfirst($subscriber->plan) }}</span></td>
                                    <td>
                                        <span class="text-danger">{{ $subscriber->subscription_end_date->format('M d, Y') }}</span>
                                        <br><small class="text-muted">{{ $subscriber->daysUntilExpiry() }} days left</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('super_admin.subscribers.edit', $subscriber) }}" class="btn btn-sm btn-outline-primary">Extend</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No subscribers expiring soon</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Subscribers -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Subscribers</h5>
                    <a href="{{ route('super_admin.subscribers.create') }}" class="btn btn-sm btn-primary">Add New</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Plan</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSubscribers as $subscriber)
                                <tr>
                                    <td>
                                        <a href="{{ route('super_admin.subscribers.show', $subscriber) }}">{{ $subscriber->name }}</a>
                                    </td>
                                    <td><span class="badge bg-secondary">{{ ucfirst($subscriber->plan) }}</span></td>
                                    <td>
                                        @if($subscriber->isActive())
                                            <span class="badge bg-success">Active</span>
                                        @elseif($subscriber->isExpired())
                                            <span class="badge bg-danger">Expired</span>
                                        @else
                                            <span class="badge bg-warning">{{ ucfirst($subscriber->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $subscriber->created_at->format('M d, Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No subscribers yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0">
                    <a href="{{ route('super_admin.subscribers.index') }}" class="text-decoration-none">View all subscribers &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
