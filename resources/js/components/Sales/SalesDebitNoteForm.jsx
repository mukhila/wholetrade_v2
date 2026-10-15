import React, { useState, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';

// ── Product select with Select2 ──────────────────────────────────────────────
const ProductSelect = ({ products, value, onChange }) => {
    const selectRef = useRef(null);

    useEffect(() => {
        if (!selectRef.current || !window.$) return;
        const $select = window.$(selectRef.current);
        $select.select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Product...',
            allowClear: true,
            width: '100%',
            data: products.map(p => ({ id: p.id, text: `${p.sku} - ${p.name}` })),
        });

        if (value) $select.val(value).trigger('change');

        $select.on('select2:select', (e) => {
            const val = e.params.data.id;
            const prod = products.find(p => p.id == val);
            onChange(val, prod ? prod.name : '');
        });
        $select.on('select2:clear', () => onChange('', ''));

        return () => {
            if ($select.data('select2')) $select.select2('destroy');
            $select.off('select2:select select2:clear');
        };
    }, [products]);

    useEffect(() => {
        if (!selectRef.current || !window.$) return;
        const $select = window.$(selectRef.current);
        if ($select.val() != value) $select.val(value).trigger('change.select2');
    }, [value]);

    return <select ref={selectRef} className="form-select form-select-sm"><option value=""></option></select>;
};

// ── Helpers ──────────────────────────────────────────────────────────────────
const calcLine = (item) => {
    const qty    = parseFloat(item.quantity)   || 0;
    const price  = parseFloat(item.unit_price) || 0;
    const tRate  = parseFloat(item.tax_rate)   || 0;
    const base   = qty * price;
    const tax    = (base * tRate) / 100;
    return { base, tax, total: base + tax };
};

const blankItem = () => ({
    id: Date.now() + Math.random(),
    product_id: '',
    gl_account: '',
    description: '',
    narration: '',
    quantity: 1,
    unit_price: 0,
    tax_rate: 0,
    tax_amount: '0.00',
    total_price: '0.00',
});

