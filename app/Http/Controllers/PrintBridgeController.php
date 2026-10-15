<?php

namespace App\Http\Controllers;

use App\Models\PrintJob;
use App\Models\SalesInvoice;
use App\Models\CompanySetting;
use App\Services\Printing\ReceiptBuilder;
use App\Services\Printing\Drivers\EscposDriver;
use App\Services\Printing\Drivers\TsplDriver;
use App\Services\Printing\Drivers\TextDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrintBridgeController extends Controller
{
    /* ══════════════════════════════════════════════════════════════
       WEB (auth-protected) ENDPOINTS
       ══════════════════════════════════════════════════════════════ */

    /**
     * Enqueue a print job for the customer detailed report.
     * Called by ⚡ Direct Print on the thermal report page.
     *
     * POST reports/customers/direct-print-thermal
     * Body: { format, station, width, copies, filters: { date_from, date_to, customer_id, payment_status } }
     */
    public function enqueueReport(Request $request)
    {
        try {
            $format  = in_array($request->input('format'), ['escpos', 'text']) ? $request->input('format') : 'escpos';
            $station = preg_replace('/[^a-z0-9_-]/i', '', $request->input('station', 'main')) ?: 'main';
            $width   = (int) $request->input('width', 80);
            $copies  = max(1, min(10, (int) $request->input('copies', 1)));
            $cols    = $width >= 80 ? 42 : 32;
            $filters = (array) $request->input('filters', []);

            $cs = CompanySetting::first();
            [$detailed, $summary] = $this->_queryReportData($filters);

            $payload = static::buildReportPayload($detailed, $summary, $cs, $filters, $cols);
            if ($copies > 1) {
                $payload = str_repeat($payload, $copies);
            }

            $job = PrintJob::create([
                'invoice_id' => 0,
                'station'    => $station,
                'format'     => $format,
                'payload'    => base64_encode($payload),
                'copies'     => $copies,
                'cols'       => $cols,
                'status'     => 'pending',
            ]);

            Log::info('[PRINT-BRIDGE] report job created', ['job_id' => $job->id, 'bytes' => strlen($payload), 'cols' => $cols]);

            return response()->json(['success' => true, 'job_id' => $job->id]);

        } catch (\Throwable $e) {
            Log::error('[PRINT-BRIDGE] enqueueReport error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Enqueue a print job for the given invoice.
     * Called by the ⚡ Direct Print button in the browser.
     *
     * POST sales-invoices/{id}/direct-print
     * Body: { format: 'escpos'|'tspl'|'text', station: 'main', width: 80, copies: 1 }
     */
    public function enqueue(Request $request, $id)
    {
        try {
            $invoice  = SalesInvoice::with(['items.product.uom', 'items.product.packSize', 'customer', 'currency', 'salesperson'])
                ->findOrFail($id);
            $cs       = CompanySetting::first();

            $format   = in_array($request->input('format'), ['escpos','tspl','text'])
                        ? $request->input('format') : 'escpos';
            $station  = preg_replace('/[^a-z0-9_-]/i', '', $request->input('station', 'main')) ?: 'main';
            $width    = (int) $request->input('width', 80);
            $copies   = max(1, min(10, (int) $request->input('copies', 1)));
            $cols     = $width >= 80 ? 42 : 32;

            Log::info('[PRINT-BRIDGE] enqueue', [
                'invoice' => $invoice->invoice_number,
                'format'  => $format,
                'station' => $station,
                'cols'    => $cols,
                'copies'  => $copies,
            ]);

            $outstanding = $invoice->customer?->getTotalOutstandingBalance() ?? 0;
            $ops     = (new ReceiptBuilder)->build($invoice, $cs, (float) $outstanding);
            $payload = $this->_buildPayload($ops, $format, $cols, $copies);

            $job = PrintJob::create([
                'invoice_id' => $invoice->id,
                'station'    => $station,
                'format'     => $format,
                'payload'    => base64_encode($payload),
                'copies'     => $copies,
                'cols'       => $cols,
                'status'     => 'pending',
            ]);

            Log::info('[PRINT-BRIDGE] job created', ['job_id' => $job->id, 'bytes' => strlen($payload)]);

            return response()->json(['success' => true, 'job_id' => $job->id]);

        } catch (\Throwable $e) {
            Log::error('[PRINT-BRIDGE] enqueue error: ' . $e->getMessage(), ['invoice_id' => $id]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Poll the status of a print job.
     * Called every 2 s by the browser's pollJob() loop.
     *
     * GET print-jobs/{id}/status
     */
    public function status($id)
    {
        $job = PrintJob::findOrFail($id);
        return response()->json([
            'status'  => $job->status,
            'error'   => $job->error,
        ]);
    }

    /**
     * Client-side debug logger — ships browser console lines to laravel.log.
     * Useful for diagnosing production issues without browser devtools.
     *
     * POST sales-invoices/thermal-print/client-log
     */
    public function clientLog(Request $request)
    {
        Log::info('[THERMAL-PRINT] ' . $request->input('step', '(no step)'), [
            'invoice' => $request->input('invoice'),
            'detail'  => $request->input('detail'),
        ]);
        return response()->json(['ok' => true]);
    }

    /* ══════════════════════════════════════════════════════════════
       BRIDGE API (no session auth, token-protected via header)
       Consumed by the Node.js print-bridge client at the shop.
       CSRF is excluded for print-bridge/* in bootstrap/app.php.
       ══════════════════════════════════════════════════════════════ */

    /**
     * Exchange a company code for the bridge token.
     * Called once by the Android "Whole Trade Print Service" app at login,
     * so the token never has to be typed on the phone.
     *
     * POST print-bridge/register
     * Body: { company_code: 'XXXX', station: 'main' }
     */
    public function register(Request $request)
    {
        $expected = config('services.print_bridge.company_code');
        $token    = config('services.print_bridge.token');

        if (!$expected || !$token) {
            Log::warning('[PRINT-BRIDGE] register attempted but PRINT_BRIDGE_COMPANY_CODE / PRINT_BRIDGE_TOKEN not set in .env');
            return response()->json(['success' => false, 'error' => 'Print service not configured on server'], 503);
        }

        $code = (string) $request->input('company_code', '');
        if (!hash_equals($expected, $code)) {
            Log::warning('[PRINT-BRIDGE] register: wrong company code', ['ip' => $request->ip()]);
            return response()->json(['success' => false, 'error' => 'Invalid company code'], 401);
        }

        $station = preg_replace('/[^a-z0-9_-]/i', '', $request->input('station', 'main')) ?: 'main';
        $cs      = CompanySetting::first();

        Log::info('[PRINT-BRIDGE] register OK', ['ip' => $request->ip(), 'station' => $station]);

        return response()->json([
            'success'      => true,
            'token'        => $token,
            'station'      => $station,
            'company_name' => $cs->company_name ?? 'Whole Trade',
            'poll_seconds' => 2,
        ]);
    }

    /**
     * Return the next pending job for a given station.
     * The bridge client calls this every 2 s.
     *
     * GET print-bridge/pending?station=main&format=escpos
     */
    public function pending(Request $request)
    {
        if (!$this->_authBridge($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $station = preg_replace('/[^a-z0-9_-]/i', '', $request->query('station', 'main')) ?: 'main';
        $format  = in_array($request->query('format'), ['escpos','tspl','text'])
                   ? $request->query('format') : 'escpos';

        $requestedJobId = (int) $request->query('job_id', 0);
        $query = PrintJob::where('status', 'pending')
            ->where('station', $station)
            ->where('format',  $format);

        if ($requestedJobId > 0) {
            $query->where('id', $requestedJobId);
        }

        $job = $query->orderBy('id')->first();

        if (!$job) {
            return response()->json(['job' => null]);
        }

        // Immediately mark as printing to prevent double-pickup
        $job->markPrinting();

        Log::info('[PRINT-BRIDGE] job picked up by bridge', [
            'job_id'  => $job->id,
            'station' => $station,
            'format'  => $format,
        ]);

        return response()->json([
            'job' => [
                'id'           => $job->id,
                'payload'      => $job->payload, // base64
                'copies'       => $job->copies,
                'format'       => $job->format,
                'cols'         => $job->cols,
                'preview_html' => $this->_buildPreviewHtml($job),
            ],
        ]);
    }

    /**
     * Bridge acknowledges a job as done or failed.
     *
     * POST print-bridge/ack/{id}
     * Body: { status: 'done'|'failed', error: '...' }
     */
    public function ack(Request $request, $id)
    {
        if (!$this->_authBridge($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $job    = PrintJob::findOrFail($id);
        $status = $request->input('status', 'done');
        $error  = $request->input('error');

        if ($status === 'done') {
            $job->markDone();
            Log::info('[PRINT-BRIDGE] job DONE', ['job_id' => $id]);
        } else {
            $job->markFailed($error ?? 'Bridge reported failure');
            Log::error('[PRINT-BRIDGE] job FAILED', ['job_id' => $id, 'error' => $error]);
        }

        return response()->json(['ok' => true]);
    }

    /* ══════════════════════════════════════════════════════════════
       PRIVATE HELPERS
       ══════════════════════════════════════════════════════════════ */

    /**
     * Build raw printer bytes for a given invoice/width.
     * Used by SalesInvoiceController to pre-generate RawBT payloads.
     */
    public static function renderPayload(
        SalesInvoice $invoice,
        ?CompanySetting $cs,
        float $outstanding,
        string $format,
        int $cols
    ): string {
        $ops = (new ReceiptBuilder)->build($invoice, $cs, $outstanding);
        return match($format) {
            'tspl'  => (new TsplDriver($cols))->render($ops),
            'text'  => (new TextDriver($cols))->render($ops),
            default => (new EscposDriver($cols))->render($ops),
        };
    }

    private function _buildPayload(array $ops, string $format, int $cols, int $copies): string
    {
        $single = match($format) {
            'tspl'  => (new TsplDriver($cols))->render($ops),
            'text'  => (new TextDriver($cols))->render($ops),
            default => (new EscposDriver($cols))->render($ops),
        };

        if ($copies <= 1) {
            return $single;
        }

        // Each copy already starts with 'init' (ESC @) and ends with a feed,
        // so plain concatenation is safe. Do NOT insert an extra ESC @ between
        // copies — a mid-stream reset can clear the printer buffer while the
        // previous copy is still printing and truncate the next one.
        return str_repeat($single, $copies);
    }

    /**
     * Render a self-contained HTML receipt for the Android WebView preview.
     * Returns null on any error so the app can fall back gracefully.
     */
    private function _buildPreviewHtml(PrintJob $job): ?string
    {
        try {
            $invoice = SalesInvoice::with([
                'items.product.uom',
                'items.product.packSize',
                'customer',
                'currency',
                'salesperson',
            ])->find($job->invoice_id);

            if (!$invoice) return null;

            $cs          = CompanySetting::first();
            $outstanding = $invoice->customer?->getTotalOutstandingBalance() ?? 0;

            return view('print_bridge.receipt_preview', [
                'invoice'                    => $invoice,
                'companySettings'            => $cs,
                'customerOutstandingBalance' => $outstanding,
            ])->render();
        } catch (\Throwable $e) {
            Log::warning('[PRINT-BRIDGE] preview_html render failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build ESC/POS bytes for the customer detailed report.
     * Called by enqueueReport() and CustomerReportController::printDetailedThermal().
     */
    public static function buildReportPayload(
        \Illuminate\Support\Collection $detailed,
        \Illuminate\Support\Collection $summary,
        ?CompanySetting $cs,
        array $filters,
        int $cols
    ): string {
        $ESC = "\x1b";
        $GS  = "\x1d";

        $init    = $ESC . '@';
        $center  = $ESC . 'a' . "\x01";
        $left    = $ESC . 'a' . "\x00";
        $boldOn  = $ESC . 'E' . "\x01";
        $boldOff = $ESC . 'E' . "\x00";
        $dblH    = $ESC . '!' . "\x10";
        $stdSz   = $ESC . '!' . "\x00";
        $feed    = $ESC . 'd' . "\x05";
        $cut     = $GS  . 'V' . "\x41" . "\x05";

        $dash = str_repeat('-', $cols);
        $eq   = str_repeat('=', $cols);
        $out  = $init;

        // ── Header ──
        $out .= $center . $boldOn . $dblH;
        $out .= self::_trunc($cs->company_name ?? 'BUSINESS NAME', $cols) . "\n";
        $out .= $stdSz . $boldOff;
        if ($cs->address ?? null) {
            foreach (explode("\n", wordwrap((string) $cs->address, $cols, "\n", true)) as $ln) {
                $out .= self::_trunc(trim($ln), $cols) . "\n";
            }
        }
        if ($cs->phone_no ?? null) {
            $out .= 'Ph: ' . $cs->phone_no . "\n";
        }
        $out .= $left . $dash . "\n";
        $out .= $center . $boldOn . 'CUSTOMER DETAILED REPORT' . $boldOff . "\n";
        $out .= now()->format('d M Y, h:i A') . "\n" . $left;

        // ── Filters ──
        if (!empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['payment_status'])) {
            $out .= $dash . "\n";
            if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
                $from = !empty($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d M Y') : '---';
                $to   = !empty($filters['date_to'])   ? \Carbon\Carbon::parse($filters['date_to'])->format('d M Y')   : '---';
                $out .= $boldOn . 'Period: ' . $boldOff . "$from to $to\n";
            }
            if (!empty($filters['payment_status'])) {
                $out .= $boldOn . 'Status: ' . $boldOff . ucfirst($filters['payment_status']) . "\n";
            }
        }
        $out .= $dash . "\n";

        // ── Column widths: Invoice | Date | Total | Balance ──
        if ($cols >= 42) {
            [$iW, $dW, $tW, $bW] = [12, 9, 11, 10];
        } else {
            [$iW, $dW, $tW, $bW] = [10, 7, 8, 7];
        }

        // ── Per-customer sections ──
        $grouped = $detailed->groupBy('customer_name');
        foreach ($grouped as $cname => $invs) {
            $out .= $boldOn . self::_trunc(strtoupper((string) $cname), $cols) . $boldOff . "\n";
            $out .= self::_trunc($invs->count() . ' inv T:' . number_format((float) $invs->sum('net_total'), 2) . ' B:' . number_format((float) $invs->sum('balance_due'), 2), $cols) . "\n";

            $hdr = str_pad('Invoice', $iW) . str_pad('Date', $dW) . str_pad('Total', $tW, ' ', STR_PAD_LEFT) . str_pad('Bal', $bW, ' ', STR_PAD_LEFT);
            $out .= substr($hdr, 0, $cols) . "\n";

            foreach ($invs->values() as $inv) {
                $no  = str_pad(substr($inv['invoice_number'], 0, $iW - 1), $iW);
                $dt  = str_pad(\Carbon\Carbon::parse($inv['invoice_date'])->format('d/m/y'), $dW);
                $t   = str_pad(number_format((float) $inv['net_total'], 2), $tW, ' ', STR_PAD_LEFT);
                $b   = str_pad(number_format((float) $inv['balance_due'], 2), $bW, ' ', STR_PAD_LEFT);
                $out .= substr($no . $dt . $t . $b, 0, $cols) . "\n";
            }

            $sline = str_pad('Subtotal', $iW + $dW)
                   . str_pad(number_format((float) $invs->sum('net_total'), 2), $tW, ' ', STR_PAD_LEFT)
                   . str_pad(number_format((float) $invs->sum('balance_due'), 2), $bW, ' ', STR_PAD_LEFT);
            $out .= str_repeat('.', $cols) . "\n";
            $out .= $boldOn . substr($sline, 0, $cols) . $boldOff . "\n\n";
        }

        // ── Grand totals ──
        $grandT = (float) $detailed->sum('net_total');
        $grandP = (float) $detailed->sum('paid_amount');
        $grandB = (float) $detailed->sum('balance_due');
        $half   = (int) ($cols / 2);

        $out .= $eq . "\n";
        $out .= $center . $boldOn . 'GRAND TOTAL' . $boldOff . "\n" . $left;
        $out .= str_pad('Invoices:', $half) . str_pad($detailed->count() . ' (' . $grouped->count() . ' cust)', $half, ' ', STR_PAD_LEFT) . "\n";
        $out .= str_pad('Paid:', $half) . str_pad(number_format($grandP, 2), $half, ' ', STR_PAD_LEFT) . "\n";
        $out .= $boldOn . str_pad('TOTAL:', $half) . str_pad(number_format($grandT, 2), $half, ' ', STR_PAD_LEFT) . $boldOff . "\n";
        $out .= $boldOn . str_pad('OUTSTANDING:', $half) . str_pad(number_format($grandB, 2), $half, ' ', STR_PAD_LEFT) . $boldOff . "\n";

        // ── Customer Summary ──
        $nmW = max(4, $cols - 22);
        $out .= $dash . "\n";
        $out .= $center . $boldOn . 'CUSTOMER SUMMARY' . $boldOff . "\n" . $left;
        $out .= substr(str_pad('Customer', $nmW) . str_pad('Sales', 11, ' ', STR_PAD_LEFT) . str_pad('Bal', 11, ' ', STR_PAD_LEFT), 0, $cols) . "\n";

        foreach ($summary->values() as $row) {
            $nm  = str_pad(substr($row['customer_name'], 0, $nmW - 1), $nmW);
            $sal = str_pad(number_format((float) $row['total_sales'], 2), 11, ' ', STR_PAD_LEFT);
            $bl  = str_pad(number_format((float) $row['total_balance'], 2), 11, ' ', STR_PAD_LEFT);
            $out .= substr($nm . $sal . $bl, 0, $cols) . "\n";
        }

        $stot = (float) $summary->sum('total_sales');
        $sbal = (float) $summary->sum('total_balance');
        $nm   = str_pad('TOTAL', $nmW);
        $sal  = str_pad(number_format($stot, 2), 11, ' ', STR_PAD_LEFT);
        $bl   = str_pad(number_format($sbal, 2), 11, ' ', STR_PAD_LEFT);
        $out .= $eq . "\n";
        $out .= $boldOn . substr($nm . $sal . $bl, 0, $cols) . $boldOff . "\n";

        $out .= $dash . "\n";
        $out .= $center . "* END OF REPORT *\n";
        $out .= 'Powered by Techgeeks' . "\n";
        $out .= $left . $feed . $cut;

        return $out;
    }

    private function _queryReportData(array $filters): array
    {
        $applyFilters = function ($query) use ($filters) {
            if (!empty($filters['date_from']))      $query->whereDate('si.invoice_date', '>=', $filters['date_from']);
            if (!empty($filters['date_to']))        $query->whereDate('si.invoice_date', '<=', $filters['date_to']);
            if (!empty($filters['customer_id']))    $query->where('si.customer_id', $filters['customer_id']);
            if (!empty($filters['payment_status'])) $query->where('si.payment_status', $filters['payment_status']);
            return $query;
        };

        $detailed = $applyFilters(
            DB::table('sales_invoices as si')
                ->join('associates as a', 'si.customer_id', '=', 'a.id')
                ->whereIn('si.status', ['confirmed', 'posted'])
                ->whereNull('si.deleted_at')
                ->select('a.id as customer_id', 'a.name as customer_name', 'si.invoice_number', 'si.invoice_date', 'si.net_total', 'si.paid_amount', 'si.balance_due', 'si.payment_status')
                ->orderBy('a.name')
                ->orderBy('si.invoice_date', 'desc')
        )->get()->map(fn($r) => (array) $r);

        $summary = $applyFilters(
            DB::table('sales_invoices as si')
                ->join('associates as a', 'si.customer_id', '=', 'a.id')
                ->whereIn('si.status', ['confirmed', 'posted'])
                ->whereNull('si.deleted_at')
                ->select('a.id as customer_id', 'a.name as customer_name',
                    DB::raw('SUM(si.net_total) as total_sales'),
                    DB::raw('SUM(si.paid_amount) as total_paid'),
                    DB::raw('SUM(si.balance_due) as total_balance'))
                ->groupBy('a.id', 'a.name')
                ->orderByDesc('total_sales')
        )->get()->map(fn($r) => (array) $r);

        return [collect($detailed), collect($summary)];
    }

    private static function _trunc(string $s, int $cols): string
    {
        return mb_strlen($s) > $cols ? mb_substr($s, 0, $cols - 1) . '~' : $s;
    }

    private function _authBridge(Request $request): bool
    {
        $token = config('services.print_bridge.token');
        if (!$token) {
            Log::warning('[PRINT-BRIDGE] auth failed: PRINT_BRIDGE_TOKEN not set in .env');
            return false;
        }
        $sent = $request->header('X-Bridge-Token') ?? $request->bearerToken();
        $ok   = hash_equals($token, (string) $sent);
        if (!$ok) {
            Log::warning('[PRINT-BRIDGE] auth failed: token mismatch', [
                'ip'         => $request->ip(),
                'sent_len'   => strlen((string) $sent),
                'has_header' => $request->hasHeader('X-Bridge-Token'),
            ]);
        }
        return $ok;
    }
}
