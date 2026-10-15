import React, { useState, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';

// ── Product select with Select2 ──────────────────────────────────────────────
const ProductSelect = ({ products, value, onChange }) => {
    const ref = useRef(null);

    useEffect(() => {
        if (!window.$) return;
        const $el = window.$(ref.current);
        $el.select2({ theme: 'bootstrap-5', placeholder: 'Select Product…', allowClear: true, width: '100%' });
        $el.on('select2:select', e => onChange(e.params.data.id));
        $el.on('select2:clear', () => onChange(''));
        return () => {
            if ($el.data('select2')) $el.select2('destroy');
            $el.off('select2:select select2:clear');
        };
    }, []);

    useEffect(() => {
        if (!window.$) return;
        const $el = window.$(ref.current);
        if ($el.val() != value) $el.val(value).trigger('change');
    }, [value]);

    return (
        <select ref={ref} className="form-select form-select-sm">
            <option value="">Select Product…</option>
            {products.map(p => (
                <option key={p.id} value={p.id}>{p.sku} – {p.name}</option>
            ))}
        </select>
    );
};

// ── Helpers ──────────────────────────────────────────────────────────────────
const calcLine = (item) => {
    const bQty   = parseFloat(item.box_qty)   || 0;
    const iQty   = parseFloat(item.item_qty)  || 0;
    const bPrice = parseFloat(item.box_price) || 0;
    const uPrice = parseFloat(item.unit_price)|| 0;
    const tRate  = parseFloat(item.tax_rate)  || 0;
    const base   = (bQty * bPrice) + (iQty * uPrice);
    const tax    = (base * tRate) / 100;
    return { base, tax, total: base + tax };
};

const blankItem = () => ({
    id: Date.now() + Math.random(),
    product_id: '',
    description: '',
    box_qty: 0,
    item_qty: 0,
    box_price: 0,
    unit_price: 0,
    tax_rate: 0,
    tax_amount: '0.00',
    total_price: '0.00',
    // Return-against-invoice tracking
    original_box_qty: null,
    original_item_qty: null,
    original_total_qty: null,
    pack_size: 1,
    from_invoice: false,
});

// ── Main form ────────────────────────────────────────────────────────────────
const SalesCreditNoteForm = ({ customers, products, currencies, taxes, salespeople, transactionNumber }) => {

    const [header, setHeader] = useState({
        customer_id: '',
        salesperson_id: '',
        transaction_number: transactionNumber,
        transaction_date: new Date().toISOString().split('T')[0],
        currency_id: currencies.find(c => c.is_default == 1)?.id || currencies[0]?.id || '',
        exchange_rate: 1.0,
        billing_address: '',
        shipping_address: '',
        remarks: '',
        invoice_id: '',
        // Computed
        subtotal: 0,
        discount_percentage: 0,
        discount_amount: 0,
        total_tax_amount: 0,
        round_off: 0,
        net_total: 0,
    });

    const [items, setItems]                       = useState([blankItem()]);
    const [loading, setLoading]                   = useState(false);
    const [customerInvoices, setCustomerInvoices] = useState([]);
    const [invoiceLoading, setInvoiceLoading]     = useState(false);
    const [selectedInvoice, setSelectedInvoice]   = useState(null);

    const customerSelectRef = useRef(null);

    // ── Select2 for customer ──────────────────────────────────────────────────
    useEffect(() => {
        if (!customerSelectRef.current || !window.$) return;
        const $sel = window.$(customerSelectRef.current);
        $sel.select2({ theme: 'bootstrap-5', placeholder: 'Select Customer…', allowClear: true, width: '100%' });
        $sel.on('select2:select', e => handleHeaderChange({ target: { name: 'customer_id', value: e.params.data.id } }));
        $sel.on('select2:clear', () => handleHeaderChange({ target: { name: 'customer_id', value: '' } }));
        return () => {
            if ($sel.data('select2')) $sel.select2('destroy');
            $sel.off('select2:select select2:clear');
        };
    }, [customers]);

    useEffect(() => {
        if (!customerSelectRef.current || !window.$) return;
        const $sel = window.$(customerSelectRef.current);
        if ($sel.val() != header.customer_id) $sel.val(header.customer_id).trigger('change.select2');
    }, [header.customer_id]);

    // ── When customer changes: prefill address + fetch their invoices ─────────
    useEffect(() => {
        setCustomerInvoices([]);
        setSelectedInvoice(null);
        setHeader(prev => ({ ...prev, invoice_id: '' }));

        if (!header.customer_id) {
            setHeader(prev => ({ ...prev, billing_address: '', shipping_address: '' }));
            return;
        }

        const customer = customers.find(c => c.id == header.customer_id);
        if (customer) {
            const addr = [customer.address_line_1, customer.address_line_2, customer.city, customer.postal_code]
                .filter(Boolean).join(', ');
            setHeader(prev => ({
                ...prev,
                billing_address:  addr,
                shipping_address: addr,
                currency_id: customer.currency_id || prev.currency_id,
            }));
        }

        axios.get(`/api/sales-credit-notes/customer-invoices/${header.customer_id}`)
            .then(res => { if (res.data.success) setCustomerInvoices(res.data.invoices); })
            .catch(err => console.error('Error fetching invoices:', err));
    }, [header.customer_id]);

    // ── Load items from selected invoice ─────────────────────────────────────
    const handleLoadInvoiceItems = () => {
        if (!header.invoice_id) return;
        setInvoiceLoading(true);

        axios.get(`/api/sales-credit-notes/invoice-items/${header.invoice_id}`)
            .then(res => {
                if (res.data.success) {
                    setSelectedInvoice(res.data.invoice);
                    const loaded = res.data.items.map(inv => ({
                        ...blankItem(),
                        product_id:          inv.product_id,
                        description:         inv.description,
                        // Pre-fill with original invoice quantities; user can reduce for partial returns
                        box_qty:             inv.original_box_qty,
                        item_qty:            inv.original_item_qty,
                        unit_price:          inv.unit_price,
                        box_price:           inv.box_price,
                        tax_rate:            inv.tax_rate,
                        tax_amount:          '0.00',
                        total_price:         '0.00',
                        original_box_qty:    inv.original_box_qty,
                        original_item_qty:   inv.original_item_qty,
                        original_total_qty:  inv.original_total_qty,
                        pack_size:           inv.pack_size,
                        from_invoice:        true,
                        // display helpers
                        product_name: inv.product_name,
                        product_sku:  inv.product_sku,
                    }));
                    setItems(loaded);
                }
            })
            .catch(err => console.error('Error loading invoice items:', err))
            .finally(() => setInvoiceLoading(false));
    };

    // ── Recalculate footer totals whenever items or discount change ───────────
    useEffect(() => {
        let subTotal = 0;
        let totalTax = 0;

        const recalced = items.map(item => {
            const { base, tax, total } = calcLine(item);
            subTotal += base;
            totalTax += tax;
            return { ...item, tax_amount: tax.toFixed(2), total_price: total.toFixed(2) };
        });

        const discAmt  = (subTotal * (parseFloat(header.discount_percentage) || 0)) / 100;
        const net      = Math.max(0, subTotal - discAmt + totalTax + (parseFloat(header.round_off) || 0));

        setHeader(prev => ({
            ...prev,
            subtotal:           subTotal.toFixed(2),
            total_tax_amount:   totalTax.toFixed(2),
            discount_amount:    discAmt.toFixed(2),
            net_total:          net.toFixed(2),
        }));

        // Sync tax_amount + total_price on items without triggering the effect again
        setItems(recalced);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [items.map(i => `${i.box_qty}|${i.item_qty}|${i.box_price}|${i.unit_price}|${i.tax_rate}`).join(','),
        header.discount_percentage, header.round_off]);

    // ── Handlers ──────────────────────────────────────────────────────────────
    const handleHeaderChange = e => {
        const { name, value } = e.target;
        setHeader(prev => ({ ...prev, [name]: value }));
    };

    const handleItemChange = (index, field, value) => {
        setItems(prev => {
            const next = [...prev];
            next[index] = { ...next[index], [field]: value };

            if (field === 'product_id' && value) {
                const p = products.find(pr => pr.id == value);
                if (p) {
                    next[index].description = p.name;
                    next[index].unit_price  = p.price?.sales_price_item || 0;
                    next[index].box_price   = p.price?.sales_price_box  || 0;
                    next[index].tax_rate    = p.price?.tax_rate          || 0;
                    next[index].pack_size   = p.pack_size_qty            || 1;
                }
            }
            return next;
        });
    };

    const addItem  = () => setItems(prev => [...prev, blankItem()]);
    const removeItem = index => {
        if (items.length === 1) {
            // Reset invoice state so the form returns to manual-entry mode
            setSelectedInvoice(null);
            setHeader(prev => ({ ...prev, invoice_id: '' }));
            setItems([blankItem()]);
        } else {
            setItems(prev => prev.filter((_, i) => i !== index));
        }
    };

    // ── Return-qty guard: warn but don't block (server validates) ─────────────
    const isQtyExceeded = (item) => {
        if (!item.from_invoice || item.original_total_qty === null) return false;
        const returnQty = (parseFloat(item.box_qty) || 0) * (item.pack_size || 1)
                        + (parseFloat(item.item_qty) || 0);
        return returnQty > item.original_total_qty;
    };

    // ── Submit ────────────────────────────────────────────────────────────────
    const handleSubmit = async (action) => {
        if (!header.customer_id) return alert('Please select a customer.');
        if (!items[0].product_id) return alert('Add at least one product.');

        const exceeded = items.filter(isQtyExceeded);
        if (exceeded.length > 0) {
            return alert(`Return qty exceeds original sold qty for: ${exceeded.map(i => i.description || i.product_id).join(', ')}`);
        }

        setLoading(true);
        try {
            const res = await axios.post('/sales-credit-notes', { ...header, items, action });
            if (res.data.success) {
                window.location.href = res.data.redirect;
            }
        } catch (err) {
            alert('Error: ' + (err.response?.data?.error || err.message));
        } finally {
            setLoading(false);
        }
    };

    // ── Render ────────────────────────────────────────────────────────────────
    return (
        <div className="card">
            <div className="card-body">
                <h5 className="card-title mb-4">Sales Return / Credit Note</h5>

                {/* ── Row 1: Customer + Transaction No + Date + Salesperson ── */}
                <div className="row g-3 mb-3">
                    <div className="col-md-3">
                        <label className="form-label">Customer <span className="text-danger">*</span></label>
                        <select className="form-select" name="customer_id" ref={customerSelectRef}>
                            <option value="">Select Customer…</option>
                            {customers.map(c => <option key={c.id} value={c.id}>{c.name} ({c.code})</option>)}
                        </select>
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Credit Note No.</label>
                        <input type="text" className="form-control bg-light" value={header.transaction_number} readOnly />
                    </div>
                    <div className="col-md-2">
                        <label className="form-label">Date <span className="text-danger">*</span></label>
                        <input type="date" className="form-control" name="transaction_date" value={header.transaction_date} onChange={handleHeaderChange} />
                    </div>
                    <div className="col-md-2">
                        <label className="form-label">Salesperson</label>
                        <select className="form-select" name="salesperson_id" value={header.salesperson_id} onChange={handleHeaderChange}>
                            <option value="">— None —</option>
                            {salespeople.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="col-md-2">
                        <label className="form-label">Currency</label>
                        <select className="form-select" name="currency_id" value={header.currency_id} onChange={handleHeaderChange}>
                            {currencies.map(c => <option key={c.id} value={c.id}>{c.code}</option>)}
                        </select>
                    </div>
                </div>

                {/* ── Return Against Invoice ── */}
                {header.customer_id && (
                    <div className="card bg-light border-primary mb-4">
                        <div className="card-body py-3">
                            <h6 className="card-title mb-3 text-primary">
                                <i className="ti ti-receipt me-1"></i> Return Against Invoice (Optional)
                            </h6>
                            {customerInvoices.length === 0 ? (
                                <div className="text-muted small">No confirmed invoices found for this customer.</div>
                            ) : (
                                <div className="row g-2 align-items-end">
                                    <div className="col-md-5">
                                        <label className="form-label mb-1 small fw-medium">Select Invoice to Return Against</label>
                                        <select
                                            className="form-select form-select-sm"
                                            name="invoice_id"
                                            value={header.invoice_id}
                                            onChange={handleHeaderChange}
                                        >
                                            <option value="">— Manual return (no invoice) —</option>
                                            {customerInvoices.map(inv => (
                                                <option key={inv.id} value={inv.id}>
                                                    {inv.invoice_number} &nbsp;|&nbsp; {inv.invoice_date} &nbsp;|&nbsp;
                                                    Total: {parseFloat(inv.net_total).toFixed(2)} &nbsp;|&nbsp;
                                                    Balance: {parseFloat(inv.balance_due).toFixed(2)}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="col-md-auto">
                                        <button
                                            type="button"
                                            className="btn btn-sm btn-primary"
                                            onClick={handleLoadInvoiceItems}
                                            disabled={!header.invoice_id || invoiceLoading}
                                        >
                                            {invoiceLoading
                                                ? <><span className="spinner-border spinner-border-sm me-1"></span>Loading…</>
                                                : <><i className="ti ti-download me-1"></i>Load Invoice Items</>}
                                        </button>
                                    </div>
                                    {selectedInvoice && (
                                        <div className="col-12">
                                            <div className="alert alert-success py-2 mb-0 d-flex gap-4 align-items-center">
                                                <span><strong>Invoice:</strong> {selectedInvoice.invoice_number}</span>
                                                <span><strong>Date:</strong> {selectedInvoice.invoice_date}</span>
                                                <span><strong>Total:</strong> {parseFloat(selectedInvoice.net_total).toFixed(2)}</span>
                                                <small className="text-muted">Set return quantities below (cannot exceed original sold qty)</small>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* ── Items Table ── */}
                <div className="table-responsive mb-4">
                    <table className="table table-bordered table-sm align-middle">
                        <thead className="table-light">
                            <tr>
                                <th style={{ width: '18%' }}>Product</th>
                                {selectedInvoice && <th className="text-center bg-info-subtle" style={{ width: '8%' }}>Orig. Qty</th>}
                                <th style={{ width: '7%' }}>Box Qty</th>
                                <th style={{ width: '7%' }}>Item Qty</th>
                                <th style={{ width: '8%' }}>Box Price</th>
                                <th style={{ width: '8%' }}>Unit Price</th>
                                <th style={{ width: '6%' }}>Tax %</th>
                                <th style={{ width: '8%' }} className="text-end">Tax Amt</th>
                                <th style={{ width: '9%' }} className="text-end">Total</th>
                                <th style={{ width: '4%' }}></th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((item, index) => {
                                const exceeded = isQtyExceeded(item);
                                return (
                                    <tr key={item.id} className={exceeded ? 'table-danger' : ''}>
                                        <td>
                                            {item.from_invoice ? (
                                                <div>
                                                    <span className="fw-semibold">{item.product_sku}</span>
                                                    <div className="small text-muted">{item.product_name}</div>
                                                </div>
                                            ) : (
                                                <ProductSelect
                                                    products={products}
                                                    value={item.product_id}
                                                    onChange={val => handleItemChange(index, 'product_id', val)}
                                                />
                                            )}
                                        </td>
                                        {selectedInvoice && (
                                            <td className="text-center bg-info-subtle">
                                                {item.from_invoice ? (
                                                    <span className="badge bg-info">
                                                        {item.original_box_qty > 0
                                                            ? `${item.original_box_qty}B + ${item.original_item_qty}P`
                                                            : `${item.original_item_qty} pcs`}
                                                    </span>
                                                ) : (
                                                    <span className="text-muted">—</span>
                                                )}
                                            </td>
                                        )}
                                        <td>
                                            <input
                                                type="number" min="0"
                                                className="form-control form-control-sm text-end"
                                                value={item.box_qty}
                                                onChange={e => handleItemChange(index, 'box_qty', e.target.value)}
                                            />
                                        </td>
                                        <td>
                                            <div>
                                                <input
                                                    type="number" min="0"
                                                    className={`form-control form-control-sm text-end ${exceeded ? 'border-danger' : ''}`}
                                                    value={item.item_qty}
                                                    onChange={e => handleItemChange(index, 'item_qty', e.target.value)}
                                                />
                                                {exceeded && (
                                                    <div className="text-danger" style={{ fontSize: '0.68rem' }}>
                                                        Max: {item.original_total_qty}
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                        <td>
                                            <input
                                                type="number" step="0.01" min="0"
                                                className="form-control form-control-sm text-end"
                                                value={item.box_price}
                                                onChange={e => handleItemChange(index, 'box_price', e.target.value)}
                                            />
                                        </td>
                                        <td>
                                            <input
                                                type="number" step="0.01" min="0"
                                                className="form-control form-control-sm text-end"
                                                value={item.unit_price}
                                                onChange={e => handleItemChange(index, 'unit_price', e.target.value)}
                                            />
                                        </td>
                                        <td>
                                            <input
                                                type="number" step="0.01" min="0"
                                                className="form-control form-control-sm text-end"
                                                value={item.tax_rate}
                                                onChange={e => handleItemChange(index, 'tax_rate', e.target.value)}
                                            />
                                        </td>
                                        <td className="text-end text-muted small">{item.tax_amount}</td>
                                        <td className="text-end fw-bold">{item.total_price}</td>
                                        <td className="text-center">
                                            <button
                                                type="button"
                                                className="btn btn-sm text-danger"
                                                onClick={() => removeItem(index)}
                                                tabIndex={-1}
                                            ><i className="ti ti-trash"></i></button>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                    {!selectedInvoice && (
                        <button type="button" className="btn btn-sm btn-outline-secondary" onClick={addItem}>
                            <i className="ti ti-plus me-1"></i>Add Line
                        </button>
                    )}
                </div>

                {/* ── Footer Totals ── */}
                <div className="row justify-content-end">
                    <div className="col-md-4">
                        <table className="table table-sm table-borderless">
                            <tbody>
                                <tr>
                                    <td>Subtotal</td>
                                    <td className="text-end fw-bold">{header.subtotal}</td>
                                </tr>
                                <tr>
                                    <td>Discount (%)</td>
                                    <td className="text-end">
                                        <input
                                            type="number" min="0" max="100"
                                            className="form-control form-control-sm d-inline-block text-end"
                                            style={{ width: '90px' }}
                                            name="discount_percentage"
                                            value={header.discount_percentage}
                                            onChange={handleHeaderChange}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>Discount Amount</td>
                                    <td className="text-end text-danger">−{header.discount_amount}</td>
                                </tr>
                                <tr>
                                    <td>Tax</td>
                                    <td className="text-end">{header.total_tax_amount}</td>
                                </tr>
                                <tr>
                                    <td>Round Off</td>
                                    <td className="text-end">
                                        <input
                                            type="number" step="0.01"
                                            className="form-control form-control-sm d-inline-block text-end"
                                            style={{ width: '90px' }}
                                            name="round_off"
                                            value={header.round_off}
                                            onChange={handleHeaderChange}
                                        />
                                    </td>
                                </tr>
                                <tr className="border-top fw-bold fs-5">
                                    <td>Credit Total</td>
                                    <td className="text-end text-primary">{header.net_total}</td>
                                </tr>
                                {header.invoice_id && selectedInvoice && (
                                    <tr>
                                        <td colSpan="2">
                                            <div className="alert alert-info py-2 mb-0 small">
                                                This credit of <strong>{header.net_total}</strong> will reduce invoice{' '}
                                                <strong>{selectedInvoice.invoice_number}</strong>'s outstanding balance when submitted.
                                            </div>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* ── Billing Address + Remarks ── */}
                <div className="row g-3 mb-4">
                    <div className="col-md-6">
                        <label className="form-label">Billing Address</label>
                        <textarea className="form-control" rows="2" name="billing_address" value={header.billing_address} onChange={handleHeaderChange}></textarea>
                    </div>
                    <div className="col-md-6">
                        <label className="form-label">Remarks / Reason for Return</label>
                        <textarea className="form-control" rows="2" name="remarks" value={header.remarks} onChange={handleHeaderChange} placeholder="e.g. Damaged goods, wrong item shipped…"></textarea>
                    </div>
                </div>

                {/* ── Actions ── */}
                <div className="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="button" className="btn btn-light" onClick={() => window.history.back()}>Cancel</button>
                    <button type="button" className="btn btn-outline-secondary" onClick={() => handleSubmit('draft')} disabled={loading}>
                        <i className="ti ti-device-floppy me-1"></i>Save Draft
                    </button>
                    <button type="button" className="btn btn-primary" onClick={() => handleSubmit('submit')} disabled={loading}>
                        {loading
                            ? <><span className="spinner-border spinner-border-sm me-1"></span>Submitting…</>
                            : <><i className="ti ti-check me-1"></i>Submit & Post</>}
                    </button>
                </div>
            </div>
        </div>
    );
};

// ── Mount ────────────────────────────────────────────────────────────────────
const rootEl = document.getElementById('sales-credit-note-form-root');
if (rootEl) {
    const props = {
        customers:         JSON.parse(rootEl.dataset.customers),
        products:          JSON.parse(rootEl.dataset.products),
        currencies:        JSON.parse(rootEl.dataset.currencies),
        taxes:             JSON.parse(rootEl.dataset.taxes),
        salespeople:       JSON.parse(rootEl.dataset.salespeople),
        transactionNumber: rootEl.dataset.transactionNumber,
    };
    createRoot(rootEl).render(<SalesCreditNoteForm {...props} />);
}

export default SalesCreditNoteForm;
