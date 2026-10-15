@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Total Outstanding Report</h4>
            <div class="d-flex gap-2">
                <a href="{{ route('supplier-reports.outstanding.export') }}" class="btn btn-outline-success no-print">
                    <i class="ti ti-file-download me-1"></i> Export CSV
                </a>
                <button onclick="window.print()" class="btn btn-outline-secondary no-print">
                    <i class="ti ti-printer me-1"></i> Print
                </button>
                <a href="{{ route('supplier-reports.ledger') }}" class="btn btn-outline-primary no-print">
                    Supplier Ledger
                </a>
            </div>
        </div>
    </div>
</div>

<div id="print-area">

    {{-- Print-only header (hidden on screen) --}}
    <div class="d-none d-print-block mb-3">
        @php $cs = \App\Models\CompanySetting::first(); @endphp
        <div class="text-center">
            <div style="font-size:16px; font-weight:bold; text-transform:uppercase;">
                {{ $cs->company_name ?? '' }}
            </div>
            @if($cs->address ?? null)
                <div style="font-size:11px;">{{ $cs->address }}</div>
            @endif
            @if($cs->phone_no ?? null)
                <div style="font-size:11px;">Tel: {{ $cs->phone_no }}</div>
            @endif
        </div>
        <hr style="border-top:2px solid #000; margin:6px 0;">
        <div class="text-center" style="font-size:14px; font-weight:bold; letter-spacing:1px;">
            SUPPLIER OUTSTANDING REPORT
        </div>
        <div class="text-end" style="font-size:10px; margin-top:2px;">
            Printed: {{ now()->format('d M Y, h:i A') }}
        </div>
        <hr style="border-top:1px solid #000; margin:4px 0 8px;">
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="outstandingTable">
                    <thead class="table-dark">
                        <tr>
                            <th>No.</th>
                            <th>Name of Supplier</th>
                            <th class="text-center">Total No. of Invoice</th>
                            <th class="text-end">Total Amount</th>
                            <th class="text-end">Total Paid</th>
                            <th class="text-end">Outstanding Balance</th>
                            <th class="text-center no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-medium">{{ $row->supplier_name }}</td>
                            <td class="text-center">{{ $row->total_invoices }}</td>
                            <td class="text-end">{{ number_format($row->total_amount, 2) }}</td>
                            <td class="text-end text-success">{{ number_format($row->total_paid, 2) }}</td>
                            <td class="text-end fw-bold text-danger">{{ number_format($row->total_balance, 2) }}</td>
                            <td class="text-center no-print">
                                <a href="{{ route('supplier-reports.ledger', ['supplier_id' => $row->supplier_id]) }}"
                                    class="btn btn-xs btn-sm btn-outline-primary py-0 px-2">
                                    View Ledger
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="ti ti-circle-check fs-3 d-block mb-2 text-success"></i>
                                No outstanding balances. All payments are up to date!
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($data->count() > 0)
                    <tfoot class="table-secondary">
                        <tr>
                            <td colspan="2" class="fw-bold">Grand Total</td>
                            <td class="text-center fw-bold">{{ $data->sum('total_invoices') }}</td>
                            <td class="text-end fw-bold">{{ number_format($data->sum('total_amount'), 2) }}</td>
                            <td class="text-end fw-bold text-success">{{ number_format($data->sum('total_paid'), 2) }}</td>
                            <td class="text-end fw-bold text-danger fs-6">{{ number_format($data->sum('total_balance'), 2) }}</td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

</div>{{-- /#print-area --}}

@push('styles')
<style>
@media print {
    .no-print { display: none !important; }
    .table-dark th { background-color: #343a40 !important; color: #fff !important; }
    .text-success { color: #198754 !important; }
    .text-danger  { color: #dc3545 !important; }
    .table-secondary { background-color: #e9ecef !important; }
    #print-area .card { border: none !important; box-shadow: none !important; }
    #print-area .card-body { padding: 0 !important; }
}
</style>
@endpush
@endsection
