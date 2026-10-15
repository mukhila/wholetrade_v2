@extends('layouts.app')

@section('title', 'Subscription Expired')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-5">
                    <div class="mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" class="bi bi-exclamation-circle text-warning" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                        </svg>
                    </div>

                    <h2 class="mb-3">Subscription Expired</h2>
                    <p class="text-muted mb-4">
                        Your subscription has expired. Please contact your administrator or support to renew your subscription.
                    </p>

                    @if(auth()->user()->subscriber)
                    <div class="alert alert-light mb-4">
                        <p class="mb-1"><strong>Subscription ended:</strong></p>
                        <p class="mb-0">{{ auth()->user()->subscriber->subscription_end_date->format('F d, Y') }}</p>
                    </div>
                    @endif

                    <div class="d-grid gap-2">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">Logout</button>
                        </form>
                    </div>

                    <hr class="my-4">

                    <p class="text-muted small mb-0">
                        Need help? Contact support at <a href="mailto:support@example.com">support@example.com</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
