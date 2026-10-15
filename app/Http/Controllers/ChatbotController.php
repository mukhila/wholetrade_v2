<?php

namespace App\Http\Controllers;

use App\Models\Associate;
use App\Models\Product;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ChatbotController extends Controller
{
    // ── pages the user can navigate to ──────────────────────────────────────────
    private array $pages = [
        ['keys' => ['sales invoice', 'invoices', 'invoice list', 'sales invoices'], 'label' => 'Sales Invoices',      'url' => '/sales-invoices'],
        ['keys' => ['create invoice', 'new invoice'],                               'label' => 'Create Invoice',       'url' => '/sales-invoices/create'],
        ['keys' => ['report', 'reports'],                                           'label' => 'Reports',              'url' => '/reports'],
        ['keys' => ['dashboard', 'home'],                                           'label' => 'Dashboard',            'url' => '/dashboard'],
        ['keys' => ['product', 'products', 'item list'],                            'label' => 'Products',             'url' => '/products'],
        ['keys' => ['stock list', 'stock'],                                         'label' => 'Stock List',           'url' => '/stock-list'],
        ['keys' => ['customer', 'supplier', 'associate', 'associates', 'clients'],  'label' => 'Customers / Suppliers','url' => '/associates'],
        ['keys' => ['quotation', 'quotations', 'quote'],                            'label' => 'Quotations',           'url' => '/quotations'],
        ['keys' => ['sales receipt', 'receipt', 'receipts'],                        'label' => 'Sales Receipts',       'url' => '/sales-receipts'],
        ['keys' => ['purchase order', 'po ', 'purchase orders'],                    'label' => 'Purchase Orders',      'url' => '/purchase-orders'],
        ['keys' => ['supplier invoice', 'supplier invoices', 'purchase invoice'],   'label' => 'Supplier Invoices',    'url' => '/supplier-invoices'],
        ['keys' => ['proforma', 'pro forma'],                                       'label' => 'Proforma Invoices',    'url' => '/proforma-invoices'],
        ['keys' => ['price setting', 'price settings', 'pricing'],                  'label' => 'Price Settings',       'url' => '/price-settings'],
    ];

    public function message(Request $request)
    {
        $request->validate(['message' => 'required|string|max:1000']);
        $msg   = trim($request->input('message'));
        $lower = strtolower($msg);

        $reply = $this->dispatch($lower, $msg);

        return response()->json(['reply' => $reply]);
    }

    private function dispatch(string $lower, string $raw): string
    {
        // help
        if ($this->has($lower, ['help', 'what can you do', 'commands', 'what can i ask'])) {
            return $this->replyHelp();
        }

        // today's invoice count
        if ($this->has($lower, ['today invoice', "today's invoice", 'invoice today', 'invoice count', 'how many invoice'])) {
            return $this->replyTodayInvoices();
        }

        // specific invoice lookup  e.g. "invoice INV001" or "show INV001"
        if (preg_match('/\b([A-Z]{2,}[-]?\d{3,})\b/i', $raw, $m)) {
            return $this->replyInvoiceLookup($m[1]);
        }

        // stats / dashboard summary
        if ($this->has($lower, ['today stat', "today's stat", 'this month', 'monthly stat', 'dashboard stat', 'summary', 'overview', 'revenue'])) {
            return $this->replyStats();
        }

        // low stock
        if ($this->has($lower, ['low stock', 'out of stock', 'reorder', 'stock alert'])) {
            return $this->replyLowStock();
        }

        // product search  e.g. "search product rice" / "find product ABC" / "product rice"
        if (preg_match('/(?:search|find|look up|lookup)\s+(?:product|item|sku)?\s*[:\-]?\s*(.+)/i', $raw, $m)) {
            return $this->replyProductSearch(trim($m[1]));
        }
        // bare "product <name>" e.g. "product basmati"
        if (preg_match('/^products?\s+(.+)$/i', $raw, $m)) {
            return $this->replyProductSearch(trim($m[1]));
        }
        if ($this->has($lower, ['product search', 'search product', 'find product'])) {
            return 'Please type: **product [name or SKU]** — for example: `product rice`';
        }
        // list / go to products page
        if ($this->has($lower, ['list product', 'all product', 'product list', 'show product', 'go to product', 'open product', 'products'])) {
            $url = url('/products');
            return "Here is the **Products** page: [Products]({$url})";
        }

        // customer / supplier search
        if (preg_match('/(?:search|find|look up)\s+(?:customer|supplier|associate)\s+(.+)/i', $raw, $m)) {
            return $this->replyAssociateSearch(trim($m[1]));
        }
        // bare "customer <name>" or "supplier <name>"
        if (preg_match('/^(?:customer|supplier|associate)\s+(.+)$/i', $raw, $m)) {
            return $this->replyAssociateSearch(trim($m[1]));
        }

        // recent invoices
        if ($this->has($lower, ['recent invoice', 'latest invoice', 'last invoice', 'show invoice'])) {
            return $this->replyRecentInvoices();
        }

        // navigation — go to / open / show page
        $navPage = $this->matchPage($lower);
        if ($navPage) {
            $url = url($navPage['url']);
            return "Here is the **{$navPage['label']}** page: [{$navPage['label']}]({$url})";
        }

        // fallback
        return $this->replyFallback();
    }

    // ── intent helpers ───────────────────────────────────────────────────────────

    private function has(string $lower, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (str_contains($lower, $kw)) return true;
        }
        return false;
    }

    private function matchPage(string $lower): ?array
    {
        // strip common navigation verbs so "go to reports" → "reports"
        $lower = preg_replace('/^(go to|open|show|navigate to|take me to|visit)\s+/i', '', $lower);

        foreach ($this->pages as $page) {
            foreach ($page['keys'] as $kw) {
                if (str_contains($lower, $kw)) return $page;
            }
        }
        return null;
    }

    // ── reply builders ───────────────────────────────────────────────────────────

    private function replyHelp(): string
    {
        return implode("\n", [
            "Here's what I can help you with:\n",
            "**Navigation**",
            "- `go to sales invoices` — open the invoices page",
            "- `go to reports` — open reports",
            "- `go to products` — open product list",
            "- `go to stock list` — open stock levels",
            "- `go to customers` — open customers / suppliers",
            "- _(and any other page name)_\n",
            "**Invoice queries**",
            "- `today invoice count` — how many invoices created today",
            "- `recent invoices` — last 5 invoices",
            "- `invoice INV001` — look up a specific invoice number\n",
            "**Business stats**",
            "- `today stats` — today's revenue & invoice summary",
            "- `this month` — month-to-date summary\n",
            "**Products & stock**",
            "- `product rice` — find product by name or SKU (shows price + stock)",
            "- `products` — go to the products page",
            "- `low stock` — products running low\n",
            "**Customers**",
            "- `customer ABC Trading` — full detail: contact, balance, recent invoices + report link",
            "- `find customer ABC` — search customers/suppliers",
        ]);
    }

    private function replyTodayInvoices(): string
    {
        $today = Carbon::today();

        $total     = SalesInvoice::whereDate('invoice_date', $today)->count();
        $confirmed = SalesInvoice::whereDate('invoice_date', $today)->where('status', 'confirmed')->count();
        $draft     = SalesInvoice::whereDate('invoice_date', $today)->where('status', 'draft')->count();
        $revenue   = (float) SalesInvoice::whereDate('invoice_date', $today)->where('status', 'confirmed')->sum('net_total');

        $url = url('/sales-invoices');
        return implode("\n", [
            "**Today's Invoices** (" . $today->format('d M Y') . ")\n",
            "- Total: **{$total}** invoice(s)",
            "- Confirmed: **{$confirmed}**",
            "- Draft: **{$draft}**",
            "- Confirmed revenue: **" . number_format($revenue, 2) . "**\n",
            "[View all invoices]({$url})",
        ]);
    }

    private function replyStats(): string
    {
        $today  = Carbon::today();
        $month  = Carbon::now()->startOfMonth();

        $todayCount   = SalesInvoice::whereDate('invoice_date', $today)->count();
        $todayRev     = (float) SalesInvoice::whereDate('invoice_date', $today)->where('status', 'confirmed')->sum('net_total');
        $monthCount   = SalesInvoice::where('invoice_date', '>=', $month)->count();
        $monthRev     = (float) SalesInvoice::where('invoice_date', '>=', $month)->where('status', 'confirmed')->sum('net_total');
        $outstanding  = (float) SalesInvoice::sum('balance_due');
        $activeProds  = Product::where('status', true)->count();
        $lowStock     = Product::where('status', true)->where('stock_qty_pcs', '<=', 0)->count();

        return implode("\n", [
            "**Business Summary**\n",
            "**Today** (" . $today->format('d M Y') . ")",
            "- Invoices: **{$todayCount}**",
            "- Revenue: **" . number_format($todayRev, 2) . "**\n",
            "**This Month** (" . Carbon::now()->format('M Y') . ")",
            "- Invoices: **{$monthCount}**",
            "- Revenue: **" . number_format($monthRev, 2) . "**\n",
            "**Overall**",
            "- Outstanding balance: **" . number_format($outstanding, 2) . "**",
            "- Active products: **{$activeProds}**",
            "- Out-of-stock products: **{$lowStock}**",
        ]);
    }

    private function replyInvoiceLookup(string $number): string
    {
        $invoices = SalesInvoice::with('customer')
            ->where('invoice_number', 'like', "%{$number}%")
            ->orderByDesc('invoice_date')
            ->limit(5)
            ->get();

        if ($invoices->isEmpty()) {
            return "No invoice found matching **{$number}**.";
        }

        $lines = ["**Invoice search: {$number}**\n"];
        foreach ($invoices as $inv) {
            $url  = url("/sales-invoices/{$inv->id}");
            $due  = $inv->balance_due > 0 ? ' · Balance due: **' . number_format((float)$inv->balance_due, 2) . '**' : ' · Paid';
            $lines[] = "- [{$inv->invoice_number}]({$url}) · {$inv->customer?->name} · {$inv->invoice_date} · **" . number_format((float)$inv->net_total, 2) . "**{$due}";
        }
        return implode("\n", $lines);
    }

    private function replyRecentInvoices(): string
    {
        $invoices = SalesInvoice::with('customer')
            ->orderByDesc('invoice_date')
            ->limit(5)
            ->get();

        if ($invoices->isEmpty()) {
            return "No invoices found.";
        }

        $lines = ["**Recent Invoices**\n"];
        foreach ($invoices as $inv) {
            $url  = url("/sales-invoices/{$inv->id}");
            $lines[] = "- [{$inv->invoice_number}]({$url}) · {$inv->customer?->name} · {$inv->invoice_date} · **" . number_format((float)$inv->net_total, 2) . "**";
        }
        $lines[] = "\n[View all invoices](" . url('/sales-invoices') . ")";
        return implode("\n", $lines);
    }

    private function replyLowStock(): string
    {
        $products = Product::where('status', true)
            ->where('stock_qty_pcs', '<=', 0)
            ->orderBy('stock_qty_pcs')
            ->limit(15)
            ->get();

        if ($products->isEmpty()) {
            return "No out-of-stock products found.";
        }

        $lines = ["**Out-of-stock / Low Stock Products** ({$products->count()})\n"];
        foreach ($products as $p) {
            $url    = url("/products/{$p->id}");
            $stock  = (int) $p->stock_qty_pcs;
            $lines[] = "- [{$p->sku} — {$p->name}]({$url}) · Stock: **{$stock} pcs**";
        }
        $lines[] = "\n[View stock list](" . url('/stock-list') . ")";
        return implode("\n", $lines);
    }

    private function replyProductSearch(string $term): string
    {
        $products = Product::with(['uom', 'price', 'packSize'])
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('sku',  'like', "%{$term}%")
                  ->orWhereHas('aliases', fn($a) => $a->withoutGlobalScope('subscriber')->where('alias_name', 'like', "%{$term}%"));
            })
            ->limit(8)
            ->get();

        if ($products->isEmpty()) {
            return "No products found for \"**{$term}**\".";
        }

        $lines = ["**Products matching \"{$term}\"** ({$products->count()})\n"];
        foreach ($products as $p) {
            $url        = url("/products/{$p->id}");
            $uom        = $p->uom?->name ?? 'pcs';
            $stockPcs   = (float) $p->stock_qty_pcs;
            $stockBox   = (float) $p->stock_qty_box;
            $packQty    = (int)   ($p->packSize?->qty ?? 0);
            $sellPrice  = (float) ($p->price?->sales_price_item ?? 0);
            $sellBox    = (float) ($p->price?->sales_price_box  ?? 0);
            $costPrice  = (float) ($p->price?->cost_price_item  ?? 0);

            $stockStr = "**{$stockPcs} {$uom}**";
            if ($packQty > 0 && $stockBox > 0) {
                $stockStr .= " / **{$stockBox} box**";
            }

            $priceStr = $sellPrice > 0 ? "Sell: **" . number_format($sellPrice, 2) . "/{$uom}**" : '';
            if ($sellBox > 0 && $packQty > 0) {
                $priceStr .= $priceStr ? " / **" . number_format($sellBox, 2) . "/box**" : "Sell: **" . number_format($sellBox, 2) . "/box**";
            }
            if ($costPrice > 0) {
                $priceStr .= $priceStr ? " · Cost: " . number_format($costPrice, 2) : "Cost: " . number_format($costPrice, 2);
            }

            $status = $p->status ? '' : ' · ~~Inactive~~';
            $lines[] = "- [{$p->sku} — {$p->name}]({$url})";
            $lines[] = "  Stock: {$stockStr}" . ($priceStr ? " · {$priceStr}" : '') . $status;
        }
        return implode("\n", $lines);
    }

    private function replyAssociateSearch(string $term): string
    {
        $assocs = Associate::where('name', 'like', "%{$term}%")->limit(5)->get();

        if ($assocs->isEmpty()) {
            return "No customer/supplier found for \"**{$term}**\".";
        }

        // single match — show full detail card
        if ($assocs->count() === 1) {
            return $this->replyAssociateDetail($assocs->first());
        }

        // multiple matches — list with links
        $lines = ["**Found {$assocs->count()} results for \"{$term}\"** — type the exact name for full details.\n"];
        foreach ($assocs as $a) {
            $url = url("/associates/{$a->id}");
            $bal = (float) $a->getTotalOutstandingBalance();
            $lines[] = "- [{$a->name}]({$url}) · {$a->type}" . ($bal > 0 ? " · Outstanding: **" . number_format($bal, 2) . "**" : '');
        }
        return implode("\n", $lines);
    }

    private function replyAssociateDetail(Associate $a): string
    {
        $url        = url("/associates/{$a->id}");
        $reportUrl  = url("/reports/customers?customer_id={$a->id}");
        $bal        = (float) $a->getTotalOutstandingBalance();

        // recent invoices for this customer
        $recentInvs = SalesInvoice::with([])
            ->where('customer_id', $a->id)
            ->orderByDesc('invoice_date')
            ->limit(3)
            ->get();

        $totalInvs  = SalesInvoice::where('customer_id', $a->id)->count();
        $totalRev   = (float) SalesInvoice::where('customer_id', $a->id)->where('status', 'confirmed')->sum('net_total');

        $lines = [
            "**{$a->name}** ({$a->type})\n",
        ];

        if ($a->code)  $lines[] = "- Code: **{$a->code}**";
        if ($a->phone) $lines[] = "- Phone: **{$a->phone}**";
        if ($a->email) $lines[] = "- Email: {$a->email}";
        if ($a->address) $lines[] = "- Address: {$a->address}";

        $lines[] = "";
        $lines[] = "**Financial Summary**";
        $lines[] = "- Total invoices: **{$totalInvs}**";
        $lines[] = "- Total revenue: **" . number_format($totalRev, 2) . "**";
        $lines[] = "- Outstanding balance: **" . number_format($bal, 2) . "**";

        if ($recentInvs->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "**Recent Invoices**";
            foreach ($recentInvs as $inv) {
                $invUrl = url("/sales-invoices/{$inv->id}");
                $lines[] = "- [{$inv->invoice_number}]({$invUrl}) · {$inv->invoice_date} · **" . number_format((float)$inv->net_total, 2) . "**";
            }
        }

        $lines[] = "";
        $lines[] = "[View Profile]({$url}) · [Customer Report]({$reportUrl})";

        return implode("\n", $lines);
    }

    private function replyFallback(): string
    {
        return implode("\n", [
            "I'm not sure what you mean. Try one of these:\n",
            "- `go to sales invoices`",
            "- `go to reports`",
            "- `today invoice count`",
            "- `today stats`",
            "- `low stock`",
            "- `product [name or SKU]`",
            "- `invoice [number]`",
            "- `help` — see all commands",
        ]);
    }
}
