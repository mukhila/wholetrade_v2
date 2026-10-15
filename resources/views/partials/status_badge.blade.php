@props(['status'])

@php
    $color = 'secondary';
    switch(strtolower($status)) {
        case 'draft':
            $color = 'secondary'; // or warning
            break;
        case 'submitted':
        case 'pending':
        case 'waiting_for_approval':
            $color = 'warning';
            break;
        case 'approved':
        case 'verified':
        case 'converted':
        case 'completed':
        case 'fully_received':
        case 'confirmed':
        case 'posted':
            $color = 'success';
            break;
        case 'partially_received':
        case 'unified':
            $color = 'info';
            break;
        case 'rejected':
        case 'cancelled':
        case 'expired':
            $color = 'danger';
            break;
        case 'voided':
            $color = 'dark';
            break;
        case 'closed':
            $color = 'dark';
            break;
        default:
            $color = 'secondary';
    }
@endphp

<span class="badge bg-{{ $color }} text-uppercase">{{ str_replace('_', ' ', $status) }}</span>
