<?php

namespace App\Http\Controllers;

use App\Models\ProformaInvoice;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    // Print Proforma Invoice Receipt
    public function printProformaReceipt($id)
    {
        $pi = ProformaInvoice::with(['items.product', 'customer', 'currency', 'salesperson'])->findOrFail($id);
        return view('sales.receipts.proforma_receipt', compact('pi'));
    }

    // You can add methods for SalesInvoice receipts here later
    // public function printInvoiceReceipt($id) { ... }
}
