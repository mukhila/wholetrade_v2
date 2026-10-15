<?php

namespace App\Http\Controllers;

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReceipt;
use App\Models\Associate;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\Currency;
use App\Models\User;
use App\Models\CompanySetting;
use App\Models\AuditLog;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\InvoiceReference;
use App\Models\AssociateProductPrice;
use App\Models\ProductPrice;
use App\Models\ProductAlias;

class SalesInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesInvoice::with('customer', 'salesperson')
            ->withSum('items as total_quantity', 'total_qty');

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', '%' . $request->search . '%');
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Date-wise summary (only when a date range is active)
        $summary = collect();
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $summaryQuery = SalesInvoice::selectRaw(
                'DATE(invoice_date) as date,
                 COUNT(*) as invoice_count,
                 SUM((SELECT COALESCE(SUM(sii.total_qty), 0) FROM sales_invoice_items sii WHERE sii.sales_invoice_id = sales_invoices.id)) as total_qty,
                 SUM(net_total) as total_sales,
                 SUM(tax_amount) as total_gst,
                 SUM(paid_amount) as total_paid,
                 SUM(balance_due) as total_balance'
            );
            if ($request->filled('date_from')) {
                $summaryQuery->whereDate('invoice_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $summaryQuery->whereDate('invoice_date', '<=', $request->date_to);
            }
            if ($request->filled('customer_id')) {
                $summaryQuery->where('customer_id', $request->customer_id);
            }
            $summary = $summaryQuery->groupByRaw('DATE(invoice_date)')
                ->orderBy('date', 'desc')
                ->get();
        }

        $customers = Associate::where('type', 'Customer')->orderBy('name')->get(['id', 'name']);

        return view('sales.invoices.index', compact('invoices', 'summary', 'customers'));
    }

    /**
     * Export filtered sales invoices to CSV
     */
    public function export(Request $request)
    {
        $this->checkStaffPermission('sales-invoices', 'export');

        $query = SalesInvoice::with(['customer', 'salesperson'])
            ->withSum('items as total_quantity', 'total_qty');

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', '%' . $request->search . '%');
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();

        $filename = 'sales_invoices_' . now()->format('Y-m-d_His') . '.csv';

        return response()->stream(function () use ($invoices) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, [
                'Invoice #',
                'Type',
                'Invoice Date',
                'Due Date',
                'Customer',
                'Salesperson',
                'Total Qty',
                'Gross Total',
                'Discount',
                'Tax Amount (GST)',
                'Net Total',
                'Paid Amount',
                'Balance Due',
                'Payment Status',
                'Status',
                'Reference #'
            ]);

            foreach ($invoices as $inv) {
                fputcsv($handle, [
                    $inv->invoice_number,
                    strtoupper($inv->invoice_type ?? 'GST'),
                    $inv->invoice_date ? \Carbon\Carbon::parse($inv->invoice_date)->format('Y-m-d') : '',
                    $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('Y-m-d') : '',
                    $inv->customer->name ?? 'N/A',
                    $inv->salesperson->name ?? 'N/A',
                    $inv->total_quantity ?? 0,
                    number_format((float) ($inv->gross_total ?? $inv->net_total), 2, '.', ''),
                    number_format((float) ($inv->discount_amount ?? 0), 2, '.', ''),
                    number_format((float) ($inv->tax_amount ?? 0), 2, '.', ''),
                    number_format((float) ($inv->net_total ?? 0), 2, '.', ''),
                    number_format((float) ($inv->paid_amount ?? 0), 2, '.', ''),
                    number_format((float) ($inv->balance_due ?? 0), 2, '.', ''),
                    ucfirst($inv->payment_status ?? 'unpaid'),
                    ucfirst($inv->status ?? 'draft'),
                    $inv->reference_number ?? '',
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-store, no-cache',
        ]);
    }

    public function create(Request $request)
    {
        $customers = Associate::where('type', 'Customer')->where('status', true)->with('tax')->get();
        $currencies = Currency::all(); // BUG-009 FIX: all currencies, not just the default
        $products = Product::where('status', true)
            ->with([
                'price', 'packSize', 'uom',
                'aliases' => fn($q) => $q->withoutGlobalScope('subscriber')->where('status', true),
            ])
            ->get();
        $salespeople = User::whereHas('roles', function($q) {
            $q->where('name', 'salesperson');
        })->get();

        $invoiceType = in_array($request->query('invoice_type'), ['gst', 'non_gst']) ? $request->query('invoice_type') : 'gst';

        // Preview number only; actual number is generated from sequential type count in store()
        $cs = CompanySetting::first();
        $prefix = $invoiceType === 'non_gst'
            ? ($cs->non_gst_invoice_prefix ?? 'INV-')
            : ($cs->gst_invoice_prefix ?? 'INVG');
        $nextCount = SalesInvoice::where('invoice_type', $invoiceType)->count() + 1;
        $invNumber = $prefix . str_pad($nextCount, 3, '0', STR_PAD_LEFT);

        return view('sales.invoices.create', compact('customers', 'currencies', 'products', 'salespeople', 'invNumber', 'invoiceType'));
    }

    public function store(Request $request)
    {
        $this->checkStaffPermission('sales-invoices', 'create');
        $request->validate([
            'customer_id' => 'required|exists:associates,id',
            'invoice_date' => 'required|date',
            'items' => 'required|array|min:1',
        ]);

        $invoiceType = in_array($request->invoice_type, ['gst', 'non_gst']) ? $request->invoice_type : 'gst';

        DB::beginTransaction();
        try {
            $invoice = SalesInvoice::create([
                'invoice_number' => 'TEMP-' . uniqid('', true), // overwritten immediately below
                'invoice_type'   => $invoiceType,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'customer_id' => $request->customer_id,
                'salesperson_id' => $request->salesperson_id,
                'currency_id' => $request->currency_id,
                'exchange_rate' => $request->exchange_rate ?? 1,
                'billing_address' => $request->billing_address,
                'shipping_address' => $request->shipping_address,
                'reference_number' => $request->reference_number,
                'quotation_id' => $request->quotation_id,

                // Calculation fields (Source of Truth: Net = Subtotal + Tax)
                // gross_total = sum of line items (before bill discount)
                // subtotal = gross_total - discount_amount
                // net_total = subtotal + tax_amount
                'gross_total' => $request->gross_total ?? 0,
                'subtotal' => $request->subtotal ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'tax_amount' => $request->tax_amount ?? 0,
                'round_off' => $request->round_off ?? 0,
                'net_total' => $request->net_total ?? 0,
                'balance_due' => $request->net_total ?? 0,

                'remarks' => $request->remarks,
                'status' => 'draft',
            ]);

            // Generate type-specific sequential invoice number using count within each series
            $cs = CompanySetting::first();
            $prefix = $invoiceType === 'non_gst'
                ? ($cs->non_gst_invoice_prefix ?? 'INV-')
                : ($cs->gst_invoice_prefix ?? 'INVG');
            $sequenceNo = SalesInvoice::where('invoice_type', $invoiceType)->count();
            $invoice->update(['invoice_number' => $prefix . str_pad($sequenceNo, 3, '0', STR_PAD_LEFT)]);

            foreach ($request->items as $item) {
                // Determine Qty
                $productId = $item['product_id'];
                $lineQty = 0;
                $product = null;

                if ($productId) {
                    $product = Product::with('price')->find($productId);
                    $packSize = $product->pack_size_qty ?? 1;
                    $lineQty = ($item['box_qty'] * $packSize) + $item['item_qty'];

                    // Customer-Specific Pricing Logic: Only save if price was manually changed
                    $inputPrice = floatval($item['unit_price'] ?? 0);
                    if ($inputPrice > 0) {
                        $defaultPrice = $product->price?->sales_price_item ?? 0;

                        $priceResult = AssociateProductPrice::setCustomPrice(
                            $request->customer_id,
                            $productId,
                            $inputPrice,
                            $defaultPrice,
                            Auth::id(),
                            "Set from Invoice {$invoice->invoice_number}"
                        );

                        // Log if price was actually changed
                        if ($priceResult['saved']) {
                            AuditLog::log(
                                AuditLog::ACTION_PRICE_OVERRIDE,
                                AuditLog::MODULE_CUSTOMER_PRICING,
                                $invoice,
                                [
                                    'reference_code' => $invoice->invoice_number,
                                    'description' => "Custom price set for product {$product->sku}",
                                    'metadata' => [
                                        'associate_id' => $request->customer_id,
                                        'product_id' => $productId,
                                        'product_sku' => $product->sku,
                                        'old_price' => $priceResult['old_price'] ?? $defaultPrice,
                                        'new_price' => $inputPrice,
                                        'action' => $priceResult['action'],
                                    ],
                                ]
                            );
                        }
                    }
                } else {
                     // Service item
                     $lineQty = $item['quantity'] ?? 1;
                }

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'product_id' => $productId,
                    'alias_id' => $item['alias_id'] ?? null,
                    'gl_account' => $item['gl_account'] ?? null,
                    'item_description' => $item['description'] ?? ($product->name ?? ''),
                    'box_qty' => $item['box_qty'] ?? 0,
                    'item_qty' => $item['item_qty'] ?? 0,
                    'total_qty' => $lineQty,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'total_price' => $item['total_price'] ?? 0,
                ]);
            }

            // TODO: Post to GL if Accounting module exists

            DB::commit();
            return response()->json([
                'success' => true, 
                'message' => 'Sales Invoice created successfully!',
                'id' => $invoice->id,
                'redirect' => route('sales-invoices.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $invoice = SalesInvoice::with(['items.product.uom', 'items.product.packSize', 'customer', 'currency', 'salesperson'])->findOrFail($id);

        // Get customer's total outstanding balance
        $customerOutstandingBalance = $invoice->customer?->getTotalOutstandingBalance() ?? 0;

        return view('sales.invoices.show', compact('invoice', 'customerOutstandingBalance'));
    }

    public function thermalPrint($id)
    {
        $invoice = SalesInvoice::with(['items.product.uom', 'items.product.packSize', 'customer', 'currency', 'salesperson'])->findOrFail($id);
        $companySettings = \App\Models\CompanySetting::first();
        $customerOutstandingBalance = $invoice->customer?->getTotalOutstandingBalance() ?? 0;
        $hasNetworkPrinter = !empty($companySettings->thermal_printer_host);

        // ESC/POS payloads for RawBT — 80mm = 48 chars/line, 58mm = 32 chars/line
        $rawbtPayload80 = null;
        $rawbtPayload58 = null;
        try {
            $rawbtPayload80 = base64_encode(\App\Http\Controllers\PrintBridgeController::renderPayload(
                $invoice, $companySettings, (float) $customerOutstandingBalance, 'escpos', 48
            ));
            $rawbtPayload58 = base64_encode(\App\Http\Controllers\PrintBridgeController::renderPayload(
                $invoice, $companySettings, (float) $customerOutstandingBalance, 'escpos', 32
            ));
            \Log::info('[THERMAL-PRINT][SERVER] RawBT payloads built (80mm & 58mm)');
        } catch (\Throwable $e) {
            \Log::error('[THERMAL-PRINT][SERVER] RawBT payload build failed: ' . $e->getMessage());
        }

        return view('sales.invoices.thermal-print', compact(
            'invoice', 'companySettings', 'customerOutstandingBalance',
            'hasNetworkPrinter', 'rawbtPayload80', 'rawbtPayload58'
        ));
    }

    public function thermalEscposPrint($id)
    {
        try {
            return $this->_doEscposPrint($id);
        } catch (\Throwable $e) {
            // clean any open output buffers so the JSON response is not corrupted
            while (ob_get_level() > 0) { ob_end_clean(); }
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /* ─────────────────────────────────────────────────────────────
     |  QZ TRAY — certificate, signing, ESC/POS byte generation
     ───────────────────────────────────────────────────────────── */

    public function qzCertificate()
    {
        $dir  = storage_path('app/qz-tray');
        $cert = $dir . '/certificate.pem';
        $key  = $dir . '/private.pem';

        if (!file_exists($cert)) {
            if (!is_dir($dir)) mkdir($dir, 0750, true);

            $pk  = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            $csr = openssl_csr_new([
                'commonName'       => parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost',
                'organizationName' => config('app.name', 'WholeTrade'),
                'countryName'      => 'SG',
            ], $pk, ['digest_alg' => 'sha256']);
            $x509 = openssl_csr_sign($csr, null, $pk, 3650, ['digest_alg' => 'sha256']);

            openssl_x509_export($x509, $certPem);
            openssl_pkey_export($pk, $keyPem);

            file_put_contents($cert, $certPem);
            file_put_contents($key,  $keyPem);
            chmod($key, 0600);
        }

        return response(file_get_contents($cert), 200)
            ->header('Content-Type', 'text/plain')
            ->header('Cache-Control', 'no-store');
    }

    public function qzSign(Request $request)
    {
        $toSign  = $request->input('request', '');
        $keyFile = storage_path('app/qz-tray/private.pem');

        if (!file_exists($keyFile)) {
            $this->qzCertificate();
        }

        $privateKey = openssl_pkey_get_private(file_get_contents($keyFile));
        if (!$privateKey) {
            return response('Private key error', 500)->header('Content-Type', 'text/plain');
        }

        openssl_sign($toSign, $signature, $privateKey, 'SHA512');
        return response(base64_encode($signature), 200)
            ->header('Content-Type', 'text/plain');
    }

    public function qzEscposBytes($id)
    {
        $copies     = max(1, min(10, (int) request('copies', 1)));
        $paperWidth = (int) request('paper_width', 80);
        $W          = $paperWidth >= 80 ? 42 : 32;

        $invoice     = SalesInvoice::with(['items.product.uom', 'items.product.packSize', 'customer', 'currency', 'salesperson'])->findOrFail($id);
        $cs          = CompanySetting::first();
        $outstanding = $invoice->customer?->getTotalOutstandingBalance() ?? 0;
        $sym         = $invoice->currency?->symbol ?? '$';
        $gstOn       = isset($cs) && $cs->gst_include === 'Yes';

        $connector = new \Mike42\Escpos\PrintConnectors\DummyPrintConnector();
        $printer   = new \Mike42\Escpos\Printer($connector);

        for ($copy = 1; $copy <= $copies; $copy++) {
            if ($copy > 1) $printer->initialize();
            $this->_writeReceiptData($printer, $invoice, $cs, $outstanding, $sym, $gstOn, $W);
        }

        $bytes = $connector->getData();
        $printer->close();

        return response()->json([
            'data'   => base64_encode($bytes),
            'length' => strlen($bytes),
        ]);
    }

    public function bleEscposBytes($id)
    {
        $paperWidth = (int) request('paper_width', 80);
        $cols       = $paperWidth >= 80 ? 42 : 32;

        $invoice     = SalesInvoice::with(['items.product.uom', 'items.product.packSize', 'customer', 'currency', 'salesperson'])->findOrFail($id);
        $cs          = CompanySetting::first();
        $outstanding = $invoice->customer?->getTotalOutstandingBalance() ?? 0;

        $bytes = \App\Http\Controllers\PrintBridgeController::renderPayload(
            $invoice, $cs, (float) $outstanding, 'escpos', $cols
        );

        return response()->json([
            'data'   => base64_encode($bytes),
            'length' => strlen($bytes),
        ]);
    }

    public function getThermalPrinters()
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $raw = shell_exec('powershell -NoProfile -Command "Get-Printer | Select-Object -ExpandProperty Name | ConvertTo-Json -Compress" 2>$null');
                if ($raw) {
                    $names = json_decode($raw, true);
                    return response()->json(['printers' => is_array($names) ? $names : [$names]]);
                }
                return response()->json(['printers' => []]);
            }

            // Linux — mode is driven by .env
            $mode = env('THERMAL_PRINTER_MODE', 'network');

            if ($mode === 'network') {
                $host = env('THERMAL_PRINTER_HOST', '');
                $port = env('THERMAL_PRINTER_PORT', 9100);
                if (!$host) {
                    return response()->json(['printers' => [], 'error' => 'Set THERMAL_PRINTER_HOST in .env']);
                }
                return response()->json(['printers' => ["Network Printer ({$host}:{$port})"]]);
            }

            if ($mode === 'cups') {
                $raw      = shell_exec('lpstat -a 2>/dev/null') ?? '';
                $printers = [];
                foreach (explode("\n", trim($raw)) as $line) {
                    if ($line && preg_match('/^(\S+)/', $line, $m)) {
                        $printers[] = $m[1];
                    }
                }
                if (empty($printers) && env('THERMAL_PRINTER_NAME')) {
                    $printers = [env('THERMAL_PRINTER_NAME')];
                }
                return response()->json(['printers' => $printers]);
            }

            if ($mode === 'usb') {
                $device = env('THERMAL_PRINTER_DEVICE', '/dev/usb/lp0');
                return response()->json(['printers' => [basename($device)]]);
            }

            return response()->json(['printers' => []]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'printers' => []], 500);
        }
    }

    public function thermalDirectPrint(Request $request, $id)
    {
        try {
            return $this->_doEscposPrint(
                $id,
                (int) $request->input('copies', 1),
                (int) $request->input('paper_width', 80)
            );
        } catch (\Throwable $e) {
            while (ob_get_level() > 0) { ob_end_clean(); }
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function _makePrintConnector($cs = null)
    {
        // Company settings take priority over .env for all modes
        $csHost = $cs->thermal_printer_host ?? null;
        $csPort = (int) ($cs->thermal_printer_port ?? 9100) ?: 9100;

        // Network mode: IP stored in company settings or .env
        if ($csHost) {
            return new \Mike42\Escpos\PrintConnectors\NetworkPrintConnector($csHost, $csPort);
        }

        // Windows fallback: named printer
        if (PHP_OS_FAMILY === 'Windows') {
            $name = ($cs->thermal_printer_name ?? null) ?: env('THERMAL_PRINTER_NAME', '');
            if (!$name) throw new \Exception('No printer configured. Set the Printer IP in Company Settings.');
            return new \Mike42\Escpos\PrintConnectors\WindowsPrintConnector($name);
        }

        // Linux: check .env for legacy support
        $mode = env('THERMAL_PRINTER_MODE', 'network');

        if ($mode === 'network') {
            $host = env('THERMAL_PRINTER_HOST', '');
            $port = (int) env('THERMAL_PRINTER_PORT', 9100);
            if (!$host) throw new \Exception('Printer IP not configured. Go to Company Settings → Thermal Printer and enter the printer IP address.');
            return new \Mike42\Escpos\PrintConnectors\NetworkPrintConnector($host, $port);
        }

        if ($mode === 'cups') {
            $name = env('THERMAL_PRINTER_NAME', '');
            if (!$name) throw new \Exception('THERMAL_PRINTER_NAME not set in .env for CUPS mode');
            return new \Mike42\Escpos\PrintConnectors\CupsPrintConnector($name);
        }

        if ($mode === 'usb') {
            $device = env('THERMAL_PRINTER_DEVICE', '/dev/usb/lp0');
            return new \Mike42\Escpos\PrintConnectors\FilePrintConnector($device);
        }

        throw new \Exception("Unknown THERMAL_PRINTER_MODE '{$mode}'. Use: network, cups, or usb");
    }

    private function _doEscposPrint($id, $copies = 1, $paperWidth = 80)
    {
        $copies      = max(1, min(10, (int) $copies));
        $W           = $paperWidth >= 80 ? 42 : 32;
        $invoice     = SalesInvoice::with(['items.product.uom', 'items.product.packSize', 'customer', 'currency', 'salesperson'])->findOrFail($id);
        $cs          = CompanySetting::first();
        $outstanding = $invoice->customer?->getTotalOutstandingBalance() ?? 0;
        $sym         = $invoice->currency?->symbol ?? '$';
        $gstOn       = isset($cs) && $cs->gst_include === 'Yes';

        $connector = $this->_makePrintConnector($cs);
        $printer   = new \Mike42\Escpos\Printer($connector);

        for ($copy = 1; $copy <= $copies; $copy++) {
            if ($copy > 1) $printer->initialize();
            $this->_writeReceiptData($printer, $invoice, $cs, $outstanding, $sym, $gstOn, $W);
        }

        $printer->close();
        return response()->json(['success' => true, 'message' => "Printed {$copies} copy(s)"]);
    }

    private function _writeReceiptData($printer, $invoice, $cs, $outstanding, $sym, $gstOn, $W = 42)
    {
        $sep = str_repeat('-', $W);
        $dbl = str_repeat('=', $W);

        /* ── header ── */
        $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->setTextSize(2, 2);
        $printer->text(mb_strtoupper($cs->company_name ?? 'BUSINESS NAME') . "\n");
        $printer->setTextSize(1, 1);
        $printer->setEmphasis(false);

        if ($cs->address ?? null) {
            $printer->text($cs->address . "\n");
        }
        $phoneLine = implode(' | ', array_filter([
            ($cs->phone_no ?? null)       ? 'Ph: '      . $cs->phone_no       : null,
            ($cs->registration_no ?? null) ? 'Co.Reg: ' . $cs->registration_no : null,
        ]));
        if ($phoneLine) $printer->text($phoneLine . "\n");
        $emailUen = implode(' | ', array_filter([
            $cs->email  ?? null,
            ($cs->uen_no ?? null) ? 'UEN: ' . $cs->uen_no : null,
        ]));
        if ($emailUen) $printer->text($emailUen . "\n");

        $printer->text($sep . "\n");
        $printer->setEmphasis(true);
        $printer->text(($invoice->tax_amount > 0 ? 'TAX INVOICE' : 'INVOICE') . "\n");
        $printer->setEmphasis(false);
        $printer->text($sep . "\n");

        /* ── meta ── */
        $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_LEFT);
        $printer->text("Invoice No  : " . $invoice->invoice_number . "\n");
        $printer->text("Date        : " . \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') . "\n");
        if ($invoice->due_date) {
            $printer->text("Due Date    : " . \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') . "\n");
        }
        $printer->text("Customer    : " . ($invoice->customer->name ?? 'WALK-IN') . "\n");
        if ($invoice->billing_address) {
            $addrLines = explode("\n", wordwrap($invoice->billing_address, $W - 14, "\n", true));
            foreach ($addrLines as $i => $al) {
                $printer->text(($i === 0 ? "Address     : " : "              ") . $al . "\n");
            }
        }
        if ($invoice->salesperson) {
            $printer->text("Salesperson : " . $invoice->salesperson->name . "\n");
        }
        if ($invoice->reference_number) {
            $printer->text("Reference   : " . $invoice->reference_number . "\n");
        }
        $printer->text($sep . "\n");

        /* ── items ── */
        $c1 = 22; $c2 = 5; $c3 = 7; $c4 = 8;
        $printer->setEmphasis(true);
        $printer->text(
            str_pad('DESCRIPTION', $c1) .
            str_pad('QTY',   $c2, ' ', STR_PAD_LEFT) .
            str_pad('PRICE', $c3, ' ', STR_PAD_LEFT) .
            str_pad('AMT',   $c4, ' ', STR_PAD_LEFT) . "\n"
        );
        $printer->setEmphasis(false);
        $printer->text($sep . "\n");

        foreach ($invoice->items as $item) {
            $desc   = $item->item_description ?: ($item->product?->name ?? '');
            $qty    = $item->total_qty;
            $qtyFmt = ($qty == floor($qty)) ? (int) $qty : number_format($qty, 2);
            $chunks = str_split($desc, $c1);
            $first  = array_shift($chunks);
            $printer->text(
                str_pad($first, $c1) .
                str_pad($qtyFmt,                            $c2, ' ', STR_PAD_LEFT) .
                str_pad(number_format($item->unit_price,  2), $c3, ' ', STR_PAD_LEFT) .
                str_pad(number_format($item->total_price, 2), $c4, ' ', STR_PAD_LEFT) . "\n"
            );
            foreach ($chunks as $chunk) {
                $printer->text(str_pad($chunk, $c1) . "\n");
            }
        }
        $printer->text($dbl . "\n");

        /* ── totals ── */
        $rW = $W - 24;
        $printer->text(str_pad('Sub Total', 24) . str_pad(number_format($invoice->subtotal, 2), $rW, ' ', STR_PAD_LEFT) . "\n");
        if ($invoice->discount_amount > 0) {
            $printer->text(str_pad('Discount', 24) . str_pad('-' . number_format($invoice->discount_amount, 2), $rW, ' ', STR_PAD_LEFT) . "\n");
        }
        if ($invoice->tax_amount > 0) {
            $taxBase = max(0, ($invoice->subtotal ?? 0) - ($invoice->discount_amount ?? 0));
            $rate = $taxBase > 0 ? (int) round($invoice->tax_amount / $taxBase * 100) : 0;
            $printer->text(str_pad("GST ({$rate}%)", 24) . str_pad(number_format($invoice->tax_amount, 2), $rW, ' ', STR_PAD_LEFT) . "\n");
        }
        $printer->text($dbl . "\n");

        $printer->setEmphasis(true);
        $printer->setTextSize(1, 2);
        $netLabel = "Net Total ({$sym})";
        $netAmt   = number_format($invoice->net_total, 2);
        $printer->text(str_pad($netLabel, 24) . str_pad($netAmt, $rW, ' ', STR_PAD_LEFT) . "\n");
        $printer->setTextSize(1, 1);
        $printer->setEmphasis(false);

        if ($invoice->paid_amount > 0) {
            $printer->text($sep . "\n");
            $printer->text(str_pad('Paid Amount', 24) . str_pad(number_format($invoice->paid_amount,  2), $rW, ' ', STR_PAD_LEFT) . "\n");
            $printer->setEmphasis(true);
            $printer->text(str_pad('Balance Due', 24) . str_pad(number_format($invoice->balance_due, 2), $rW, ' ', STR_PAD_LEFT) . "\n");
            $printer->setEmphasis(false);
        }

        $printer->text($sep . "\n");
        $printer->setEmphasis(true);
        $printer->text(str_pad('THIS INVOICE :', 24) . str_pad("{$sym} " . number_format($invoice->balance_due, 2), $rW, ' ', STR_PAD_LEFT) . "\n");
        $printer->setEmphasis(false);
        if ($outstanding > 0) {
            $printer->text(str_pad('TOTAL OUTSTANDING:', 24) . str_pad("{$sym} " . number_format($outstanding, 2), $rW, ' ', STR_PAD_LEFT) . "\n");
        }

        if ($invoice->remarks) {
            $printer->text($sep . "\n");
            $printer->text("Remarks:\n");
            $printer->text(wordwrap($invoice->remarks, $W, "\n", true) . "\n");
        }

        /* ── payment instructions ── */
        $printer->text($sep . "\n");
        $printer->text("PAYMENT INSTRUCTIONS\n");
        $printer->text("Cheques payable to:\n");
        $printer->setEmphasis(true);
        $printer->text(mb_strtoupper($cs->company_name ?? 'BUSINESS NAME') . "\n");
        $printer->setEmphasis(false);
        if (($cs->bank_name ?? null) || ($cs->bank_account_no ?? null)) {
            $printer->text("BANK TRANSFER:\n");
            if ($cs->bank_name       ?? null) $printer->text("Bank    : " . $cs->bank_name       . "\n");
            if ($cs->bank_account_no ?? null) $printer->text("Acct No : " . $cs->bank_account_no . "\n");
            if ($cs->uen_no          ?? null) $printer->text("UEN No  : " . $cs->uen_no          . "\n");
        }

        /* ── footer ── */
        $printer->text($sep . "\n");
        $printer->feed(3);
        $printer->setJustification(\Mike42\Escpos\Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text("* THANK YOU FOR THE BUSINESS *\n");
        $printer->setEmphasis(false);
        $printer->text($sep . "\n");
        $printer->text("Powered by Techgeeks\n");
        $printer->feed(5);

        /* ── cut after every copy ── */
        $printer->cut();
    }

    public function print($id)
    {
        $invoice = SalesInvoice::with(['items.product.uom', 'items.product.packSize', 'customer', 'currency', 'salesperson'])->findOrFail($id);
        $companySettings = \App\Models\CompanySetting::first();

        // Get customer's total outstanding balance
        $customerOutstandingBalance = $invoice->customer?->getTotalOutstandingBalance() ?? 0;

        return view('sales.invoices.print', compact('invoice', 'companySettings', 'customerOutstandingBalance'));
    }

    /**
     * Show the form for editing a draft invoice.
     */
    public function edit($id)
    {
        $invoice = SalesInvoice::with('items.product', 'customer.tax', 'currency', 'salesperson')->findOrFail($id);

        // Only draft invoices can be edited
        if (!$invoice->canEdit()) {
            return redirect()->route('sales-invoices.show', $id)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $customers = Associate::where('type', 'Customer')->where('status', true)->with('tax')->get();
        $currencies = Currency::all(); // BUG-009 FIX
        $products = Product::where('status', true)
            ->with([
                'price', 'packSize', 'uom',
                'aliases' => fn($q) => $q->withoutGlobalScope('subscriber')->where('status', true),
            ])
            ->get();
        $salespeople = User::whereHas('roles', function($q) {
            $q->where('name', 'salesperson');
        })->get();

        return view('sales.invoices.edit', compact('invoice', 'customers', 'currencies', 'products', 'salespeople'));
    }

    /**
     * Update a draft invoice.
     */
    public function update(Request $request, $id)
    {
        $this->checkStaffPermission('sales-invoices', 'edit');
        $invoice = SalesInvoice::findOrFail($id);

        if (!$invoice->canEdit()) {
            return response()->json([
                'success' => false,
                'error' => 'Only draft invoices can be edited.'
            ], 403);
        }

        // BUG-005 FIX: invoice_number is system-generated; no longer validated from request.
        $request->validate([
            'customer_id' => 'required|exists:associates,id',
            'invoice_date' => 'required|date',
            'items' => 'required|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $newNetTotal = $request->net_total ?? 0;

            // BUG-016 FIX: balance_due must account for payments already made, not blindly reset to net_total.
            $newBalanceDue = max(0, $newNetTotal - $invoice->paid_amount);

            $invoice->update([
                // invoice_number intentionally omitted — system-generated, must not change after creation
                'invoice_date'     => $request->invoice_date,
                'due_date'         => $request->due_date,
                'customer_id'      => $request->customer_id,
                'salesperson_id'   => $request->salesperson_id,
                'currency_id'      => $request->currency_id,
                'exchange_rate'    => $request->exchange_rate ?? 1,
                'billing_address'  => $request->billing_address,
                'shipping_address' => $request->shipping_address,
                'reference_number' => $request->reference_number,
                'gross_total'      => $request->gross_total ?? 0,
                'subtotal'         => $request->subtotal ?? 0,
                'discount_amount'  => $request->discount_amount ?? 0,
                'tax_amount'       => $request->tax_amount ?? 0,
                'round_off'        => $request->round_off ?? 0,
                'net_total'        => $newNetTotal,
                'balance_due'      => $newBalanceDue,
                'remarks'          => $request->remarks,
            ]);

            // Delete old items and create new ones
            $invoice->items()->delete();

            foreach ($request->items as $item) {
                $productId = $item['product_id'];
                $lineQty = 0;
                $product = null;

                if ($productId) {
                    $product = Product::with('price')->find($productId);
                    $packSize = $product->pack_size_qty ?? 1;
                    $lineQty = ($item['box_qty'] * $packSize) + $item['item_qty'];

                    // Customer-Specific Pricing Logic: Only save if price was manually changed
                    $inputPrice = floatval($item['unit_price'] ?? 0);
                    if ($inputPrice > 0) {
                        $defaultPrice = $product->price?->sales_price_item ?? 0;

                        $priceResult = AssociateProductPrice::setCustomPrice(
                            $request->customer_id,
                            $productId,
                            $inputPrice,
                            $defaultPrice,
                            Auth::id(),
                            "Updated from Invoice {$invoice->invoice_number}"
                        );

                        // Log if price was actually changed
                        if ($priceResult['saved']) {
                            AuditLog::log(
                                AuditLog::ACTION_PRICE_OVERRIDE,
                                AuditLog::MODULE_CUSTOMER_PRICING,
                                $invoice,
                                [
                                    'reference_code' => $invoice->invoice_number,
                                    'description' => "Custom price updated for product {$product->sku}",
                                    'metadata' => [
                                        'associate_id' => $request->customer_id,
                                        'product_id' => $productId,
                                        'product_sku' => $product->sku,
                                        'old_price' => $priceResult['old_price'] ?? $defaultPrice,
                                        'new_price' => $inputPrice,
                                        'action' => $priceResult['action'],
                                    ],
                                ]
                            );
                        }
                    }
                } else {
                    $lineQty = $item['quantity'] ?? 1;
                }

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'product_id' => $productId,
                    'alias_id' => $item['alias_id'] ?? null,
                    'gl_account' => $item['gl_account'] ?? null,
                    'item_description' => $item['description'] ?? ($product->name ?? ''),
                    'box_qty' => $item['box_qty'] ?? 0,
                    'item_qty' => $item['item_qty'] ?? 0,
                    'total_qty' => $lineQty,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'total_price' => $item['total_price'] ?? 0,
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Sales Invoice updated successfully!',
                'redirect' => route('sales-invoices.show', $id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Confirm a draft invoice and deduct inventory.
     */
    public function confirm($id)
    {
        $this->checkStaffPermission('sales-invoices', 'edit');
        $invoice = SalesInvoice::with('items.product')->findOrFail($id);

        // Only draft invoices can be confirmed
        if (!$invoice->canConfirm()) {
            return response()->json([
                'success' => false,
                'error' => 'Only draft invoices can be confirmed.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Deduct inventory for each item with audit logging
            foreach ($invoice->items as $item) {
                if ($item->product_id) {
                    $product = $item->product;

                    StockService::deductStock(
                        $product,
                        (int) $item->box_qty,
                        (int) $item->item_qty,
                        'Sales Invoice',
                        [
                            'invoice_number' => $invoice->invoice_number,
                            'invoice_id' => $invoice->id,
                            'customer_id' => $invoice->customer_id,
                            'item_id' => $item->id,
                        ]
                    );
                }
            }

            // Update invoice status to confirmed
            $invoice->update(['status' => 'confirmed']);

            // Log the confirmation action
            AuditLog::log(
                AuditLog::ACTION_CONFIRM,
                AuditLog::MODULE_SALES_INVOICE,
                $invoice,
                [
                    'reference_code' => $invoice->invoice_number,
                    'description' => "Sales Invoice {$invoice->invoice_number} confirmed",
                    'metadata' => [
                        'customer_id' => $invoice->customer_id,
                        'net_total' => $invoice->net_total,
                        'items_count' => $invoice->items->count(),
                    ],
                ]
            );

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Invoice confirmed successfully! Inventory has been deducted.',
                'redirect' => route('sales-invoices.show', $id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Void a confirmed/posted invoice that has no payments recorded.
     * Reverses inventory deductions and marks the invoice as voided.
     */
    public function void($id)
    {
        $this->checkStaffPermission('sales-invoices', 'edit');
        $invoice = SalesInvoice::with('items.product')->findOrFail($id);

        if (!in_array($invoice->status, ['confirmed', 'posted'])) {
            return response()->json([
                'success' => false,
                'error'   => 'Only confirmed or posted invoices can be voided.',
            ], 422);
        }

        if ($invoice->paid_amount > 0) {
            return response()->json([
                'success' => false,
                'error'   => 'Cannot void an invoice that has payments recorded. Reverse the payment first.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Restore inventory that was deducted when invoice was confirmed
            foreach ($invoice->items as $item) {
                if ($item->product_id && $item->product) {
                    StockService::addStock(
                        $item->product,
                        (int) $item->box_qty,
                        (int) $item->item_qty,
                        'Invoice Voided',
                        [
                            'invoice_number' => $invoice->invoice_number,
                            'invoice_id'     => $invoice->id,
                            'customer_id'    => $invoice->customer_id,
                        ]
                    );
                }
            }

            $invoice->update([
                'status'         => 'voided',
                'balance_due'    => 0,
                'payment_status' => 'voided',
            ]);

            AuditLog::log(
                AuditLog::ACTION_CANCEL,
                AuditLog::MODULE_SALES_INVOICE,
                $invoice,
                [
                    'reference_code' => $invoice->invoice_number,
                    'description'    => "Invoice {$invoice->invoice_number} voided — inventory restored",
                ]
            );

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Invoice voided. Inventory has been restored.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get the customer's total outstanding balance.
     * Includes both old (invoice_references) and new (sales_invoices) systems.
     */
    public function getCustomerLastCredit($customerId)
    {
        $customer = Associate::find($customerId);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ]);
        }

        // Get total outstanding from both old and new systems
        $totalOutstanding = $customer->getTotalOutstandingBalance();

        // Also get the last invoice reference for backward compatibility
        $lastInvoice = InvoiceReference::where('customer_id', $customerId)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return response()->json([
            'success' => true,
            'balance' => $totalOutstanding, // Total outstanding balance
            'old_balance' => $lastInvoice?->balance ?? 0,
            'invoice_no' => $lastInvoice?->invoice_no,
            'net_total' => $lastInvoice?->net_total ?? 0,
            'paid' => $lastInvoice?->paid ?? 0,
            'date' => $lastInvoice?->date?->format('Y-m-d'),
        ]);
    }

    /**
     * Show payment form for an invoice.
     */
    public function showPaymentForm($id)
    {
        $invoice = SalesInvoice::with(['customer', 'currency', 'receipts'])
            ->findOrFail($id);

        // Check if invoice can receive payment (must be confirmed, not draft)
        if (!$invoice->canReceivePayment()) {
            return redirect()->route('sales-invoices.show', $id)
                ->with('error', 'This invoice cannot receive payments. It must be confirmed first.');
        }

        $currencies = Currency::where('is_default', 1)->get();

        // Generate receipt number
        $nextId = SalesReceipt::max('id') + 1;
        $rcpNumber = 'RCP-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        // Get payment history for this invoice
        $paymentHistory = $invoice->receipts;

        $companySettings = CompanySetting::first();

        return view('sales.invoices.pay', compact('invoice', 'currencies', 'rcpNumber', 'paymentHistory', 'companySettings'));
    }

    /**
     * Store payment for an invoice.
     */
    public function storePayment(Request $request, $id)
    {
        $request->validate([
            'receipt_number' => 'required|unique:sales_receipts,receipt_number',
            'amount' => 'required|numeric|min:0.01',
            'receipt_date' => 'required|date',
        ]);

        $invoice = SalesInvoice::findOrFail($id);

        // Guard: only confirmed/posted invoices can receive payment
        if (!$invoice->canReceivePayment()) {
            return response()->json([
                'success' => false,
                'error' => 'This invoice cannot receive payments. It must be confirmed first.'
            ], 403);
        }

        // BUG-018 FIX: apply the same 0.01 rounding tolerance in the guard that is used
        // when determining "paid" status, so both checks behave consistently.
        if ($request->amount > ($invoice->balance_due + 0.01)) {
            return response()->json([
                'success' => false,
                'error' => 'Payment amount cannot exceed the balance due.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // 1. Create Receipt
            $receipt = SalesReceipt::create([
                'receipt_number' => $request->receipt_number,
                'receipt_date' => $request->receipt_date,
                'customer_id' => $invoice->customer_id,
                'currency_id' => $request->currency_id ?? $invoice->currency_id,
                'amount' => $request->amount,
                'unused_amount' => 0, // Fully allocated to this invoice
                'payment_mode' => $request->payment_mode,
                'reference' => $request->reference,
                'remarks' => $request->remarks ?? "Payment for Invoice: {$invoice->invoice_number}",
                'status' => 'posted',
            ]);

            // 2. Create allocation (link receipt to invoice)
            $receipt->invoices()->attach($invoice->id, [
                'allocated_amount' => $request->amount
            ]);

            // 3. Update invoice payment fields
            $invoice->paid_amount += floatval($request->amount);
            $invoice->balance_due -= floatval($request->amount);

            // Update payment status
            if ($invoice->balance_due <= 0.01) { // Tolerance for rounding
                $invoice->balance_due = 0;
                $invoice->payment_status = 'paid';
            } else {
                $invoice->payment_status = 'partial';
            }
            $invoice->save();

            // Log the payment action
            AuditLog::log(
                AuditLog::ACTION_PAYMENT,
                AuditLog::MODULE_SALES_INVOICE,
                $invoice,
                [
                    'reference_code' => $invoice->invoice_number,
                    'description' => "Payment of {$request->amount} recorded for Invoice {$invoice->invoice_number}",
                    'metadata' => [
                        'receipt_id' => $receipt->id,
                        'receipt_number' => $receipt->receipt_number,
                        'amount' => $request->amount,
                        'payment_mode' => $request->payment_mode,
                        'new_balance' => $invoice->balance_due,
                        'payment_status' => $invoice->payment_status,
                    ],
                ]
            );

            DB::commit();

            $redirectUrl = $request->input('return_url');
            if (! $redirectUrl || ! str_starts_with($redirectUrl, url('/'))) {
                $redirectUrl = route('sales-invoices.index');
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully!',
                'receipt_id' => $receipt->id,
                'redirect' => $redirectUrl
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('storePayment error for invoice ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Payment could not be recorded. Please try again.'], 500);
        }
    }

    /**
     * Get product details with stock information.
     * Returns customer-specific price if exists, otherwise default price.
     */
    public function getProductStock($productId, Request $request)
    {
        // Support alias lookup: if productId is prefixed with "alias:" resolve via alias table
        $aliasId = null;
        $aliasName = null;
        if ($request->filled('alias_id')) {
            $alias = ProductAlias::withoutGlobalScope('subscriber')
                ->where('status', true)->find($request->alias_id);
            if ($alias) {
                $aliasId = $alias->id;
                $aliasName = $alias->alias_name;
                $productId = $alias->product_id;
            }
        }

        $product = Product::with(['price', 'inventory', 'packSize', 'uom'])->find($productId);

        if ($product) {
            $defaultPrice = $product->price?->sales_price_item ?? 0;
            $salesPrice = $defaultPrice;
            $isCustomPrice = false;

            // Customer-Specific Pricing: Check for active custom price
            if ($request->filled('associate_id')) {
                $customPrice = AssociateProductPrice::getCustomPrice(
                    (int) $request->associate_id,
                    (int) $productId
                );

                if ($customPrice !== null) {
                    $salesPrice = $customPrice;
                    $isCustomPrice = true;
                }
            }

            return response()->json([
                'success' => true,
                'product' => [
                    'id'              => $product->id,
                    'sku'             => $product->sku,
                    'name'            => $aliasName ?? $product->name,
                    'original_name'   => $product->name,
                    'alias_id'        => $aliasId,
                    'alias_name'      => $aliasName,
                    'stock_qty_pcs'   => $product->stock_qty_pcs ?? 0,
                    'stock_qty_box'   => $product->stock_qty_box ?? 0,
                    'stock_in_hand'   => $product->inventory?->stock_in_hand ?? 0,
                    'pack_size_qty'   => $product->pack_size_qty ?? 1,
                    'cost_price_item' => $product->price?->cost_price_item ?? 0,
                    'cost_price_box'  => $product->price?->cost_price_box ?? 0,
                    'sales_price_item' => $salesPrice,
                    'cost_price'      => $product->price?->cost_price_item ?? 0,
                    'sales_price_box' => $product->price?->sales_price_box ?? 0,
                    'tax_rate'        => $product->price?->tax_rate ?? 0,
                    'default_price'   => $defaultPrice,
                    'is_custom_price' => $isCustomPrice,
                    'uom_name'        => $product->uom?->name ?? '',
                    'pack_size_name'  => $product->packSize?->name ?? '',
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Product not found'
        ]);
    }

    /**
     * Get all custom prices for a customer.
     * Useful for viewing/managing customer-specific pricing.
     */
    public function getCustomerPrices($customerId)
    {
        $prices = AssociateProductPrice::with(['product:id,sku,name', 'setter:id,name'])
            ->where('associate_id', $customerId)
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($price) {
                return [
                    'id' => $price->id,
                    'product_id' => $price->product_id,
                    'product_sku' => $price->product?->sku,
                    'product_name' => $price->product?->name,
                    'unit_price' => $price->unit_price,
                    'original_price' => $price->original_price,
                    'discount_percent' => $price->original_price > 0
                        ? round((1 - ($price->unit_price / $price->original_price)) * 100, 2)
                        : 0,
                    'set_by' => $price->setter?->name,
                    'reason' => $price->reason,
                    'updated_at' => $price->updated_at?->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'count' => $prices->count(),
            'prices' => $prices,
        ]);
    }
}