// ── Main form ────────────────────────────────────────────────────────────────
const SalesDebitNoteForm = ({ customers, currencies, taxes, salespeople, products, transactionNumber }) => {

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
        subtotal: 0,
        discount_percentage: 0,
        discount_amount: 0,
        total_tax_amount: 0,
        round_off: 0,
        net_total: 0,
    });

    const [items, setItems] = useState([blankItem()]);
    const [loading, setLoading] = useState(false);
    const customerSelectRef = useRef(null);

    // ── Select2 for customer ──────────────────────────────────────────────────
    useEffect(() => {
        if (!customerSelectRef.current || !window.$) return;
        const $sel = window.$(customerSelectRef.current);
        $sel.select2({ theme: 'bootstrap-5', placeholder: 'Select Customer...', allowClear: true, width: '100%' });
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

    // ── Prefill address when customer changes ─────────────────────────────────
    useEffect(() => {
        if (!header.customer_id) {
            setHeader(prev => ({ ...prev, billing_address: '', shipping_address: '' }));
            return;
        }
        const customer = customers.find(c => c.id == header.customer_id);
        if (customer) {
            const addr = [customer.address_line_1, customer.address_line_2, customer.city]
                .filter(Boolean).join(', ');
            setHeader(prev => ({
                ...prev,
                billing_address:  addr,
                shipping_address: addr,
                currency_id: customer.currency_id || prev.currency_id,
            }));
        }
    }, [header.customer_id]);

    // ── Recalculate totals whenever qty/price/tax or discount/round-off change ─
    useEffect(() => {
        let subTotal = 0;
        let totalTax = 0;

        const recalced = items.map(item => {
            const { base, tax, total } = calcLine(item);
            subTotal += base;
            totalTax += tax;
            return { ...item, tax_amount: tax.toFixed(2), total_price: total.toFixed(2) };
        });

        const discAmt = (subTotal * (parseFloat(header.discount_percentage) || 0)) / 100;
        const net     = Math.max(0, subTotal - discAmt + totalTax + (parseFloat(header.round_off) || 0));

        setHeader(prev => ({
            ...prev,
            subtotal:         subTotal.toFixed(2),
            total_tax_amount: totalTax.toFixed(2),
            discount_amount:  discAmt.toFixed(2),
            net_total:        net.toFixed(2),
        }));

        setItems(recalced);
    // Dependency string tracks only input fields to avoid infinite loop
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [
        items.map(i => `${i.quantity}|${i.unit_price}|${i.tax_rate}`).join(','),
        header.discount_percentage,
        header.round_off,
    ]);

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
                const prod = products.find(p => p.id == value);
                if (prod) {
                    next[index].description = prod.name;
                    next[index].unit_price  = prod.price?.sales_price_item || 0;
                    next[index].tax_rate    = prod.price?.tax_rate          || 0;
                }
            }
            return next;
        });
    };

    const addItem    = () => setItems(prev => [...prev, blankItem()]);
    const removeItem = index => {
        setItems(prev => prev.length === 1 ? [blankItem()] : prev.filter((_, i) => i !== index));
    };

    // ── Submit ────────────────────────────────────────────────────────────────
    const handleSubmit = async (action) => {
        if (!header.customer_id) return alert('Please select a customer.');

        setLoading(true);
        try {
            const res = await axios.post('/sales-debit-notes', { ...header, items, action });
            if (res.data.success) {
                window.location.href = res.data.redirect;
            }
        } catch (err) {
            alert('Error: ' + (err.response?.data?.error || err.response?.data?.message || err.message));
        } finally {
            setLoading(false);
        }
    };

    // ── Render ────────────────────────────────────────────────────────────────
    return (
        <div className="card">
            <div className="card-body">
                <h5 className="card-title mb-4">Sales Debit Note Details (Service / Adjustment)</h5>

                {/* ── Row 1 ── */}
                <div className="row g-3 mb-3">
                    <div className="col-md-3">
                        <label className="form-label">Customer <span className="text-danger">*</span></label>
                        <select className="form-select" name="customer_id" ref={customerSelectRef}>
                            <option value="">Select Customer...</option>
                            {customers.map(c => <option key={c.id} value={c.id}>{c.name} ({c.code})</option>)}
                        </select>
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Transaction No. <span className="text-danger">*</span></label>
                        <input type="text" className="form-control bg-light" name="transaction_number" value={header.transaction_number} readOnly />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Date <span className="text-danger">*</span></label>
                        <input type="date" className="form-control" name="transaction_date" value={header.transaction_date} onChange={handleHeaderChange} />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Salesperson</label>
                        <select className="form-select" name="salesperson_id" value={header.salesperson_id} onChange={handleHeaderChange}>
                            <option value="">— None —</option>
                            {salespeople.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                        </select>
                    </div>
                </div>

                {/* ── Row 2 ── */}
                <div className="row g-3 mb-4">
                    <div className="col-md-3">
                        <label className="form-label">Currency</label>
                        <select className="form-select" name="currency_id" value={header.currency_id} onChange={handleHeaderChange}>
                            {currencies.map(c => <option key={c.id} value={c.id}>{c.code} - {c.name}</option>)}
                        </select>
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Exchange Rate</label>
                        <input type="number" step="0.000001" className="form-control" name="exchange_rate" value={header.exchange_rate} onChange={handleHeaderChange} />
                    </div>
                    <div className="col-md-6">
                        <label className="form-label">Billing Address</label>
                        <textarea className="form-control" rows="2" name="billing_address" value={header.billing_address} onChange={handleHeaderChange}></textarea>
                    </div>
                </div>

                {/* ── Items Table ── */}
                <h5 className="card-title mb-3">Line Items</h5>
                <div className="table-responsive mb-4">
                    <table className="table table-bordered table-sm align-middle">
                        <thead className="table-light">
                            <tr>
                                <th style={{ width: '25%' }}>Description</th>
                                <th style={{ width: '15%' }}>GL Account</th>
                                <th style={{ width: '10%' }}>Qty</th>
                                <th style={{ width: '12%' }}>Unit Price</th>
                                <th style={{ width: '8%' }}>Tax %</th>
                                <th style={{ width: '10%' }} className="text-end">Tax Amt</th>
                                <th style={{ width: '12%' }} className="text-end">Total</th>
                                <th style={{ width: '4%' }}></th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((item, index) => (
                                <tr key={item.id}>
                                    <td>
                                        <ProductSelect
                                            products={products}
                                            value={item.product_id}
                                            onChange={(val, name) => {
                                                handleItemChange(index, 'product_id', val);
                                                if (name) handleItemChange(index, 'description', name);
                                            }}
                                        />
                                        <input type="text" className="form-control form-control-sm mt-1 mb-1" value={item.description} onChange={e => handleItemChange(index, 'description', e.target.value)} placeholder="Description" />
                                        <input type="text" className="form-control form-control-sm text-muted" style={{ fontSize: '0.8rem' }} value={item.narration} onChange={e => handleItemChange(index, 'narration', e.target.value)} placeholder="Narration (Optional)" />
                                    </td>
                                    <td>
                                        <input type="text" className="form-control form-control-sm" value={item.gl_account} onChange={e => handleItemChange(index, 'gl_account', e.target.value)} placeholder="GL Code" />
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" className="form-control form-control-sm text-end" value={item.quantity} onChange={e => handleItemChange(index, 'quantity', e.target.value)} />
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" className="form-control form-control-sm text-end" value={item.unit_price} onChange={e => handleItemChange(index, 'unit_price', e.target.value)} />
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" className="form-control form-control-sm text-end" value={item.tax_rate} onChange={e => handleItemChange(index, 'tax_rate', e.target.value)} />
                                    </td>
                                    <td className="text-end text-muted small">{item.tax_amount}</td>
                                    <td className="text-end fw-bold bg-light">{item.total_price}</td>
                                    <td className="text-center">
                                        <button type="button" className="btn btn-sm text-danger" onClick={() => removeItem(index)} tabIndex={-1}>
                                            <i className="ti ti-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    <button type="button" className="btn btn-sm btn-outline-secondary" onClick={addItem}>
                        <i className="ti ti-plus me-1"></i>Add Line
                    </button>
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
                                        <input type="number" min="0" max="100"
                                            className="form-control form-control-sm d-inline-block text-end"
                                            style={{ width: '80px' }}
                                            name="discount_percentage"
                                            value={header.discount_percentage}
                                            onChange={handleHeaderChange} />
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
                                        <input type="number" step="0.01"
                                            className="form-control form-control-sm d-inline-block text-end"
                                            style={{ width: '80px' }}
                                            name="round_off"
                                            value={header.round_off}
                                            onChange={handleHeaderChange} />
                                    </td>
                                </tr>
                                <tr className="border-top fw-bold fs-5">
                                    <td>Grand Total</td>
                                    <td className="text-end text-primary">{header.net_total}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* ── Remarks ── */}
                <div className="mb-4">
                    <label className="form-label">Remarks</label>
                    <textarea className="form-control" name="remarks" value={header.remarks} onChange={handleHeaderChange}></textarea>
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
const rootEl = document.getElementById('sales-debit-note-form-root');
if (rootEl) {
    const props = {
        customers:         JSON.parse(rootEl.dataset.customers),
        currencies:        JSON.parse(rootEl.dataset.currencies),
        taxes:             JSON.parse(rootEl.dataset.taxes),
        salespeople:       JSON.parse(rootEl.dataset.salespeople),
        products:          JSON.parse(rootEl.dataset.products || '[]'),
        transactionNumber: rootEl.dataset.transactionNumber,
    };
    createRoot(rootEl).render(<SalesDebitNoteForm {...props} />);
}

export default SalesDebitNoteForm;
