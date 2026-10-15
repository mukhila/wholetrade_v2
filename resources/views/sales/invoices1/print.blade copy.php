<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
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
           /* @page { size: A4 portrait; margin: 8mm 10mm; }*/

            @page{
                size:A4;
                margin:0;
            }

            html, body {
                width: 100%;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff;
                font-family: 'Noto Sans', Arial, sans-serif;
                font-size: 10px;
                line-height: 1.3;
            }
            .a4-container {
                width: 100% !important;
                height:auto !important;
                min-height: auto !important;
                max-height: none !important;
                margin: 0 !important;
                padding: 40px 20px 0px 20px !important;
                box-shadow: none !important;
                border: none !important;
                box-sizing: border-box !important;
                page-break-after: auto;
                /*page-break-inside: avoid;*/
            }
            .a4-container:last-of-type {
                page-break-after: avoid !important;
            }
            .page-break{
                page-break-after: always;
            }
        }
        body {
            font-family: 'Noto Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0px 0;
        }
        .a4-container {
            width: 210mm;
            height:auto;
            min-height: 297mm;
            margin:0 auto;
            background: white;
            padding: 10mm 15mm;
            position: relative;
            box-sizing: border-box;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        /* Header */
        .company-header { text-align: center; margin-bottom: 12px; }
        .company-name { font-size: 18px; font-weight: bold; margin-bottom: 3px; letter-spacing: 1px; }
        .company-details { font-size: 9px; line-height: 1.4; color: #000000; }

        /* Invoice Info Box */
        .invoice-box-container {
            display: flex;
            justify-content: space-between;
            margin-top: 12px;
            margin-bottom: 20px;
        }
        .bill-to { width: 55%; font-size: 10px; line-height: 1.4; }
        .invoice-details { width: 42%; border: 1px solid #000; }
        .invoice-title {
            text-align: center;
            font-weight: bold;
            padding: 5px;
            font-size: 14px;
            border-bottom: 1px solid #000;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .invoice-data-row { display: flex; border-bottom: 1px solid #ccc; }
        .invoice-data-row:last-child { border-bottom: none; }
        .invoice-label {
            width: 40%;
            padding: 4px;
            border-right: 1px solid #ccc;
            font-weight: bold;
            font-size: 9px;
        }
        .invoice-value { width: 58%; padding: 3px 5px; text-align: right; font-size: 9px; }

        /* Table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .items-table th, .items-table td { border: 1px solid #000; padding: 4px 6px; font-size: 10px; }
        .items-table th {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .col-sno { width: 40px; text-align: center; }
        .col-code { width: 100px; }
        .col-qty { width: 60px; text-align: right; }
        .col-price { width: 90px; text-align: right; }
        .col-total { width: 80px; text-align: right; font-weight: bold; }

        /* Summary Section */
        .summary-container { display: flex; justify-content: space-between; margin-top: 5px; }
        .footer-left { width: 58%; font-size: 10px; line-height: 1.4; }
        .footer-right { width: 35%; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 3px 5px; font-size: 10px; }
        .total-label { text-align: right; font-weight: bold; }
        .total-value { text-align: right; width: 100px; }
        .net-amount-row {
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        /* Payment Info */
        .payment-info { margin-top: 8px; border: 1px solid #000; padding: 6px 8px; font-size: 9.5px; line-height: 1.4; }
        .outstanding-box { font-size: 9.5px; font-weight: bold; margin-bottom: 6px; }

        /* Signatures */
        .signatures-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            align-items: flex-end;
        }
        .sign-box { width: 45%; text-align: center; }
        .sign-line {
            border-top: 1px solid #000;
            margin-top: 28px;
            padding-top: 5px;
            font-weight: bold;
            font-size: 11px;
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
   <div class="a4-container" style="height: 297mm; position: relative; @if(!$loop->last) page-break-after: always; @endif">
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

        <div class="invoice-box-container">
            <!-- Bill To -->
            <div class="bill-to">
                <div class="text-bold" style="margin-bottom: 5px; text-decoration: underline;">BILL TO:</div>
                <div class="text-bold" style="font-size: 14px;">{{ $invoice->customer->name ?? 'N/A' }}</div>
                <div style="margin-top: 4px;">{{ $invoice->billing_address }}</div>
               
                @if($invoice->customer && $invoice->customer->tax_id)
                <div style="margin-top: 4px;">Tax ID: {{ $invoice->customer->tax_id }}</div>
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
                @if($invoice->due_date)
                <div class="invoice-data-row">
                    <div class="invoice-label">Due Date</div>
                    <div class="invoice-value">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</div>
                </div>
                @endif
                <div class="invoice-data-row">
                    <div class="invoice-label">Page</div>
                    <div class="invoice-value">{{ $pageIndex + 1 }} of {{ $totalChunks }}</div>
                </div>
            </div>
        </div>

        <!-- 2. Item Table -->
        @if($chunk->count() > 0)
        <div style="min-height: 400px;">
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
                            <span style="font-size: 10px; color: #555;">({{ $item->product->uom->name }})</span>
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
        <div style="min-height: 50px;"></div>
        @endif

        <!-- 3. Summary & Footer (Only on Last Page) -->
        @if($loop->last)
        <div class="summary-container">
            <!-- Left Side Footer -->
            <div class="footer-left">
                <div class="outstanding-box" style="border: 2px solid #000; padding: 8px 8px; margin-bottom: 10px;">
                    <div style="margin-bottom: 5px;">
                        THIS INVOICE: {{ $invoice->currency->symbol ?? '$' }} {{ number_format($invoice->balance_due, 2) }}
                    </div>
                    @if(isset($customerOutstandingBalance) && $customerOutstandingBalance > 0)
                    <div style="font-size: 14px;">
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
                <div style="margin-top: 10px; border: 1px dashed #ccc; padding: 5px;">
                    <strong>Remarks:</strong> {{ $invoice->remarks }}
                </div>
                @endif
            </div>

            <!-- Right Side Totals -->
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
            </div>
        </div>

        <!-- 4. Signatures -->
        <div class="signatures-container">
            <div class="sign-box">
                <div class="sign-line">Customer’s Sign & Co. Stamp</div>
            </div>
            <div class="sign-box">
                <div style="margin-bottom: 40px; text-align: right; font-size: 10px;">
                    For {{ $companySettings->company_name ?? 'ST TRADERS PTE. LTD.' }}
                </div>
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