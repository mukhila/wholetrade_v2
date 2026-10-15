<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Summary Print</title>
    <style>
        @font-face {
            font-family: 'Noto Sans';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('/assets/fonts/NotoSans-Regular.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Noto Sans';
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url('/assets/fonts/NotoSans-Bold.woff2') format('woff2');
        }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            @page { margin: 10mm 12mm; size: A4; }
            body { font-family: 'Noto Sans', Arial, sans-serif; font-size: 10px; line-height: 1.5; }
            .no-print { display: none !important; }
        }
        body {
            font-family: 'Noto Sans', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 10px;
            line-height: 1.5;
            color: #000;
        }
        .header-section { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .company-details { text-align: center; width: 100%; }
        .company-name { font-weight: bold; font-size: 18px; text-transform: uppercase; margin-bottom: 4px; letter-spacing: 1px; }
        .bill-to-section { margin-bottom: 16px; font-size: 10px; line-height: 1.5; }
        .bill-to-title { font-weight: bold; margin-bottom: 4px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .main-table th, .main-table td { border: 1px solid #000; padding: 5px 8px; font-size: 10px; }
        .main-table th { text-align: center; font-weight: bold; background-color: #f0f0f0; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-box { float: right; width: 280px; border: 1px solid #000; }
        .summary-row { display: flex; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #000; font-size: 10px; }
        .summary-row:last-child { border-bottom: none; font-weight: bold; }
        .footer-section { margin-top: 40px; clear: both; display: flex; justify-content: space-between; padding-top: 16px; }
        .bank-details { border-top: 1px solid #000; padding-top: 8px; width: 50%; font-size: 10px; line-height: 1.5; }
        .signature-section { text-align: center; width: 40%; font-size: 10px; }
        .signature-line { border-bottom: 1px solid #000; margin-bottom: 4px; height: 40px; }
    </style>
</head>
<body onload="window.print()">

    <!-- Header Section -->
    <div class="company-details">
        <div class="company-name">{{ $companySettings->company_name ?? 'COMPANY NAME' }}</div>
        <div>{{ $companySettings->address ?? '' }}</div>
        <div>
            Tel: {{ $companySettings->phone_no ?? '' }}
            @if($companySettings->registration_no) | Reg No: {{ $companySettings->registration_no }} @endif
        </div>
        <div>
            Email: {{ $companySettings->email ?? '' }} 
            @if($companySettings->whatsapp_no) | WhatsApp: {{ $companySettings->whatsapp_no }} @endif
        </div>
    </div>

    <!-- Bill To Section -->
    <div class="header-section" style="margin-top: 20px;">
        <div class="bill-to-section">
            <div class="bill-to-title">Bill To:</div>
            <div><strong>{{ $customer->name ?? 'N/A' }}</strong></div>
            <div>{{ $customer->address_line_1 ?? '' }}</div>
            <div>{{ $customer->address_line_2 ?? '' }}</div>
            <div>
                {{ $customer->city ?? '' }} {{ $customer->postal_code ?? '' }}
                <br>{{ $customer->country ?? '' }}
            </div>
        </div>
        <div>
            <!-- Additional Reference Info Could Go Here -->
            <div style="text-align: right;">
                <strong>Date:</strong> {{ date('d M Y') }}
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 5%;">S.No</th>
                <th style="width: 20%;">Invoice No</th>
                <th style="width: 15%;">Date</th>
                <th style="width: 20%;">Total Amount</th>
                <th style="width: 20%;">Paid Amount</th>
                <th style="width: 20%;">Balance</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAmount = 0; $totalPaid = 0; $totalBalance = 0; @endphp
            @foreach($invoices as $index => $inv)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ $inv['invoice_no'] }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($inv['date'])->format('d M Y') }}</td>
                <td class="text-right">{{ number_format($inv['net_total'], 2) }}</td>
                <td class="text-right">{{ number_format($inv['paid_amount'], 2) }}</td>
                <td class="text-right">{{ number_format($inv['balance'], 2) }}</td>
            </tr>
            @php 
                $totalAmount += $inv['net_total'];
                $totalPaid += $inv['paid_amount'];
                $totalBalance += $inv['balance'];
            @endphp
            @endforeach
        </tbody>
    </table>

    <!-- Summary Section -->
    <div class="summary-box">
        <div class="summary-row">
            <span>TOTAL AMOUNT</span>
            <span>{{ $defaultCurrency->code ?? '$' }} {{ number_format($totalAmount, 2) }}</span>
        </div>
        <div class="summary-row">
            <span>TOTAL PAID</span>
            <span>{{ $defaultCurrency->code ?? '$' }} {{ number_format($totalPaid, 2) }}</span>
        </div>
        <div class="summary-row" style="background-color: #eee;">
            <span>TOTAL OUTSTANDING</span>
            <span>{{ $defaultCurrency->code ?? '$' }} {{ number_format($totalBalance, 2) }}</span>
        </div>
        <!-- Placeholder Discount (Can be wired up if data available) -->
        <div class="summary-row">
            <span>DISCOUNT %</span>
            <span>0.00</span>
        </div>
        <div class="summary-row">
            <span>NET OUTSTANDING</span>
            <span>{{ $defaultCurrency->code ?? '$' }} {{ number_format($totalBalance, 2) }}</span>
        </div>
    </div>

    <div style="clear: both;"></div>

    <!-- Footer Section -->
    <div class="footer-section">
        <div class="bank-details">
            <div style="font-weight: bold; margin-bottom: 5px;">
                OUTSTANDING : {{ $defaultCurrency->code ?? '$' }} {{ number_format($totalBalance, 2) }}
            </div>
            <p style="margin: 5px 0;">
                All cheques should be crossed and make payable to<br>
                <strong>{{ $companySettings->company_name ?? 'SM WHOLESALE TRADERS PTE. LTD.' }}</strong>
            </p>
            <div style="margin-top: 10px;">
                @if($companySettings->bank_name)
                <strong>Bank Transfer:</strong><br>
                Bank: {{ $companySettings->bank_name }}<br>
                Acc No: {{ $companySettings->bank_account_no }}<br>
                Branch: {{ $companySettings->bank_branch ?? '-' }}
                @endif
            </div>
        </div>

        <div class="signature-section">
            <div class="signature-line"></div>
            <strong>For {{ $companySettings->company_name ?? 'SM WHOLESALE TRADERS PTE LTD' }}</strong><br>
            (Authorized Sign)
            
            <div style="margin-top: 30px;">
                <div class="signature-line"></div>
                <strong>Customer’s Sign & Co. Stamp</strong>
            </div>
        </div>
    </div>

</body>
</html>
