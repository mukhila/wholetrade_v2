@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box">
             <h4 class="page-title">Sales Credit Notes</h4>
            <div class="page-title-right text-end">
                @if($canCreate)
                <a href="{{ route('credit-notes.create') }}" class="btn btn-primary">
                    <i data-lucide="plus" class="icon-sm me-1"></i> New Sales Credit Note
                </a>
                @endif
            </div>
           
        </div>
    </div>
</div>

<div class="row mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-centered table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Transaction #</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Currency</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($creditNotes as $note)
                            <tr>
                                <td>{{ $note->transaction_number }}</td>
                                <td>{{ $note->transaction_date }}</td>
                                <td>{{ $note->customer->name ?? 'N/A' }}</td>
                                <td>{{ $note->currency->code ?? 'N/A' }}</td>
                                <td>{{ number_format($note->grand_total, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $note->status === 'draft' ? 'secondary' : 'success' }}">
                                        {{ ucfirst($note->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('credit-notes.show', $note->id) }}" class="text-info me-2"><i data-lucide="eye" class="icon-sm"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No credit notes found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $creditNotes->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
