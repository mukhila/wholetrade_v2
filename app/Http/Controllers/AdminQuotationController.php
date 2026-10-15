<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Invoice;
use App\Models\QuotationLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Notifications\QuotationStatusUpdated; 
// Ensure User model has Notifiable trait, defaulting to ID 1 if not auth for system actions

class AdminQuotationController extends Controller
{
    // Blade: Dashboard for Admin
    public function index()
    {
        // Fetch statuses appropriate for Admin dashboard
        $pending = Quotation::where('status', 'PENDING_ADMIN_APPROVAL')->count();
        $approved = Quotation::where('status', 'APPROVED_BY_ADMIN')->count(); // Or Invoiced
        $rejected = Quotation::where('status', 'REJECTED')->count();
            
        // Main list - usually pending first
        $quotations = Quotation::with(['customer', 'salesperson'])
            ->whereIn('status', ['PENDING_ADMIN_APPROVAL', 'WAITING_FOR_SALES_APPROVAL', 'REVISED_BY_ADMIN'])
            ->latest()
            ->paginate(15);
            
        return view('admin.quotations.index', compact('quotations', 'pending', 'approved', 'rejected'));
    }

    // Blade: Detailed Review Page
    public function show($id)
    {
        $quotation = Quotation::with(['items.product', 'customer', 'salesperson', 'items.product.price'])->findOrFail($id);
        return view('admin.quotations.show', compact('quotation'));
    }

    // API: Approve Quotation
    public function approve($id)
    {
        DB::beginTransaction();
        try {
            $quotation = Quotation::findOrFail($id);
            
            // 1. Status Update
            $quotation->status = 'APPROVED_BY_ADMIN';
            $quotation->save();

            // 2. Generate Invoice
            $invoice = Invoice::create([
                'quotation_id' => $quotation->id,
                'subtotal' => $quotation->subtotal,
                'tax_amount' => $quotation->tax_amount,
                'grand_total' => $quotation->grand_total,
                'payment_status' => 'Pending',
            ]);

            // 3. Log
            $this->logAction($quotation->id, 'APPROVED_BY_ADMIN', 'Quotation approved and Invoice generated.');

            // 4. Notify Salesperson
            if ($quotation->salesperson) {
                $quotation->salesperson->notify(new QuotationStatusUpdated($quotation, 'APPROVED', 'Your quotation has been approved and invoiced.'));
            }

            DB::commit();
            return response()->json(['message' => 'Quotation Approved and Invoice Generated!', 'invoice_number' => $invoice->invoice_number]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // API: Reject Quotation
    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string']);

        $quotation = Quotation::findOrFail($id);
        $quotation->status = 'REJECTED';
        $quotation->remarks = $request->reason; // Or separate admin_notes field
        $quotation->save();

        $this->logAction($quotation->id, 'REJECTED', $request->reason);

        // Notify
        if ($quotation->salesperson) {
            $quotation->salesperson->notify(new QuotationStatusUpdated($quotation, 'REJECTED', 'Your quotation was rejected: ' . $request->reason));
        }

        return response()->json(['message' => 'Quotation Rejected.']);
    }

    // API: Revise Quotation (Partial Stock)
    public function revise(Request $request, $id)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:quotation_items,id',
            'items.*.item_qty' => 'required|integer|min:0',
            'items.*.box_qty' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $quotation = Quotation::findOrFail($id);
            $total = 0;

            foreach ($request->items as $itemData) {
                $item = QuotationItem::findOrFail($itemData['id']);
                
                // If both are 0, maybe delete? Or keep as 0? 
                // Let's keep as 0 to show "Out of Stock" effectively unless explicit delete requested.
                // But generally 0 qty item is weird. If both 0, delete it.
                if ($itemData['item_qty'] == 0 && $itemData['box_qty'] == 0) {
                    $item->delete();
                } else {
                    $item->item_qty = $itemData['item_qty'];
                    $item->box_qty = $itemData['box_qty'];
                    
                    // Recalculate Total
                    $itemTotal = ($item->item_qty * $item->unit_price) + ($item->box_qty * $item->box_price);
                    $item->total_price = $itemTotal;
                    $item->save();
                    
                    $total += $itemTotal;
                }
            }

            // Recalculate Totals
            $quotation->subtotal = $total;
            $quotation->grand_total = $total; // Simplified tax logic
            $quotation->status = 'WAITING_FOR_SALES_APPROVAL';
            $quotation->admin_notes = $request->remarks;
            $quotation->save();

            $this->logAction($quotation->id, 'REVISED_BY_ADMIN', $request->remarks ?? 'Quantity adjusted by Admin.');

            // Notify
            if ($quotation->salesperson) {
                $quotation->salesperson->notify(new QuotationStatusUpdated($quotation, 'REVISED', 'Action Required: Quotation revised by Admin.'));
            }

            DB::commit();
            return response()->json(['message' => 'Quotation Revised. Sent back to Salesperson.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function logAction($quotationId, $status, $remarks = null)
    {
        QuotationLog::create([
            'quotation_id' => $quotationId,
            'status' => $status,
            'changed_by' => Auth::id() ?? 1,
            'remarks' => $remarks,
            'changed_at' => now(),
        ]);
    }
}
