import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.jsx',
                'resources/js/thermal-print.js',
                'resources/js/price-settings.jsx',
                'resources/js/price-updates.jsx',
                'resources/js/quotations-blade.jsx',
                'resources/js/components/Expenses/CreditNoteForm.jsx',
                'resources/js/components/Expenses/DebitNoteForm.jsx',
                'resources/js/components/Sales/SalesCreditNoteForm.jsx',
                'resources/js/components/Sales/SalesDebitNoteForm.jsx',
                'resources/js/components/Sales/DeliveryOrderForm.jsx',
                'resources/js/components/Expenses/PurchaseDeliveryNoteForm.jsx',
                'resources/js/components/Expenses/PurchaseOrderForm.jsx',
                'resources/js/components/Sales/ProformaInvoiceForm.jsx',
                'resources/js/components/Sales/SalesInvoiceForm.jsx',
                'resources/js/components/Sales/SalesReceiptForm.jsx',
                'resources/js/components/Supplier/SupplierInvoiceForm.jsx',
            ],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
})
