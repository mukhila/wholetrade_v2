<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SuperAdmin\SubscriberController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;


use App\Http\Controllers\QzController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffRoleController;
Route::get('/qz/certificate', [QzController::class, 'certificate']);
Route::post('/qz/sign',       [QzController::class, 'sign']);

// Print-bridge polling API — no session/CSRF (token auth instead), excluded in bootstrap/app.php
Route::get('print-bridge/pending',   [\App\Http\Controllers\PrintBridgeController::class, 'pending'])->name('print-bridge.pending');
Route::post('print-bridge/ack/{id}', [\App\Http\Controllers\PrintBridgeController::class, 'ack'])->name('print-bridge.ack');
Route::post('print-bridge/register', [\App\Http\Controllers\PrintBridgeController::class, 'register'])
    ->middleware('throttle:10,1')->name('print-bridge.register');

// BUG-001 FIX: All maintenance/diagnostic routes require authentication + admin role.
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/clear-cache', function () {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('optimize:clear');
        return "Cache cleared!";
    });

    Route::get('/db-check', function () {
        try {
            DB::connection()->getPdo();
            return '✅ Database connection is working!';
        } catch (\Exception $e) {
            return '❌ DB Error: ' . $e->getMessage();
        }
    });

    Route::get('/run-migrations', function () {
        try {
            Artisan::call('migrate', ['--force' => true]);
            return nl2br(Artisan::output());
        } catch (\Exception $e) {
            return '❌ Migration Error: ' . $e->getMessage();
        }
    });

    Route::get('/run-seeders', function () {
        try {
            Artisan::call('db:seed', ['--force' => true]);
            return nl2br(Artisan::output());
        } catch (\Exception $e) {
            return '❌ Seeder Error: ' . $e->getMessage();
        }
    });

    Route::get('/run-outstanding-seeder', function () {
        try {
            Artisan::call('db:seed', ['--class' => 'OutstandingInvoiceSeeder', '--force' => true]);
            return nl2br(Artisan::output());
        } catch (\Exception $e) {
            return '❌ Seeder Error: ' . $e->getMessage();
        }
    });

    Route::get('/clean-test-invoices', function () {
        if (app()->environment('production')) {
            return '❌ Cannot run in production!';
        }
        try {
            Artisan::call('db:seed', ['--class' => 'CleanTestInvoiceDataSeeder', '--force' => true]);
            return '<pre>' . Artisan::output() . '</pre>';
        } catch (\Exception $e) {
            return '❌ Error: ' . $e->getMessage();
        }
    });

    Route::get('/create-storage-link', function () {
        try {
            if (file_exists(public_path('storage'))) {
                return '✅ Storage link already exists';
            }
            Artisan::call('storage:link');
            return '✅ Storage link created successfully';
        } catch (\Exception $e) {
            return '❌ Error: ' . $e->getMessage();
        }
    });
});

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1'); // BUG-020 FIX: rate-limit brute force
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Subscription Expired Route
Route::middleware('auth')->get('/subscription/expired', function () {
    return view('subscription.expired');
})->name('subscription.expired');

// Super Admin Routes
Route::middleware(['auth', 'super_admin'])->prefix('super-admin')->name('super_admin.')->group(function () {
    Route::get('/', [SubscriberController::class, 'dashboard'])->name('dashboard');
    Route::resource('subscribers', SubscriberController::class);
    Route::post('subscribers/{subscriber}/extend', [SubscriberController::class, 'extend'])->name('subscribers.extend');
    Route::post('subscribers/{subscriber}/suspend', [SubscriberController::class, 'suspend'])->name('subscribers.suspend');
    Route::post('subscribers/{subscriber}/activate', [SubscriberController::class, 'activate'])->name('subscribers.activate');
    Route::get('subscribers/{subscriber}/users', [SubscriberController::class, 'users'])->name('subscribers.users');
});

