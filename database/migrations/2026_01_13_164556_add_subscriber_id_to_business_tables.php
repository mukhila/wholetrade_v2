<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that require subscriber_id for multi-tenancy
     */
    private array $tables = [
        // Core settings
        'company_settings',

        // Master data
        'currencies',
        'timezones',
        'taxes',
        'terms',
        'incoterms',
        'paymodes',
        'banks',
        'product_brands',
        'item_categories',
        'unit_of_measures',
        'colors',
        'sizes',

        // Associates
        'associate_groups',
        'associates',

        // Products
        'products',
        'product_variants',
        'product_prices',
        'product_inventories',
        'product_images',
        'composite_products',
        'product_labels',
        'product_label_assignments',

        // Pricing
        'associate_prices',
        'group_prices',
        'promotion_prices',
        'price_audit_logs',
        'price_update_logs',

        // Quotations
        'quotations',
        'quotation_items',
        'quotation_logs',

        // Invoices
        'invoices',

        // Sales documents
        'sales_invoices',
        'sales_invoice_items',
        'sales_receipts',
        'sales_credit_notes',
        'sales_credit_note_items',
        'sales_debit_notes',
        'sales_debit_note_items',

        // Delivery
        'delivery_orders',
        'delivery_order_items',

        // Purchase documents
        'purchase_orders',
        'purchase_order_items',
        'purchase_delivery_notes',
        'purchase_delivery_note_items',

        // Supplier documents
        'supplier_credit_notes',
        'supplier_credit_note_items',
        'supplier_debit_notes',
        'supplier_debit_note_items',

        // Proforma
        'proforma_invoices',
        'proforma_invoice_items',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'subscriber_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->foreignId('subscriber_id')
                        ->nullable()
                        ->after('id')
                        ->constrained()
                        ->cascadeOnDelete();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'subscriber_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->dropForeign([$table . '_subscriber_id_foreign']);
                    $blueprint->dropColumn('subscriber_id');
                });
            }
        }
    }
};
