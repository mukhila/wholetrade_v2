@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <div class="page-title-right">
                @if($canCreate)
                <a href="{{ route('admin.debit-notes.create') }}" class="btn btn-primary">
                    <i data-lucide="plus" class="icon-sm me-1"></i> New Debit Note
                </a>
                @endif
            </div>
            <h4 class="page-title">Supplier Debit Notes</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-centered table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Transaction #</th>
                                <th>Date</th>
                                <th>Supplier</th>
                                <th>Currency</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($debitNotes as $note)
                            <tr>
                                <td>{{ $note->transaction_number }}</td>
                                <td>{{ $note->transaction_date }}</td>
                                <td>{{ $note->supplier->name ?? 'N/A' }}</td>
                                <td>{{ $note->currency->code ?? 'N/A' }}</td>
                                <td>{{ number_format($note->net_total, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $note->status === 'draft' ? 'secondary' : 'success' }}">
                                        {{ ucfirst($note->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.debit-notes.show', $note->id) }}" class="text-info me-2"><i data-lucide="eye" class="icon-sm"></i></a>
                                    @if($note->status === 'draft' && $canDelete)
                                        <a href="javascript:void(0);" onclick="deleteItem({{ $note->id }})" class="text-danger"><i data-lucide="trash-2" class="icon-sm"></i></a>
                                        <form id="delete-form-{{ $note->id }}" action="{{ route('admin.debit-notes.destroy', $note->id) }}" method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No debit notes found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $debitNotes->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function deleteItem(id) {
        if(confirm('Are you sure you want to delete this debit note?')) {
            const form = document.getElementById('delete-form-' + id);
            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error deleting item');
                }
            });
        }
    }
</script>

@vite(['resources/js/components/Expenses/CreditNoteForm.jsx'])
@endpush
