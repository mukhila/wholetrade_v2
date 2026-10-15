<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            @page {
                size: auto;
                margin: 5mm;
            }

            html, body {
                width: 100%;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff;
                font-family: 'Courier New', Courier, monospace;
                font-size: 13px;
                line-height: 1.2;
            }
            .a4-container {
                width: 100% !important;
                max-width: 80ch !important;
                height: auto !important;
                min-height: auto !important;
                max-height: none !important;
                margin: 0 auto !important;
                padding: 10px !important;
                box-shadow: none !important;
                border: none !important;
                box-sizing: border-box !important;
                page-break-after: auto;
            }
            .a4-container:last-of-type {
                page-break-after: avoid !important;
            }
            .page-break {
                page-break-after: always;
            }
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            line-height: 1.2;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .a4-container {
            width: 100%;
            max-width: 80ch;
            height: auto;
            min-height: auto;
            margin: 0 auto;
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
        .company-header { text-align: center; margin-bottom: 10px; }
        .company-name { font-size: 18px; font-weight: bold; margin-bottom: 3px; letter-spacing: 0.5px; }
        .company-details { font-size: 12px; line-height: 1.3; color: #000000; }

        /* Layout Table */
        .layout-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .layout-table td {
            vertical-align: top;
            padding: 0;
        }

        /* Bill To & Invoice Info Box */
        .bill-to {
            font-size: 13px;
            line-height: 1.3;
        }
        .invoice-details-table {
            width: 100%;
            border: 1px dashed #000;
            border-collapse: collapse;
        }
        .invoice-title {
            text-align: center;
            font-weight: bold;
            padding: 4px;
            font-size: 15px;
            border-bottom: 1px dashed #000;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .invoice-details-table td {
            padding: 3px 5px;
            font-size: 12px;
        }
        .invoice-label {
            width: 42%;
            border-right: 1px dashed #000;
            border-bottom: 1px dashed #000;
            font-weight: bold;
        }
        .invoice-value {
            width: 58%;
            text-align: right;
            border-bottom: 1px dashed #000;
        }
        .invoice-details-table tr:last-child .invoice-label,
        .invoice-details-table tr:last-child .invoice-value {
            border-bottom: none;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
        }
        .items-table th, .items-table td {
            padding: 4px 6px;
            font-size: 13px;
        }
        .items-table th {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            font-weight: bold;
            text-transform: uppercase;
        }
        th.col-sno, td.col-sno { width: 5%; text-align: center; }
        th.col-code, td.col-code { width: 15%; text-align: left; }
        th.col-desc, td.col-desc { width: 40%; text-align: left; }
        th.col-qty, td.col-qty { width: 10%; text-align: right; }
        th.col-price, td.col-price { width: 15%; text-align: right; }
        th.col-total, td.col-total { width: 15%; text-align: right; font-weight: bold; }

        /* Totals Table */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 3px 5px;
            font-size: 14px;
        }
        .total-label {
            text-align: right;
            font-weight: bold;
        }
        .total-value {
            text-align: right;
            width: 100px;
        }
        .net-amount-row {
            font-size: 15px;
            font-weight: bold;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }

        /* Payment Info & Outstanding */
        .payment-info {
            margin-top: 8px;
            border: 1px dashed #000;
            padding: 6px 8px;
            font-size: 12px;
            line-height: 1.3;
        }
        .outstanding-box {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 6px;
            border: 1px dashed #000;
            padding: 6px 8px;
        }

        /* Payment Stamp */
        .payment-stamp {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            width: 220px;
            opacity: 0.45;
            pointer-events: none;
            z-index: 10;
        }

        /* Signatures */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
        }
        .sign-box {
            width: 45%;
            text-align: center;
            vertical-align: bottom;
        }
        .sign-line {
            border-top: 1px dashed #000;
            margin-top: 25px;
            padding-top: 4px;
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

        // Full pages
        $fullPages = intdiv($totalItems, $rowsPerPage);
        $remaining = $totalItems % $rowsPerPage;

        // Case 1: Everything fits with footer on 1 page
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
    <div class="a4-container" style="position: relative; @if(!$loop->last) page-break-after: always; @endif">
        <!-- 1. Header Section (Repeats on every page) -->
        <div class="company-header" style="position: relative;">
            @if(isset($companySettings) && $companySettings->company_name)
                <div class="company-name uppercase">{{ $companySettings->company_name }}</div>
                <div class="company-details">
                    {{ $companySettings->address }}<br>
                    @if($companySettings->phone_no) Tel: {{ $companySettings->phone_no }} @endif
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

        <table class="layout-table">
            <tr>
                <td style="width: 55%;">
                    <!-- Bill To -->
                    <div class="bill-to">
                        <div class="text-bold" style="margin-bottom: 5px; text-decoration: underline;">BILL TO:</div>
                        <div class="text-bold" style="font-size: 16px;">{{ $invoice->customer->name ?? 'N/A' }}</div>
                        <div style="margin-top: 4px;">{!! nl2br(e($invoice->billing_address)) !!}</div>
                       
                        @if($invoice->customer && $invoice->customer->tax)
                        <div style="margin-top: 4px;">Tax : {{ $invoice->customer->tax->name }} - {{ $invoice->customer->tax->percentage }}%</div>
                        @endif
                        @if($invoice->contact_person)
                        <div style="margin-top: 4px;">Attn: {{ $invoice->contact_person }}</div>
                        @endif
                    </div>
                </td>
                <td style="width: 5%;"></td>
                <td style="width: 40%;">
                    <!-- Invoice Details Box -->
                    <table class="invoice-details-table">
                        <tr>
                            <th colspan="2" class="invoice-title">INVOICE</th>
                        </tr>
                        <tr>
                            <td class="invoice-label">Invoice No</td>
                            <td class="invoice-value">{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td class="invoice-label">Date</td>
                            <td class="invoice-value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</td>
                        </tr>
                        @if($invoice->due_date)
                        <tr>
                            <td class="invoice-label">Due Date</td>
                            <td class="invoice-value">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="invoice-label">Page</td>
                            <td class="invoice-value">{{ $pageIndex + 1 }} of {{ $totalChunks }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- 2. Item Table -->
        @if($chunk->count() > 0)
        <div>
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
                        <td class="col-qty">{{ (int)$item->total_qty == $item->total_qty ? number_format($item->total_qty, 0) : number_format($item->total_qty, 2) }}</td>
                        <td class="col-price">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="col-total">{{ number_format($item->total_price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div style="height: 20px;"></div>
        @endif

        <!-- 3. Summary & Footer (Only on Last Page) -->
        @if($loop->last)
        <table class="layout-table">
            <tr>
                <td style="width: 58%;">
                    <!-- Left Side Footer -->
                    <div class="outstanding-box">
                        <div style="margin-bottom: 5px;">
                            THIS INVOICE: {{ $invoice->currency->symbol ?? '$' }} {{ number_format($invoice->balance_due, 2) }}
                        </div>
                        @if(isset($customerOutstandingBalance) && $customerOutstandingBalance > 0)
                        <div style="font-size: 16px;">
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
                </td>
                <td style="width: 7%;"></td>
                <td style="width: 35%;">
                    <!-- Right Side Totals -->
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
                        @if(isset($companySettings) && $companySettings->gst_include === 'Yes' && $invoice->tax_amount > 0)
                        <tr>
                            <td class="total-label">GST</td>
                            <td class="total-value">{{ number_format($invoice->tax_amount, 2) }}</td>
                        </tr>
                        @endif

                        <tr class="net-amount-row">
                            <td class="total-label">NET TOTAL</td>
                            <td class="total-value">{{ $invoice->currency->symbol ?? '$' }}{{ number_format($invoice->net_total, 2) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Paid / Unpaid Stamp -->
        @php $ps = strtolower($invoice->payment_status ?? 'unpaid'); @endphp
        @if($ps === 'paid')
            <img src="{{ asset('assets/images/paid.png') }}" class="payment-stamp" alt="PAID">
        @else
            <img src="{{ asset('assets/images/unpaid.png') }}" class="payment-stamp" alt="UNPAID">
        @endif

        <!-- 4. Signatures -->
        <table class="signatures-table">
            <tr>
                <td class="sign-box">
                    <div class="sign-line">Customer’s Sign & Co. Stamp</div>
                </td>
                <td style="width: 10%;"></td>
                <td class="sign-box">
                    <div style="margin-bottom: 30px; text-align: right; font-size: 12px;">
                        For {{ $companySettings->company_name ?? 'ST TRADERS PTE. LTD.' }}
                    </div>
                    <div class="sign-line">Authorized Signature</div>
                </td>
            </tr>
        </table>
        @endif

        @if(
            !$loop->last &&
            isset($pages[$pageIndex + 1]) &&
            $pages[$pageIndex + 1]->count() > 0
        )
        <div style="text-align: right; font-style: italic; margin-top: 15px;">
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