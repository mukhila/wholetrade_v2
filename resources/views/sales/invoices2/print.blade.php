<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        @media print {
            @page {
                size: auto;
                margin: 5mm;
            }
            body {
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .a4-container {
            width: 100%;
            max-width: 80ch;
            margin: auto;
            background: white;
            padding: 15px;
            position: relative;
            box-sizing: border-box;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        /* Header */
        .company-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .company-details {
            font-size: 13px;
            line-height: 1.4;
        }

        /* Invoice Info Box */
        .invoice-box-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .bill-to {
            width: 55%;
        }
        .invoice-details {
            width: 40%;
            border: 1px dashed #000;
        }
        .invoice-title {
            background-color: #fff;
            text-align: center;
            font-weight: bold;
            padding: 5px;
            font-size: 16px;
            border-bottom: 1px dashed #000;
        }
        .invoice-data-row {
            display: flex;
            border-bottom: 1px dashed #000;
        }
        .invoice-data-row:last-child { border-bottom: none; }
        .invoice-label {
            width: 40%;
            padding: 4px;
            border-right: 1px dashed #000;
            font-weight: bold;
            background: #fff;
        }
        .invoice-value {
            width: 60%;
            padding: 4px;
            text-align: right;
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th, .items-table td {
            border: 1px dashed #000;
            padding: 6px;
        }
        .items-table th {
            background-color: #fff;
            font-weight: bold;
        }
        th.col-sno, td.col-sno { width: 40px; text-align: center; }
        th.col-code, td.col-code { width: 100px; text-align: left; }
        th.col-desc, td.col-desc { text-align: left; }
        th.col-qty, td.col-qty { width: 60px; text-align: right; }
        th.col-price, td.col-price { width: 90px; text-align: right; }
        th.col-total, td.col-total { width: 80px; text-align: right; font-weight: bold; }

        /* Summary Section */
        .summary-container {
            display: flex;
            justify-content: space-between;
        }
        .footer-left {
            width: 60%;
            font-size: 13px;
        }
        .footer-right {
            width: 35%;
        }
        
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 5px;
        }
        .total-label { text-align: right; font-weight: bold; }
        .total-value { text-align: right; width: 100px; }
        .net-amount-row {
            font-size: 16px;
            font-weight: bold;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }

        /* Payment Info */
        .payment-info {
            margin-top: 15px;
            border: 1px dashed #000;
            padding: 8px;
        }
        .outstanding-box {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        /* Signatures */
        .signatures-container {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            align-items: flex-end;
        }
        .sign-box {
            width: 45%;
            text-align: center;
        }
        .sign-line {
            border-top: 1px dashed #000;
            margin-top: 40px;
            padding-top: 5px;
            font-weight: bold;
            font-size: 13px;
        }
    </style>
</head>
<body>
    @php
        $rowsPerPage = 24;
        $footerThreshold = 19;

        $items = $invoice->items->values();
        $totalItems = $items->count();

        $pages = [];

        // Full 24-row pages
        $fullPages = intdiv($totalItems, $rowsPerPage);
        $remaining = $totalItems % $rowsPerPage;

        // Case 1: Everything fits with footer
        if ($totalItems <= $footerThreshold) {
            $pages[] = $items;
        } else {
            // Add full pages
            for ($i = 0; $i < $fullPages; $i++) {
                $pages[] = $items->slice($i * $rowsPerPage, $rowsPerPage);
            }

            if ($remaining > 0 && $remaining <= $footerThreshold) {
                // Remaining fits WITH footer
                $pages[] = $items->slice($fullPages * $rowsPerPage);
            } else {
                // Remaining too large OR exact multiple
                if ($remaining > 0) {
                    $pages[] = $items->slice($fullPages * $rowsPerPage);
                }
                // Footer-only page
                $pages[] = collect([]);
            }
        }

        $totalChunks = count($pages);
    @endphp

    @foreach($pages as $pageIndex => $chunk)
    <div class="a4-container" style="height: 297mm; position: relative; @if(!$loop->last) page-break-after: always; @endif">
        <!-- 1. Header Section (Repeats on every page) -->
        <div class="company-header" style="position: relative;">
            
            @if(isset($companySettings) && $companySettings->logo)
                <div style="position: absolute; left: 0; top: 0;">
                    <img src="{{ asset('storage/' . $companySettings->logo) }}" alt="Logo" style="height: 60px; max-width: 150px; object-fit: contain;">
                </div>
            @endif

            @if(isset($companySettings) && $companySettings->company_name)
                <div class="company-name uppercase">{{ $companySettings->company_name }}</div>
                <div class="company-details">
                    {{ $companySettings->address }}<br>
                    @if($companySettings->phone_no) Tel: {{ $companySettings->phone_no }} @endif
                    @if($companySettings->whatsapp_no) | WhatsApp: {{ $companySettings->whatsapp_no }} @endif<br>
                    @if($companySettings->email) Email: {{ $companySettings->email }} @endif
                    @if($companySettings->registration_no) | Co. Reg. No: {{ $companySettings->registration_no }} @endif
                </div>
            @else
                <div class="company-name uppercase">SM WHOLESALE TRADERS PTE. LTD.</div>
                <div class="company-details">
                    Your Address Here<br>
                    Tel: XXXXXXXX | Email: info@example.com
                </div>
            @endif
        </div>

        <div class="invoice-box-container">
            <!-- Bill To -->
            <div class="bill-to">
                <div class="text-bold" style="margin-bottom: 5px; text-decoration: underline;">BILL TO:</div>
                <div class="text-bold" style="font-size: 14px;">{{ $invoice->customer->name ?? 'N/A' }}</div>
                <div style="margin-top: 4px;">{{ $invoice->billing_address }}</div>
               
                @if($invoice->customer && $invoice->customer->tax)
                <div style="margin-top: 4px;">Tax : {{ $invoice->customer->tax->name }}</div>
                @endif
                @if($invoice->contact_person)
                <div style="margin-top: 4px;">Attn: {{ $invoice->contact_person }}</div>
                @endif
            </div>

            <!-- Invoice Details Box -->
            <div class="invoice-details">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-data-row">
                    <div class="invoice-label">Invoice No</div>
                    <div class="invoice-value">{{ $invoice->invoice_number }}</div>
                </div>
                <div class="invoice-data-row">
                    <div class="invoice-label">Date</div>
                    <div class="invoice-value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</div>
                </div>

                <div class="invoice-data-row">
                    <div class="invoice-label">Page</div>
                    <div class="invoice-value">{{ $pageIndex + 1 }} of {{ $totalChunks }}</div>
                </div>
            </div>
        </div>

        <!-- 2. Item Table -->
        @if($chunk->count() > 0)
        <div style="min-height: 400px;"> <!-- Min height to ensure layout structure -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="col-sno">S.No</th>
                        <th class="col-code">Item Code</th>
                        <th class="col-desc">Item Description</th>
                        <th class="col-qty">Qty</th>
                        <th class="col-price">Price</th>
                        <th class="col-total">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chunk as $index => $item)
                    <tr>
                        <td class="col-sno">{{ $items->search($item) + 1 }}</td>
                        <td class="col-code">{{ $item->product->sku ?? '' }}</td>
                        <td class="col-desc">
                            {{ $item->item_description ?: ($item->product->name ?? '') }}
                            @if($item->product && $item->product->uom)
                            <span style="font-size: 12px; color: #000;">({{ $item->product->uom->name }})</span>
                            @endif
                        </td>
                        <td class="col-qty">{{ number_format($item->total_qty, 2) }}</td>
                        <td class="col-price">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="col-total">{{ number_format($item->total_price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div style="min-height: 50px;"></div> <!-- Spacer for footer page -->
        @endif

        <!-- 3. Summary & Footer (Only on Last Page) -->
        @if($loop->last)
        <div class="summary-container">
            <!-- Left Side Footer -->
            <div class="footer-left">
                <div class="outstanding-box" style="border: 2px solid #000; padding: 8px; margin-bottom: 10px;">
                    <div style="margin-bottom: 5px;">
                        THIS INVOICE: {{ $invoice->currency->symbol ?? '$' }} {{ number_format($invoice->balance_due, 2) }}
                    </div>
                    @if(isset($customerOutstandingBalance) && $customerOutstandingBalance > 0)
                    <div style="font-size: 16px; color: #000;">
                        TOTAL OUTSTANDING: {{ $invoice->currency->symbol ?? '$' }} {{ number_format($customerOutstandingBalance, 2) }}
                    </div>
                    @endif
                </div>
                
                <div class="payment-info">
                    <div style="font-weight: bold; margin-bottom: 3px;">Payment Instructions:</div>
                    <div>All cheques should be crossed and made payable to:</div>
                    <div class="text-bold uppercase">{{ $companySettings->company_name ?? 'SM WHOLESALE TRADERS PTE. LTD.' }}</div>
                    
                    <div style="margin-top: 8px;">
                        <strong>BANK TRANSFER:</strong><br>
                        Bank: {{ $companySettings->bank_name ?? '' }}<br>
                        Account No: {{ $companySettings->bank_account_no ?? '' }}<br>                           
                        UEN No: {{ $companySettings->uen_no ?? '' }}
                    </div>
                </div>

                @if($invoice->remarks)
                <div style="margin-top: 10px; border: 1px dashed #000; padding: 5px;">
                    <strong>Remarks:</strong> {{ $invoice->remarks }}
                </div>
                @endif
            </div>

            <!-- Right Side Totals -->
            <!-- Formula: Net Total = Subtotal - Discount + Tax -->
            <div class="footer-right">
                <table class="totals-table">
                    <tr>
                        <td class="total-label">SUBTOTAL</td>
                        <td class="total-value">{{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    @if($invoice->discount_amount > 0)
                    <tr>
                        <td class="total-label">DISCOUNT</td>
                        <td class="total-value">-{{ number_format($invoice->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    @if($invoice->tax_amount > 0)
                    <tr>
                        <td class="total-label">TAX</td>
                        <td class="total-value">{{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                    @endif

                    <tr class="net-amount-row">
                        <td class="total-label">NET TOTAL</td>
                        <td class="total-value">{{ $invoice->currency->symbol ?? '$' }}{{ number_format($invoice->net_total, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- 4. Signatures -->
        <div class="signatures-container">
            <div class="sign-box">
                <div class="sign-line">Customer’s Sign & Co. Stamp</div>
            </div>
            <div class="sign-box">
                <div style="margin-bottom: 40px; text-align: right; font-size: 12px;">
                    For {{ $companySettings->company_name ?? 'SM WHOLESALE TRADERS PTE. LTD.' }}
                </div>
                @if(isset($companySettings->authorized_signature) && $companySettings->authorized_signature)
                     <!-- Optional: Add Digital Signature Image if available -->
                @endif
                <div class="sign-line">Authorized Signature</div>
            </div>
        </div>
        @endif

        @if(
            !$loop->last &&
            isset($pages[$pageIndex + 1]) &&
            $pages[$pageIndex + 1]->count() > 0
        )
        <div style="text-align: right; font-style: italic; margin-top: 20px;">
            Continued on next page...
        </div>
        @endif
    </div>
    @endforeach
    
    <script>
        window.onload = function() {
             window.print();
        }
    </script>
</body>
</html>
