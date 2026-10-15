<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_no }} - Outstanding Balance</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 11px;
            color: #666;
        }

        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            color: #0d6efd;
            margin-top: 15px;
            padding: 8px 20px;
            border: 2px solid #0d6efd;
            display: inline-block;
        }

        /* Invoice Info */
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #ccc;
        }

        .invoice-info-left, .invoice-info-right {
            width: 48%;
        }

        .invoice-info-right {
            text-align: right;
        }

        .info-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .invoice-number {
            font-size: 18px;
            font-weight: bold;
            color: #0d6efd;
        }

        /* Customer Box */
        .customer-box {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            background: #f8f9fa;
        }

        .customer-box h4 {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .customer-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Summary Table */
        .summary-section {
            margin-bottom: 25px;
        }

        .summary-section h4 {
            font-size: 12px;
            color: #333;
            text-transform: uppercase;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .summary-table th,
        .summary-table td {
            padding: 10px 12px;
            border: 1px solid #dee2e6;
        }

        .summary-table th {
            background: #e9ecef;
            text-align: left;
            font-weight: 600;
        }

        .summary-table .text-end {
            text-align: right;
        }

        .summary-table .total-row {
            background: #f8f9fa;
            font-weight: bold;
        }

        .summary-table .balance-row {
            background: #dc3545;
            color: #fff;
            font-weight: bold;
            font-size: 14px;
        }

        .summary-table .paid-row {
            background: #198754;
            color: #fff;
        }

        /* Balance Box */
        .balance-box {
            border: 3px solid #dc3545;
            border-radius: 10px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
            background: linear-gradient(135deg, #f8d7da 0%, #f8f9fa 100%);
        }

        .balance-box.paid {
            border-color: #198754;
            background: linear-gradient(135deg, #d1e7dd 0%, #f8f9fa 100%);
        }

        .balance-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .balance-value {
            font-size: 36px;
            font-weight: bold;
            color: #dc3545;
        }

        .balance-box.paid .balance-value {
            color: #198754;
        }

        /* Payment History */
        .payment-history {
            margin-bottom: 20px;
        }

        .payment-history h4 {
            font-size: 12px;
            color: #333;
            text-transform: uppercase;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .payment-table th {
            background: #e9ecef;
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #dee2e6;
            font-weight: 600;
        }

        .payment-table td {
            padding: 8px 10px;
            border: 1px solid #dee2e6;
        }

        .text-success {
            color: #198754;
        }

        .text-danger {
            color: #dc3545;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #999;
        }

        /* Print Styles */
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .invoice-container {
                padding: 0;
                max-width: 100%;
            }

            .no-print {
                display: none !important;
            }
        }

        /* Print Button */
        .print-actions {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .btn {
            display: inline-block;
            padding: 10px 25px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            margin: 0 5px;
        }

        .btn-primary {
            background: #0d6efd;
            color: #fff;
        }

        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Print Actions -->
        <div class="print-actions no-print">
            <button class="btn btn-primary" onclick="window.print()">
                Print Invoice
            </button>
            <a href="{{ route('old-sales-invoices.index') }}" class="btn btn-secondary">
                Back to List
            </a>
        </div>

        <!-- Header -->
        <div class="header">
            <div class="company-name">{{ $companySettings->company_name ?? 'WHOLESALE TRADERS PTE LTD' }}</div>
            <div class="company-details">
                {{ $companySettings->address ?? '63 HILLVIEW AVENUE, #10-11 LAM SOON INDUSTRIAL BUILDING, SINGAPORE 669569' }}<br>
                Tel: {{ $companySettings->phone_no ?? '+65 84427604' }} |
                Email: {{ $companySettings->email ?? 'JMAHENDRAN79@GMAIL.COM' }}<br>
                @if(isset($companySettings->registration_no) && $companySettings->registration_no)
                    Reg No: {{ $companySettings->registration_no }}
                @else
                    Reg No: 202415901E
                @endif
            </div>
            <div class="invoice-title">INVOICE STATEMENT</div>
        </div>

        <!-- Invoice Info -->
        <div class="invoice-info">
            <div class="invoice-info-left">
                <div class="info-label">Invoice Number</div>
                <div class="invoice-number">#{{ $invoice->invoice_no }}</div>
            </div>
            <div class="invoice-info-right">
                <div class="info-label">Invoice Date</div>
                <div class="info-value">{{ $invoice->date ? $invoice->date->format('d M Y') : '-' }}</div>
                <div class="info-label">Statement Date</div>
                <div class="info-value">{{ now()->format('d M Y') }}</div>
            </div>
        </div>

        <!-- Customer Box -->
        <div class="customer-box">
            <h4>Bill To</h4>
            <div class="customer-name">{{ $invoice->customer_name ?? ($invoice->customer->name ?? 'N/A') }}</div>
            @if($invoice->customer)
                <div style="font-size: 11px; color: #666;">
                    {{ $invoice->customer->code ?? '' }}<br>
                    {{ $invoice->customer->address_line_1 ?? '' }}
                    @if($invoice->customer->address_line_2), {{ $invoice->customer->address_line_2 }}@endif
                    @if($invoice->customer->city), {{ $invoice->customer->city }}@endif
                </div>
            @endif
        </div>

        <!-- Summary Section -->
        <div class="summary-section">
            <h4>Invoice Summary</h4>
            <table class="summary-table">
                <tbody>
                    <tr class="total-row">
                        <td>Invoice Total</td>
                        <td class="text-end">{{ $defaultCurrency->code ?? 'SGD' }} {{ number_format($invoice->net_total, 2) }}</td>
                    </tr>
                    <tr class="paid-row">
                        <td>Total Paid</td>
                        <td class="text-end">{{ $defaultCurrency->code ?? 'SGD' }} {{ number_format($invoice->paid, 2) }}</td>
                    </tr>
                    <tr class="balance-row">
                        <td>Outstanding Balance</td>
                        <td class="text-end">{{ $defaultCurrency->code ?? 'SGD' }} {{ number_format($invoice->balance, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Balance Box -->
        <div class="balance-box {{ $invoice->balance <= 0 ? 'paid' : '' }}">
            <div class="balance-label">{{ $invoice->balance > 0 ? 'Amount Due' : 'Status' }}</div>
            <div class="balance-value">
                @if($invoice->balance > 0)
                    {{ $defaultCurrency->code ?? 'SGD' }} {{ number_format($invoice->balance, 2) }}
                @else
                    PAID IN FULL
                @endif
            </div>
            @if($invoice->balance > 0)
                <div style="margin-top: 10px; font-size: 11px; color: #666;">
                    Please remit payment at your earliest convenience.
                </div>
            @endif
        </div>

        <!-- Payment History -->
        @if($paymentHistory && $paymentHistory->count() > 0)
        <div class="payment-history">
            <h4>Payment History</h4>
            <table class="payment-table">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Date</th>
                        <th>Payment Mode</th>
                        <th>Reference</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paymentHistory as $payment)
                    <tr>
                        <td>{{ $payment->receipt_number }}</td>
                        <td>{{ \Carbon\Carbon::parse($payment->receipt_date)->format('d M Y') }}</td>
                        <td>{{ $payment->payment_mode ?? '-' }}</td>
                        <td>{{ $payment->reference ?? '-' }}</td>
                        <td class="text-end text-success" style="font-weight: bold;">{{ number_format($payment->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #f8f9fa;">
                        <th colspan="4" class="text-end">Total Payments:</th>
                        <th class="text-end text-success">{{ number_format($paymentHistory->sum('amount'), 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business.</p>
            <p>This is a computer-generated statement. No signature required.</p>
            <p style="margin-top: 10px;">Printed on: {{ now()->format('d M Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
