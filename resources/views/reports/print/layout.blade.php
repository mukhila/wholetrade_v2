<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Report')</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { padding: 2px 5px; border-radius: 3px; font-size: 10px; color: #fff; }
        .bg-success { background-color: #28a745; }
        .bg-danger { background-color: #dc3545; }
        .bg-warning { background-color: #ffc107; color: #333; }
        .bg-info { background-color: #17a2b8; }
        .bg-primary { background-color: #007bff; }
        .bg-secondary { background-color: #6c757d; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            @page { margin: 0.5cm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 20px; text-align: right; display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
        @yield('extra_buttons')
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">Print Report</button>
    </div>
    <div class="header">
        <h1>@yield('title')</h1>
        <p>Generated on: {{ now()->format('Y-m-d H:i:s') }}</p>
        @if(isset($filters) && count($filters) > 0)
            <p>
                Filters:
                @foreach($filters as $key => $value)
                    @if($value && $key !== 'page' && $key !== 'per_page' && $key !== 'print')
                        @php
                            $displayValue = $value;
                            if ($key === 'customer_id' && is_numeric($value)) {
                                $customer = \App\Models\Associate::find($value);
                                $displayValue = $customer ? $customer->name : $value;
                            } elseif ($key === 'salesperson_id' && is_numeric($value)) {
                                $user = \App\Models\User::find($value);
                                $displayValue = $user ? $user->name : $value;
                            }
                        @endphp
                        <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $displayValue }} &nbsp;
                    @endif
                @endforeach
            </p>
        @endif
    </div>
    @yield('content')
    <div class="footer">
        <p>&copy; {{ date('Y') }} Whole Trade. All rights reserved.</p>
    </div>
</body>
</html>
