<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; padding: 20px; text-align: center; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); font-size: 16px; line-height: 24px; color: #555; text-align: left; }
        .invoice-box table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
        .invoice-box table td { padding: 5px; vertical-align: top; }
        .invoice-box table tr td:nth-child(2) { text-align: right; }
        .invoice-box table tr.top table td { padding-bottom: 20px; }
        .invoice-box table tr.top table td.title { font-size: 45px; line-height: 45px; color: #333; }
        .invoice-box table tr.information table td { padding-bottom: 40px; }
        .invoice-box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
        .invoice-box table tr.details td { padding-bottom: 20px; }
        .invoice-box table tr.item td { border-bottom: 1px solid #eee; }
        .invoice-box table tr.item.last td { border-bottom: none; }
        .invoice-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; }
        
        @media only screen and (max-width: 600px) {
            .invoice-box table tr.top table td { width: 100%; display: block; text-align: center; }
            .invoice-box table tr.information table td { width: 100%; display: block; text-align: center; }
        }
        
        @media print {
            .no-print { display: none; }
            .invoice-box { border: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Print / Save as PDF</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Close</button>
    </div>

    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="4">
                    <table>
                        <tr>
                            <td class="title">
                                @if(isset($companySettings) && $companySettings?->logo)
                                    <img src="{{ \Storage::disk('public_uploads')->url($companySettings->logo) }}" style="width:100%; max-width:200px; object-fit:contain;">
                                @else
                                    <img src="{{ asset('assets/images/logo.png') }}" style="width:100%; max-width:200px; object-fit:contain;">
                                @endif
                            </td>
                            
                            <td>
                                Invoice #: {{ $invoice->invoice_number }}<br>
                                Date: {{ $invoice->created_at->format('Y-m-d') }}<br>
                                Ref Quotation: {{ $invoice->quotation->quotation_number }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="information">
                <td colspan="4">
                    <table>
                        <tr>
                            <td>
                                <strong>Customer:</strong><br>
                                {{ $invoice->quotation->customer->name }}<br>
                                {{ $invoice->quotation->customer->code }}<br>
                                {{ $invoice->quotation->customer->address ?? '' }}
                            </td>
                            
                            <td>
                                <strong>Salesperson:</strong><br>
                                {{ $invoice->quotation->salesperson->name }}<br>
                                {{ $invoice->quotation->salesperson->email }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="heading">
                <td>Item</td>
                <td>Qty</td>
                <td>Unit Price</td>
                <td>Total</td>
            </tr>
            
            @foreach($invoice->quotation->items as $item)
            <tr class="item">
                <td>{{ $item->product->name }} ({{ $item->product->sku }})</td>
                <td>
                    @if($item->item_qty > 0) {{ $item->item_qty }} Units @endif
                    @if($item->box_qty > 0) {{ $item->box_qty }} Boxes @endif
                </td>
                <td>${{ number_format($item->total_price / max(1, ($item->item_qty + ($item->box_qty * ($item->product->pack_size_qty ?? 1)))), 2) }} (Avg)</td>
                <td>${{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
            
            <tr class="total">
                <td colspan="3">Subtotal:</td>
                <td>${{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            <tr class="total">
                <td colspan="3">Tax:</td>
                <td>${{ number_format($invoice->tax_amount, 2) }}</td>
            </tr>
            <tr class="total">
                <td colspan="3">Grand Total:</td>
                <td>${{ number_format($invoice->grand_total, 2) }}</td>
            </tr>
        </table>
        
        <br>
        <p><strong>Payment Status:</strong> {{ $invoice->payment_status }}</p>
        <p><strong>Remarks:</strong> {{ $invoice->quotation->remarks }}</p>
    </div>
    
    <script>
        window.onload = function() {
            // Check if URL has ?print=true parameter to auto print
            // window.print();
        };
    </script>
</body>
</html>
