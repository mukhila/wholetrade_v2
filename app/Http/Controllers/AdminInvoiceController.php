<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class AdminInvoiceController extends Controller
{
    // Blade: List Invoices
    public function index()
    {
        $invoices = Invoice::with(['quotation.customer'])->latest()->paginate(15);
        
        $pending = Invoice::where('payment_status', 'Pending')->count();
        $partiallyPaid = Invoice::where('payment_status', 'Partially Paid')->count();
        $paid = Invoice::where('payment_status', 'Paid')->count();
        
        return view('admin.invoices.index', compact('invoices', 'pending', 'partiallyPaid', 'paid'));
    }

    // API: Update Payment Status
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Pending,Partially Paid,Paid,Cancelled',
        ]);

        $invoice = Invoice::findOrFail($id);
        $invoice->payment_status = $request->status;
        $invoice->save();

        // Optionally, if Paid, update Quotation status
        if($request->status === 'Paid') {
            $invoice->quotation->status = 'CONVERTED_TO_INVOICE'; 
            $invoice->quotation->save();
        }

        return response()->json(['message' => 'Invoice status updated successfully.']);
    }

    // API: Export PDF (Blade View for Print)
    public function export($id)
    {
        $invoice = Invoice::with(['quotation.items.product', 'quotation.customer', 'quotation.salesperson'])->findOrFail($id);
        return view('admin.invoices.pdf', compact('invoice'));
    }
}
