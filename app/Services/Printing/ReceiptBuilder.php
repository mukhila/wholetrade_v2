<?php

namespace App\Services\Printing;

use Carbon\Carbon;

class ReceiptBuilder
{
    public function build($invoice, $cs, float $outstanding = 0): array
    {
        $sym = $invoice->currency->symbol ?? '$';
        $ops = [['init']];

        /* ── store header ── */
        $ops[] = ['align', 'center'];
        $ops[] = ['bold', true];
        $ops[] = ['text', strtoupper($cs->company_name ?? 'BUSINESS NAME')];
        $ops[] = ['bold', false];
        if ($cs->address ?? null)         $ops[] = ['text', $cs->address];
        if ($cs->phone_no ?? null)        $ops[] = ['text', 'Ph: ' . $cs->phone_no];
        if ($cs->registration_no ?? null) $ops[] = ['text', 'Co.Reg: ' . $cs->registration_no];
        if ($cs->email ?? null)           $ops[] = ['text', $cs->email];
        if ($cs->uen_no ?? null)          $ops[] = ['text', 'UEN: ' . $cs->uen_no];

        $ops[] = ['hr', '-'];
        $ops[] = ['bold', true];
        $ops[] = ['text', '* ' . (($invoice->tax_amount ?? 0) > 0 ? 'TAX INVOICE' : 'INVOICE') . ' *'];
        $ops[] = ['bold', false];
        $ops[] = ['hr', '-'];

        /* ── meta ── */
        $ops[] = ['align', 'left'];
        $ops[] = ['row', 'Invoice No', (string) $invoice->invoice_number];
        $ops[] = ['row', 'Date',       Carbon::parse($invoice->invoice_date)->format('d M Y')];
        if ($invoice->due_date) {
            $ops[] = ['row', 'Due Date', Carbon::parse($invoice->due_date)->format('d M Y')];
        }
        $ops[] = ['row', 'Customer', $invoice->customer->name ?? 'WALK-IN'];

        if (($invoice->customer->address_line_1 ?? null) || ($invoice->customer->address_line_2 ?? null)) {
            if ($invoice->customer->address_line_1 ?? null)
                $ops[] = ['text', 'Addr: ' . $invoice->customer->address_line_1];
            if ($invoice->customer->address_line_2 ?? null)
                $ops[] = ['text', '      ' . $invoice->customer->address_line_2];
        } elseif ($invoice->customer->address ?? null) {
            $ops[] = ['text', 'Addr: ' . $invoice->customer->address];
        }

        if ($invoice->salesperson)       $ops[] = ['row', 'Sales',     $invoice->salesperson->name];
        if ($invoice->reference_number)  $ops[] = ['row', 'Reference', $invoice->reference_number];

        $ps = strtoupper($invoice->payment_status ?? $invoice->status ?? '');
        $ops[] = ['row', 'Status', $ps];

        /* ── items table ── */
        $ops[] = ['hr', '-'];
        $ops[] = ['bold', true];
        $ops[] = ['item_hdr'];
        $ops[] = ['bold', false];
        $ops[] = ['hr', '-'];

        foreach ($invoice->items as $item) {
            $desc    = $item->item_description ?: ($item->product->name ?? '');
            $uomName = $item->product->uom->name ?? '';
            $boxNote = ($item->box_qty ?? 0) > 0 ? ((int) $item->box_qty) . ' box' : '';
            $subNote = implode(' | ', array_filter([$uomName, $boxNote]));
            if ($subNote) $desc .= ' (' . $subNote . ')';

            $qty    = $item->total_qty;
            $qtyFmt = ($qty == floor($qty)) ? (string)(int)$qty : number_format($qty, 2);

            $discount = ($item->discount_amount ?? 0) > 0
                ? number_format($item->discount_amount, 2)
                : '';

            $ops[] = ['item',
                $desc,
                $qtyFmt,
                number_format($item->unit_price, 2),
                number_format($item->total_price, 2),
                $discount,
            ];
        }

        $ops[] = ['hr', '='];

        /* ── totals ── */
        $ops[] = ['row', 'Sub Total', number_format($invoice->subtotal, 2)];

        if (($invoice->discount_amount ?? 0) > 0) {
            $ops[] = ['row', 'Discount', '-' . number_format($invoice->discount_amount, 2)];
        }

        if (($invoice->tax_amount ?? 0) > 0) {
            $taxBase = max(0, ($invoice->subtotal ?? 0) - ($invoice->discount_amount ?? 0));
            $gstRate = $taxBase > 0 ? round($invoice->tax_amount / $taxBase * 100) : 0;
            $ops[] = ['row', "GST {$gstRate}%", number_format($invoice->tax_amount, 2)];
        }

        $ops[] = ['hr', '='];
        $ops[] = ['bold', true];
        $ops[] = ['row', 'NET TOTAL (' . $sym . ')', number_format($invoice->net_total, 2)];
        $ops[] = ['bold', false];
        $ops[] = ['hr', '='];

        if (($invoice->paid_amount ?? 0) > 0) {
            $ops[] = ['row', 'Paid Amount',  number_format($invoice->paid_amount, 2)];
            $ops[] = ['row', 'Balance Due',  number_format($invoice->balance_due, 2)];
            $ops[] = ['hr', '-'];
        }

        $ops[] = ['bold', true];
        $ops[] = ['row', 'THIS INVOICE', $sym . ' ' . number_format($invoice->balance_due, 2)];
        if ($outstanding > 0) {
            $ops[] = ['row', 'OUTSTANDING', $sym . ' ' . number_format($outstanding, 2)];
        }
        $ops[] = ['bold', false];

        if ($invoice->remarks) {
            $ops[] = ['hr', '-'];
            $ops[] = ['text', 'Remarks: ' . $invoice->remarks];
        }

        /* ── payment instructions ── */
        $ops[] = ['hr', '-'];
        $ops[] = ['bold', true];
        $ops[] = ['text', 'PAYMENT INSTRUCTIONS'];
        $ops[] = ['bold', false];
        $ops[] = ['text', 'Cheques payable to:'];
        $ops[] = ['bold', true];
        $ops[] = ['text', strtoupper($cs->company_name ?? 'BUSINESS NAME')];
        $ops[] = ['bold', false];
        if ($cs->bank_name ?? null)       $ops[] = ['text', 'Bank      : ' . $cs->bank_name];
        if ($cs->bank_account_no ?? null) $ops[] = ['text', 'Acct No   : ' . $cs->bank_account_no];
        if ($cs->uen_no ?? null)          $ops[] = ['text', 'UEN No    : ' . $cs->uen_no];

        /* ── QR code (PayNow / Scan to Pay) ── */
        $qrPath = public_path('assets/images/sgtrade_printqr.jpeg');
        if (!file_exists($qrPath)) {
            $qrPath = public_path('assets/images/qr.png');
        }
        if (file_exists($qrPath)) {
            $ops[] = ['hr', '-'];
            $ops[] = ['align', 'center'];
            $ops[] = ['bold', true];
            $ops[] = ['text', strtoupper($cs->company_name ?? '')];
            $ops[] = ['bold', false];
            $ops[] = ['qr_image', $qrPath];
            $ops[] = ['bold', true];
            $ops[] = ['text', '* SCAN TO PAY *'];
            $ops[] = ['bold', false];
            if ($cs->uen_no ?? null) {
                $ops[] = ['text', 'PayNow UEN: ' . $cs->uen_no];
            }
            $ops[] = ['align', 'left'];
        }

        /* ── signatures ── */
        $ops[] = ['hr', '-'];
        $ops[] = ['feed', 2];
        $ops[] = ['row', '______________', '______________'];
        $ops[] = ['row', 'Authorized Sign', 'Customer Sign'];
        $ops[] = ['row', '               ', '& Co. Stamp   '];

        /* ── footer ── */
        $ops[] = ['hr', '-'];
        $ops[] = ['align', 'center'];
        $ops[] = ['bold', true];
        $ops[] = ['text', '* THANK YOU FOR YOUR BUSINESS *'];
        $ops[] = ['bold', false];
        $ops[] = ['text', 'Powered by Techgeeks'];
        $ops[] = ['feed', 5];
        $ops[] = ['cut'];

        return $ops;
    }
}