// All authenticated routes with subscription check
Route::middleware(['auth', 'subscription'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Profile (Accessible to all authenticated users)
    Route::get('profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Cart Routes
    Route::get('cart', [\App\Http\Controllers\CartController::class, 'index'])->name('cart.index');
    Route::get('add-to-cart/{id}', [\App\Http\Controllers\CartController::class, 'addToCart'])->name('cart.add');
    Route::patch('update-cart', [\App\Http\Controllers\CartController::class, 'update'])->name('cart.update');
    Route::delete('remove-from-cart', [\App\Http\Controllers\CartController::class, 'remove'])->name('cart.remove');
    Route::get('clear-cart', [\App\Http\Controllers\CartController::class, 'clear'])->name('cart.clear');

    // Staff Management (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::resource('staff', StaffController::class);
        Route::get('staff/{staff}/permissions', [StaffController::class, 'permissions'])->name('staff.permissions');
        Route::post('staff/{staff}/permissions', [StaffController::class, 'updatePermissions'])->name('staff.permissions.update');
        Route::resource('staff-roles', StaffRoleController::class);
        Route::get('staff-roles/{staffRole}/permissions',  [StaffRoleController::class, 'permissions'])->name('staff-roles.permissions');
        Route::post('staff-roles/{staffRole}/permissions', [StaffRoleController::class, 'updatePermissions'])->name('staff-roles.permissions.update');
    });

    // Generic API Routes
    Route::get('api/associates/search', [\App\Http\Controllers\AssociateController::class, 'searchApi']);

    // Quotations (Salesperson Access)
    Route::get('quotations', [\App\Http\Controllers\QuotationController::class, 'index'])->name('quotations.index');
    Route::get('quotations/create', [\App\Http\Controllers\QuotationController::class, 'create'])->name('quotations.create');
    Route::get('api/quotations/products', [\App\Http\Controllers\QuotationController::class, 'getProducts']);
    Route::post('api/quotations/submit', [\App\Http\Controllers\QuotationController::class, 'store']);
    Route::get('quotations/{id}', [\App\Http\Controllers\QuotationController::class, 'show'])->name('quotations.show');
    Route::put('api/quotations/{id}/accept-revision', [\App\Http\Controllers\QuotationController::class, 'acceptRevision']);
    Route::put('api/quotations/{id}/reject-revision', [\App\Http\Controllers\QuotationController::class, 'rejectRevision']);

    // Sales Credit Notes
    Route::resource('sales-credit-notes', \App\Http\Controllers\SalesCreditNoteController::class)->names('credit-notes')->middleware('staff_gate');
    Route::get('api/sales-credit-notes/customer-last-credit/{customerId}', [\App\Http\Controllers\SalesCreditNoteController::class, 'getCustomerLastCredit']);
    Route::get('api/sales-credit-notes/product-stock/{productId}', [\App\Http\Controllers\SalesCreditNoteController::class, 'getProductStock']);
    Route::get('api/sales-credit-notes/customer-invoices/{customerId}', [\App\Http\Controllers\SalesCreditNoteController::class, 'getCustomerInvoices']);
    Route::get('api/sales-credit-notes/invoice-items/{invoiceId}', [\App\Http\Controllers\SalesCreditNoteController::class, 'getInvoiceItems']);
    // Sales Debit Notes
    Route::resource('sales-debit-notes', \App\Http\Controllers\SalesDebitNoteController::class)->names('debit-notes')->middleware('staff_gate');
    // Delivery Orders
    Route::resource('delivery-orders', \App\Http\Controllers\DeliveryOrderController::class)->names('delivery-orders')->middleware('staff_gate');
    Route::get('api/delivery-orders/customer-orders/{id}', [\App\Http\Controllers\DeliveryOrderController::class, 'getCustomerOrders']);

    Route::get('checkout', [\App\Http\Controllers\QuotationController::class, 'checkout'])->name('checkout.index');
    Route::post('quotations/store', [\App\Http\Controllers\QuotationController::class, 'store'])->name('quotations.store');

    Route::middleware(['role:admin,staff', 'staff_gate'])->group(function () {
        // Products (Admin Actions) - Defined FIRST to ensure 'create' is matched before '{product}'
        Route::get('products/export', [\App\Http\Controllers\ProductController::class, 'export'])->name('products.export');
        Route::get('products/import/template', [\App\Http\Controllers\ProductController::class, 'importTemplate'])->name('products.import.template');
        Route::post('products/import', [\App\Http\Controllers\ProductController::class, 'import'])->name('products.import');
        Route::resource('products', \App\Http\Controllers\ProductController::class)->except(['index', 'show']);
        Route::patch('products/{product}/toggle-status', [\App\Http\Controllers\ProductController::class, 'toggleStatus'])->name('products.toggle-status');
        Route::delete('products/{product}/images/{image}', [\App\Http\Controllers\ProductController::class, 'destroyImage'])->name('products.images.destroy');
        Route::patch('products/{product}/images/{image}/primary', [\App\Http\Controllers\ProductController::class, 'setPrimaryImage'])->name('products.images.primary');

        // Company Settings
        Route::get('/company-settings', [\App\Http\Controllers\CompanySettingController::class, 'index'])->name('company-settings.index');
        Route::get('/company-settings/create', [\App\Http\Controllers\CompanySettingController::class, 'create'])->name('company-settings.create');
        Route::post('/company-settings', [\App\Http\Controllers\CompanySettingController::class, 'store'])->name('company-settings.store');
        Route::get('/company-settings/edit', [\App\Http\Controllers\CompanySettingController::class, 'edit'])->name('company-settings.edit');
        Route::put('/company-settings', [\App\Http\Controllers\CompanySettingController::class, 'update'])->name('company-settings.update');

        // Master Data (Dynamic Types)
        Route::get('/master-data/{type}', [\App\Http\Controllers\MasterDataController::class, 'index'])->name('master-data.index');
        Route::get('/master-data/{type}/create', [\App\Http\Controllers\MasterDataController::class, 'create'])->name('master-data.create');
        Route::post('/master-data/{type}', [\App\Http\Controllers\MasterDataController::class, 'store'])->name('master-data.store');
        Route::get('/master-data/{type}/{id}/edit', [\App\Http\Controllers\MasterDataController::class, 'edit'])->name('master-data.edit');
        Route::put('/master-data/{type}/{id}', [\App\Http\Controllers\MasterDataController::class, 'update'])->name('master-data.update');
        Route::delete('/master-data/{type}/{id}', [\App\Http\Controllers\MasterDataController::class, 'destroy'])->name('master-data.destroy');

        // Associate Groups
        Route::resource('associate-groups', \App\Http\Controllers\AssociateGroupController::class);
        
        // Associates (Customers / Suppliers)
        Route::get('associates/export', [\App\Http\Controllers\AssociateController::class, 'export'])->name('associates.export');
        Route::get('associates/import/template', [\App\Http\Controllers\AssociateController::class, 'importTemplate'])->name('associates.import.template');
        Route::post('associates/import', [\App\Http\Controllers\AssociateController::class, 'import'])->name('associates.import');
        Route::patch('associates/{associate}/toggle-status', [\App\Http\Controllers\AssociateController::class, 'toggleStatus'])->name('associates.toggle-status');
        Route::resource('associates', \App\Http\Controllers\AssociateController::class);

        // Product Labels
        Route::resource('product-labels', \App\Http\Controllers\ProductLabelController::class);

        // Product Aliases (standalone menu)
        Route::get('api/product-aliases/search-products', [\App\Http\Controllers\ProductAliasController::class, 'searchProducts'])->name('product-aliases.search-products');
        Route::resource('product-aliases', \App\Http\Controllers\ProductAliasController::class)->except(['show']);

        // Product Aliases (nested — used by product edit page aliases tab)
        Route::post('products/{product}/aliases', [\App\Http\Controllers\ProductController::class, 'storeAlias'])->name('products.aliases.store');
        Route::put('products/{product}/aliases/{alias}', [\App\Http\Controllers\ProductController::class, 'updateAlias'])->name('products.aliases.update');
        Route::delete('products/{product}/aliases/{alias}', [\App\Http\Controllers\ProductController::class, 'destroyAlias'])->name('products.aliases.destroy');

        // Price Settings
        Route::get('price-settings', [\App\Http\Controllers\PriceSettingController::class, 'index'])->name('price-settings.index');
        Route::get('api/price-settings/master-data', [\App\Http\Controllers\PriceSettingController::class, 'getMasterData']);
        Route::get('api/price-settings/prices', [\App\Http\Controllers\PriceSettingController::class, 'getPrices']);
        Route::post('api/price-settings/save', [\App\Http\Controllers\PriceSettingController::class, 'savePrices']);

        // Price Updates (Selling Prices)
        Route::get('price-updates', [\App\Http\Controllers\PriceUpdateController::class, 'index'])->name('price-updates.index');
        Route::get('api/price-updates/products', [\App\Http\Controllers\PriceUpdateController::class, 'getProducts']);
        Route::post('api/price-updates/save', [\App\Http\Controllers\PriceUpdateController::class, 'updatePrices']);

        // Cost Price Updates (Bulk)
        Route::get('cost-price-updates', [\App\Http\Controllers\CostPriceUpdateController::class, 'index'])->name('cost-price-updates.index');
        Route::put('cost-price-updates', [\App\Http\Controllers\CostPriceUpdateController::class, 'update'])->name('cost-price-updates.update');

        // Admin Quotations
        Route::get('admin/quotations', [\App\Http\Controllers\AdminQuotationController::class, 'index'])->name('admin.quotations.index');
        Route::get('admin/quotations/{id}', [\App\Http\Controllers\AdminQuotationController::class, 'show'])->name('admin.quotations.show');
        Route::put('api/admin/quotations/{id}/approve', [\App\Http\Controllers\AdminQuotationController::class, 'approve']);
        Route::put('api/admin/quotations/{id}/reject', [\App\Http\Controllers\AdminQuotationController::class, 'reject']);
        Route::put('api/admin/quotations/{id}/revise', [\App\Http\Controllers\AdminQuotationController::class, 'revise']);

        // Legacy Invoices (quotation-based)
        Route::get('admin/invoices', [\App\Http\Controllers\AdminInvoiceController::class, 'index'])->name('admin.invoices.index');
        Route::put('api/admin/invoices/{id}/status', [\App\Http\Controllers\AdminInvoiceController::class, 'updateStatus']);
        Route::get('api/admin/invoices/{id}/export', [\App\Http\Controllers\AdminInvoiceController::class, 'export'])->name('admin.invoices.export');

        // Supplier Invoices (full CRUD + confirm + pay)
        Route::get('supplier-invoices', [\App\Http\Controllers\SupplierInvoiceController::class, 'index'])->name('supplier-invoices.index');
        Route::get('supplier-invoices/create', [\App\Http\Controllers\SupplierInvoiceController::class, 'create'])->name('supplier-invoices.create');
        Route::post('supplier-invoices', [\App\Http\Controllers\SupplierInvoiceController::class, 'store'])->name('supplier-invoices.store');
        Route::get('supplier-invoices/{id}', [\App\Http\Controllers\SupplierInvoiceController::class, 'show'])->name('supplier-invoices.show');
        Route::get('supplier-invoices/{id}/edit', [\App\Http\Controllers\SupplierInvoiceController::class, 'edit'])->name('supplier-invoices.edit');
        Route::put('supplier-invoices/{id}', [\App\Http\Controllers\SupplierInvoiceController::class, 'update'])->name('supplier-invoices.update');
        Route::post('supplier-invoices/{id}/confirm', [\App\Http\Controllers\SupplierInvoiceController::class, 'confirm'])->name('supplier-invoices.confirm');
        Route::get('supplier-invoices/{id}/pay', [\App\Http\Controllers\SupplierInvoiceController::class, 'showPaymentForm'])->name('supplier-invoices.pay');
        Route::post('supplier-invoices/{id}/pay', [\App\Http\Controllers\SupplierInvoiceController::class, 'storePayment'])->name('supplier-invoices.pay.store');
        Route::get('supplier-invoices/{id}/print', [\App\Http\Controllers\SupplierInvoiceController::class, 'printInvoice'])->name('supplier-invoices.print');
        Route::delete('supplier-invoices/{id}', [\App\Http\Controllers\SupplierInvoiceController::class, 'destroy'])->name('supplier-invoices.destroy');

        // Reports
        Route::get('reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
        Route::get('api/reports/filter-options', [\App\Http\Controllers\ReportController::class, 'getFilterOptions']);
        Route::get('api/reports/products', [\App\Http\Controllers\Reports\ProductReportController::class, 'getProductReport']);
        Route::get('api/reports/sales', [\App\Http\Controllers\Reports\SalesReportController::class, 'getSalesReport']);
        Route::get('api/reports/sales/summary', [\App\Http\Controllers\Reports\SalesReportController::class, 'getSalesSummary']);
        Route::get('api/reports/sales/summary/date-wise', [\App\Http\Controllers\Reports\SalesReportController::class, 'getSalesSummaryDateWise']);
        Route::get('api/reports/profit-loss', [\App\Http\Controllers\Reports\SalesReportController::class, 'getProfitLossReport']);
        Route::get('api/reports/profit-loss/day-summary', [\App\Http\Controllers\Reports\SalesReportController::class, 'getProfitLossDaySummary']);
        Route::get('api/reports/stock', [\App\Http\Controllers\Reports\StockReportController::class, 'getStockReport']);

        Route::get('reports/products/print', [\App\Http\Controllers\Reports\ProductReportController::class, 'printProductReport'])->name('reports.products.print');
        Route::get('reports/sales/print', [\App\Http\Controllers\Reports\SalesReportController::class, 'printSalesReport'])->name('reports.sales.print');
        Route::get('reports/sales/customer-wise/print', [\App\Http\Controllers\Reports\SalesReportController::class, 'printSalesReportByCustomer'])->name('reports.sales.customer-wise.print');
        Route::get('reports/sales/summary/print', [\App\Http\Controllers\Reports\SalesReportController::class, 'printSalesSummary'])->name('reports.sales.summary.print');
        Route::get('reports/sales/summary/date-wise/print', [\App\Http\Controllers\Reports\SalesReportController::class, 'printSalesSummaryDateWise'])->name('reports.sales.summary.date-wise.print');
        Route::get('reports/profit-loss/print', [\App\Http\Controllers\Reports\SalesReportController::class, 'printProfitLossReport'])->name('reports.profit-loss.print');
        Route::get('reports/stock/print', [\App\Http\Controllers\Reports\StockReportController::class, 'printStockReport'])->name('reports.stock.print');

        Route::get('api/reports/products/export', [\App\Http\Controllers\Reports\ProductReportController::class, 'exportProductReport']);
        Route::get('api/reports/sales/export', [\App\Http\Controllers\Reports\SalesReportController::class, 'exportSalesReport']);
        Route::get('api/reports/sales/summary/export', [\App\Http\Controllers\Reports\SalesReportController::class, 'exportSalesSummary']);
        Route::get('api/reports/sales/summary/date-wise/export', [\App\Http\Controllers\Reports\SalesReportController::class, 'exportSalesSummaryDateWise']);
        Route::get('api/reports/profit-loss/export', [\App\Http\Controllers\Reports\SalesReportController::class, 'exportProfitLossReport']);
        Route::get('api/reports/stock/export', [\App\Http\Controllers\Reports\StockReportController::class, 'exportStockReport']);

        // Customer-wise Reports
        Route::get('reports/customers', [\App\Http\Controllers\Reports\CustomerReportController::class, 'index'])->name('reports.customers.index');
        Route::get('reports/customers/print-detailed', [\App\Http\Controllers\Reports\CustomerReportController::class, 'printDetailed'])->name('reports.customers.print-detailed');
        Route::get('reports/customers/print-detailed-thermal', [\App\Http\Controllers\Reports\CustomerReportController::class, 'printDetailedThermal'])->name('reports.customers.print-detailed-thermal');
        Route::get('reports/customers/print-summary', [\App\Http\Controllers\Reports\CustomerReportController::class, 'printSummary'])->name('reports.customers.print-summary');
        Route::post('reports/customers/direct-print-thermal', [\App\Http\Controllers\PrintBridgeController::class, 'enqueueReport'])->name('reports.customers.direct-print-thermal');
        Route::get('api/reports/customers/export', [\App\Http\Controllers\Reports\CustomerReportController::class, 'export'])->name('reports.customers.export');
        Route::get('api/reports/customers/detail', [\App\Http\Controllers\Reports\CustomerReportController::class, 'customerDetail']);
        // ── Supplier Payment Tracking Module ──────────────────────────────────
        // Supplier Purchases (simple: supplier + date + invoice no + amount)
        Route::get('supplier-purchases', [\App\Http\Controllers\SupplierPurchaseController::class, 'index'])->name('supplier-purchases.index');
        Route::get('supplier-purchases/create', [\App\Http\Controllers\SupplierPurchaseController::class, 'create'])->name('supplier-purchases.create');
        Route::post('supplier-purchases', [\App\Http\Controllers\SupplierPurchaseController::class, 'store'])->name('supplier-purchases.store');
        Route::get('supplier-purchases/export', [\App\Http\Controllers\SupplierPurchaseController::class, 'export'])->name('supplier-purchases.export');
        Route::get('supplier-purchases/sample-csv', [\App\Http\Controllers\SupplierPurchaseController::class, 'sampleCsv'])->name('supplier-purchases.sample-csv');
        Route::get('supplier-purchases/import', [\App\Http\Controllers\SupplierPurchaseController::class, 'importForm'])->name('supplier-purchases.import');
        Route::post('supplier-purchases/import', [\App\Http\Controllers\SupplierPurchaseController::class, 'import'])->name('supplier-purchases.import.process');
        Route::get('supplier-purchases/{id}/edit', [\App\Http\Controllers\SupplierPurchaseController::class, 'edit'])->name('supplier-purchases.edit');
        Route::put('supplier-purchases/{id}', [\App\Http\Controllers\SupplierPurchaseController::class, 'update'])->name('supplier-purchases.update');
        Route::delete('supplier-purchases/{id}', [\App\Http\Controllers\SupplierPurchaseController::class, 'destroy'])->name('supplier-purchases.destroy');

        // Supplier Payments (multi-invoice: Cash / Online Transfer / GIRO)
        // NOTE: static routes (due-invoices, create) MUST come before {id} wildcard
        Route::get('supplier-payments/due-invoices', [\App\Http\Controllers\SupplierPaymentController::class, 'getDueInvoices'])->name('supplier-payments.due-invoices');
        Route::get('supplier-payments/create', [\App\Http\Controllers\SupplierPaymentController::class, 'create'])->name('supplier-payments.create');
        Route::get('supplier-payments', [\App\Http\Controllers\SupplierPaymentController::class, 'index'])->name('supplier-payments.index');
        Route::post('supplier-payments', [\App\Http\Controllers\SupplierPaymentController::class, 'store'])->name('supplier-payments.store');
        Route::get('supplier-payments/{id}', [\App\Http\Controllers\SupplierPaymentController::class, 'show'])->name('supplier-payments.show');
        Route::post('supplier-payments/{id}/giro-paid', [\App\Http\Controllers\SupplierPaymentController::class, 'markGiroPaid'])->name('supplier-payments.giro-paid');

        // Supplier Reports
        Route::get('supplier-reports/outstanding', [\App\Http\Controllers\SupplierReportController::class, 'outstanding'])->name('supplier-reports.outstanding');
        Route::get('supplier-reports/outstanding/export', [\App\Http\Controllers\SupplierReportController::class, 'exportOutstanding'])->name('supplier-reports.outstanding.export');
        Route::get('supplier-reports/ledger', [\App\Http\Controllers\SupplierReportController::class, 'ledger'])->name('supplier-reports.ledger');
        Route::get('supplier-reports/ledger/export', [\App\Http\Controllers\SupplierReportController::class, 'exportLedger'])->name('supplier-reports.ledger.export');
        Route::get('supplier-reports/giro', [\App\Http\Controllers\SupplierReportController::class, 'giro'])->name('supplier-reports.giro');
        Route::get('supplier-reports/giro/export', [\App\Http\Controllers\SupplierReportController::class, 'exportGiro'])->name('supplier-reports.giro.export');
        Route::get('supplier-reports/gst', [\App\Http\Controllers\GstReportController::class, 'index'])->name('supplier-reports.gst');
        Route::get('supplier-reports/gst/print', [\App\Http\Controllers\GstReportController::class, 'print'])->name('supplier-reports.gst.print');
        Route::get('supplier-reports/gst/export-csv', [\App\Http\Controllers\GstReportController::class, 'exportCsv'])->name('supplier-reports.gst.export-csv');
        Route::get('supplier-reports/gst/export-monthly-csv', [\App\Http\Controllers\GstReportController::class, 'exportMonthlyCsv'])->name('supplier-reports.gst.export-monthly-csv');
        // ──────────────────────────────────────────────────────────────────────

        // Expense: Supplier Credit Notes
        Route::resource('credit-notes', \App\Http\Controllers\SupplierCreditNoteController::class)->names('admin.credit-notes');
        Route::get('api/supplier-details/{id}', [\App\Http\Controllers\SupplierCreditNoteController::class, 'getSupplierDetails']);
        // Expense: Supplier Debit Notes
        Route::resource('debit-notes', \App\Http\Controllers\SupplierDebitNoteController::class)->names('admin.debit-notes');
        // Expense: Purchase Delivery Notes
        Route::resource('purchase-delivery-notes', \App\Http\Controllers\PurchaseDeliveryNoteController::class);
        // Expense: Purchase Orders
        Route::resource('purchase-orders', \App\Http\Controllers\PurchaseOrderController::class);
        // Sales: Proforma Invoices
        Route::resource('proforma-invoices', \App\Http\Controllers\ProformaInvoiceController::class);
        // Sales: Invoices
        Route::get('sales-invoices/export', [\App\Http\Controllers\SalesInvoiceController::class, 'export'])->name('sales-invoices.export');
        Route::resource('sales-invoices', \App\Http\Controllers\SalesInvoiceController::class);
        Route::post('sales-invoices/{id}/void', [\App\Http\Controllers\SalesInvoiceController::class, 'void'])->name('sales-invoices.void');
        Route::delete('sales-invoices/{sales_invoice}', fn() => redirect()->back()->with('error', 'Invoices cannot be deleted. Use Void instead.'))->name('sales-invoices.destroy');
        Route::get('sales-invoices/{id}/pay', [\App\Http\Controllers\SalesInvoiceController::class, 'showPaymentForm'])->name('sales-invoices.pay');
        Route::post('sales-invoices/{id}/pay', [\App\Http\Controllers\SalesInvoiceController::class, 'storePayment'])->name('sales-invoices.pay.store');
        Route::get('sales-invoices/{id}/print', [\App\Http\Controllers\SalesInvoiceController::class, 'print'])->name('sales-invoices.print');
        Route::get('sales-invoices/{id}/thermal-print', [\App\Http\Controllers\SalesInvoiceController::class, 'thermalPrint'])->name('sales-invoices.thermal-print');
        Route::get('sales-invoices/{id}/thermal-escpos', [\App\Http\Controllers\SalesInvoiceController::class, 'thermalEscposPrint'])->name('sales-invoices.thermal-escpos');
        Route::get('api/thermal-printers', [\App\Http\Controllers\SalesInvoiceController::class, 'getThermalPrinters'])->name('api.thermal-printers');
        Route::post('api/thermal-direct-print/{id}', [\App\Http\Controllers\SalesInvoiceController::class, 'thermalDirectPrint'])->name('api.thermal-direct-print');

        // ── Print Bridge (queue-based direct printing) ──
        Route::post('sales-invoices/{id}/direct-print', [\App\Http\Controllers\PrintBridgeController::class, 'enqueue'])->name('sales-invoices.direct-print');
        Route::get('print-jobs/{id}/status',             [\App\Http\Controllers\PrintBridgeController::class, 'status'])->name('print-jobs.status');
        Route::post('sales-invoices/thermal-print/client-log', [\App\Http\Controllers\PrintBridgeController::class, 'clientLog'])->name('sales-invoices.thermal-print.client-log');
        Route::get('api/qz-cert', [\App\Http\Controllers\SalesInvoiceController::class, 'qzCertificate'])->name('api.qz-cert');
        Route::post('api/qz-sign', [\App\Http\Controllers\SalesInvoiceController::class, 'qzSign'])->name('api.qz-sign');
        Route::get('api/qz-escpos/{id}', [\App\Http\Controllers\SalesInvoiceController::class, 'qzEscposBytes'])->name('api.qz-escpos');
        Route::get('api/ble-escpos/{id}', [\App\Http\Controllers\SalesInvoiceController::class, 'bleEscposBytes'])->name('api.ble-escpos');
        Route::get('api/sales-invoices/customer-last-credit/{customerId}', [\App\Http\Controllers\SalesInvoiceController::class, 'getCustomerLastCredit']);
        Route::get('api/sales-invoices/product-stock/{productId}', [\App\Http\Controllers\SalesInvoiceController::class, 'getProductStock']);
        Route::get('api/sales-invoices/customer-prices/{customerId}', [\App\Http\Controllers\SalesInvoiceController::class, 'getCustomerPrices']);
        // Sales: Receipts
        Route::get('proforma-invoices/{id}/receipt', [\App\Http\Controllers\ReceiptController::class, 'printProformaReceipt'])->name('proforma-invoices.receipt');
        Route::get('api/sales-receipts/pending-invoices/{customerId}', [\App\Http\Controllers\SalesReceiptController::class, 'getPendingInvoices']);
        Route::get('api/sales-receipts/customer-credit-history/{customerId}', [\App\Http\Controllers\SalesReceiptController::class, 'getCustomerCreditHistory']);
        Route::get('api/sales-receipts/bank-accounts/{bankId}', [\App\Http\Controllers\SalesReceiptController::class, 'getBankAccounts']);
        Route::get('sales-receipts/{id}/print', [\App\Http\Controllers\SalesReceiptController::class, 'print'])->name('sales-receipts.print');
        Route::post('sales-receipts/print-selected', [\App\Http\Controllers\SalesReceiptController::class, 'printSelected'])->name('sales-receipts.print-selected');
        Route::resource('sales-receipts', \App\Http\Controllers\SalesReceiptController::class);

        // Stock List
        Route::get('stock-list', [\App\Http\Controllers\StockListController::class, 'index'])->name('stock-list.index');
        Route::get('stock-list/print', [\App\Http\Controllers\StockListController::class, 'print'])->name('stock-list.print');
        Route::get('stock-list/export', [\App\Http\Controllers\StockListController::class, 'export'])->name('stock-list.export');
        Route::get('stock-list/import', [\App\Http\Controllers\StockListController::class, 'importForm'])->name('stock-list.import.form');
        Route::post('stock-list/import', [\App\Http\Controllers\StockListController::class, 'import'])->name('stock-list.import');
        Route::get('stock-list/add-stock', [\App\Http\Controllers\StockListController::class, 'addStockForm'])->name('stock-list.add-stock.form');
        Route::post('stock-list/add-stock', [\App\Http\Controllers\StockListController::class, 'addStock'])->name('stock-list.add-stock');

        // Old Sales Invoices (from invoice_references)
        Route::get('old-sales-invoices', [\App\Http\Controllers\OldSalesInvoiceController::class, 'index'])->name('old-sales-invoices.index');
        Route::get('old-sales-invoices/{id}/receipt', [\App\Http\Controllers\OldSalesInvoiceController::class, 'showReceiptForm'])->name('old-sales-invoices.receipt');
        Route::post('old-sales-invoices/{id}/receipt', [\App\Http\Controllers\OldSalesInvoiceController::class, 'storeReceipt'])->name('old-sales-invoices.receipt.store');
        Route::get('old-sales-invoices/{id}/print', [\App\Http\Controllers\OldSalesInvoiceController::class, 'print'])->name('old-sales-invoices.print'); 
    });


    // Products (Shared View) - Defined AFTER Admin Actions to allow public read access
    Route::resource('products', \App\Http\Controllers\ProductController::class)->only(['index', 'show'])->middleware('staff_gate');

    // API Routes for React Catalogue
    Route::get('/api/products/catalogue', [\App\Http\Controllers\Api\ProductCatalogueController::class, 'index']);

    // Product List (e-commerce style + cart + invoice)
    // Chatbot AI
    Route::post('api/chatbot/message', [\App\Http\Controllers\ChatbotController::class, 'message'])->name('api.chatbot.message');

    Route::middleware('staff_gate')->group(function () {
        Route::get('product-list', [\App\Http\Controllers\ProductListController::class, 'index'])->name('product-list.index');
        Route::get('api/product-list/associate/{id}', [\App\Http\Controllers\ProductListController::class, 'getAssociateDetails'])->name('product-list.associate');
        Route::post('api/product-list/invoice', [\App\Http\Controllers\ProductListController::class, 'createInvoice'])->name('product-list.invoice.create');
        Route::post('api/product-list/invoice/{id}/confirm', [\App\Http\Controllers\ProductListController::class, 'confirmInvoice'])->name('product-list.invoice.confirm');
        Route::get('api/product-list/invoice/{id}', [\App\Http\Controllers\ProductListController::class, 'getInvoiceDetails'])->name('product-list.invoice.details');
        Route::get('product-list/invoice/{id}/print', [\App\Http\Controllers\ProductListController::class, 'print'])->name('product-list.invoice.print');
        Route::get('product-list/invoice/{id}/thermal-print', [\App\Http\Controllers\ProductListController::class, 'thermalPrint'])->name('product-list.invoice.thermal-print');
    });
});
