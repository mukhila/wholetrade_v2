import React, { useState, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';

// ── Product Select (Select2 on static list) ──────────────────────────────────
const ProductSelect = ({ products, value, onChange, rowIndex, selectRef: externalRef, onReady }) => {
    const internalRef = useRef(null);
    const ref = externalRef || internalRef;

    useEffect(() => {
        const $ = window.$;
        if (!$) return;
        const $el = $(ref.current);
        $el.select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Product...',
            allowClear: true,
            width: '100%',
            data: products.map(p => ({ id: p.id, text: `${p.sku} - ${p.name}${p.uom ? ' [' + p.uom.name + ']' : ''}` })),
        });
        if (value) $el.val(value).trigger('change.select2');
        if (onReady) onReady(rowIndex);

        $el.on('select2:select', e => onChange(e.params.data.id));
        $el.on('select2:clear',  () => onChange(''));

        return () => {
            $el.off('select2:select select2:clear');
            if ($el.data('select2')) $el.select2('destroy');
        };
    }, []);

    useEffect(() => {
        const $ = window.$;
        if (!$) return;
        const $el = $(ref.current);
        if ($el.val() != value) $el.val(value || null).trigger('change.select2');
    }, [value]);

    return <select ref={ref} className="form-select form-select-sm"></select>;
};

// ── Main Form ─────────────────────────────────────────────────────────────────
const SupplierInvoiceForm = ({
    suppliers, currencies, products,
    invNumber, editMode = false, invoiceId = null, initialData = null,
}) => {
    const supplierRef = useRef(null);
    const productRefs = useRef({});
    const inputRefs   = useRef({});
    const [pendingFocus, setPendingFocus] = useState(null);
    const readyRefs   = useRef(new Set());

    const defaultCurrency = currencies.find(c => c.is_default == 1) || currencies[0];

    const [header, setHeader] = useState(() => {
        const base = {
            supplier_id: '', currency_id: defaultCurrency?.id || '',
            invoice_number: invNumber,
            invoice_date: new Date().toISOString().split('T')[0],
            due_date: '', supplier_ref_number: '',
            billing_address: '', remarks: '',
            subtotal: 0, discount_amount: 0, discount_percent: 0,
            tax_amount: 0, net_total: 0,
        };
        return editMode && initialData?.header ? { ...base, ...initialData.header } : base;
    });

    const blankItem = () => ({
        id: Date.now() + Math.random(),
        product_id: '', product_name: '',
        uom_name: '', pack_size_name: '',
        box_qty: 0, item_qty: 0, total_qty: 0,
        unit_price: 0, tax_rate: 0, tax_amount: 0,
        discount_amount: 0, total_price: 0, description: '',
    });

    const [items, setItems] = useState(() => {
        if (editMode && initialData?.items?.length) {
            return initialData.items.map(i => ({ ...blankItem(), ...i }));
        }
        return [blankItem()];
    });

    const [loading, setLoading] = useState(false);

    // ── Supplier Select2 ────────────────────────────────────────────────────
    useEffect(() => {
        const $ = window.$;
        if (!$ || !supplierRef.current) return;
        const $el = $(supplierRef.current);
        $el.select2({ theme: 'bootstrap-5', placeholder: 'Select Supplier...', allowClear: true, width: '100%' });
        $el.on('select2:select', e => setHeader(h => ({ ...h, supplier_id: e.params.data.id })));
        $el.on('select2:clear',  () => setHeader(h => ({ ...h, supplier_id: '' })));
        if (header.supplier_id) $el.val(header.supplier_id).trigger('change.select2');
        return () => { $el.off('select2:select select2:clear'); if ($el.data('select2')) $el.select2('destroy'); };
    }, [suppliers]);

    // ── Totals recalculation ────────────────────────────────────────────────
    useEffect(() => {
        const subtotal = items.reduce((s, i) => s + (parseFloat(i.total_price) || 0), 0);
        const discAmt  = header.discount_amount ? parseFloat(header.discount_amount) : 0;
        const taxAmt   = items.reduce((s, i) => s + (parseFloat(i.tax_amount) || 0), 0);
        const net      = Math.max(0, subtotal - discAmt + taxAmt);
        setHeader(h => ({ ...h, subtotal: subtotal.toFixed(2), tax_amount: taxAmt.toFixed(2), net_total: net.toFixed(2) }));
    }, [items, header.discount_amount]);

    // ── Product helpers ─────────────────────────────────────────────────────
    const getProductRef = idx => {
        if (!productRefs.current[idx]) productRefs.current[idx] = React.createRef();
        return productRefs.current[idx];
    };
    const getInputRef = (idx, field) => {
        const key = `${idx}-${field}`;
        if (!inputRefs.current[key]) inputRefs.current[key] = React.createRef();
        return inputRefs.current[key];
    };

    const handleProductReady = idx => {
        readyRefs.current.add(idx);
        if (pendingFocus === idx) {
            setTimeout(() => {
                window.$?.(getProductRef(idx).current)?.select2('open');
                setPendingFocus(null);
            }, 10);
        }
    };

    const handleItemChange = (idx, field, value) => {
        setItems(prev => {
            const rows = [...prev];
            rows[idx] = { ...rows[idx], [field]: value };

            if (field === 'product_id' && value) {
                const p = products.find(p => p.id == value);
                if (p) {
                    rows[idx].product_name  = p.name;
                    rows[idx].description   = p.name;
                    rows[idx].unit_price    = p.price?.sales_price_item || 0;
                    rows[idx].tax_rate      = p.price?.tax_rate || 0;
                    rows[idx].uom_name      = p.uom?.name || '';
                    rows[idx].pack_size_name = p.pack_size?.name || '';
                }
            }

            // Recalculate row
            const row      = rows[idx];
            const pack     = products.find(p => p.id == row.product_id)?.pack_size_qty || 1;
            const qty      = (parseFloat(row.box_qty) || 0) * pack + (parseFloat(row.item_qty) || 0);
            const gross    = qty * (parseFloat(row.unit_price) || 0);
            const discount = parseFloat(row.discount_amount) || 0;
            const taxAmt   = (gross - discount) * ((parseFloat(row.tax_rate) || 0) / 100);
            rows[idx] = { ...rows[idx], total_qty: qty, tax_amount: taxAmt.toFixed(2), total_price: Math.max(0, gross - discount + taxAmt).toFixed(2) };
            return rows;
        });
    };

    const addItem = () => setItems(prev => [...prev, blankItem()]);
    const removeItem = idx => setItems(prev => prev.filter((_, i) => i !== idx));

    const handleHeaderChange = e => {
        const { name, value } = e.target;
        setHeader(h => ({ ...h, [name]: value }));
    };

    // ── Tab navigation ───────────────────────────────────────────────────────
    const handleKeyDown = (e, idx, field) => {
        const order = ['item_qty', 'unit_price', 'tax_rate'];
        if (e.key === 'Tab' && !e.shiftKey) {
            const pos = order.indexOf(field);
            if (pos === order.length - 1) {
                e.preventDefault();
                if (idx === items.length - 1) { addItem(); setPendingFocus(items.length); }
                else { window.$?.(getProductRef(idx + 1).current)?.select2('open'); }
            }
        }
    };

    // ── Submit ───────────────────────────────────────────────────────────────
    const handleSubmit = async () => {
        if (!header.supplier_id) return alert('Please select a supplier.');
        if (!items[0].product_id) return alert('Add at least one product.');

        setLoading(true);
        const payload = { ...header, items };
        try {
            const url    = editMode && invoiceId ? `/supplier-invoices/${invoiceId}` : '/supplier-invoices';
            const method = editMode ? 'put' : 'post';
            const res    = await axios[method](url, payload);
            if (res.data.success) window.location.href = res.data.redirect;
        } catch (err) {
            alert('Error: ' + (err.response?.data?.error || err.message));
        } finally {
            setLoading(false);
        }
    };

    const currSymbol = currencies.find(c => c.id == header.currency_id)?.symbol || '$';

    return (
        <div className="card">
            <div className="card-body">

                {/* ── Header ── */}
                <div className="row g-3 mb-4">
                    <div className="col-md-3">
                        <label className="form-label fw-semibold">Supplier <span className="text-danger">*</span></label>
                        <select ref={supplierRef} className="form-select">
                            <option value="">Select Supplier...</option>
                            {suppliers.map(s => (
                                <option key={s.id} value={s.id}>{s.name} ({s.code})</option>
                            ))}
                        </select>
                    </div>
                    <div className="col-md-2">
                        <label className="form-label fw-semibold">Invoice No</label>
                        <input type="text" className="form-control bg-light" value={header.invoice_number} readOnly />
                    </div>
                    <div className="col-md-2">
                        <label className="form-label fw-semibold">Invoice Date <span className="text-danger">*</span></label>
                        <input type="date" name="invoice_date" className="form-control" value={header.invoice_date} onChange={handleHeaderChange} required />
                    </div>
                    <div className="col-md-2">
                        <label className="form-label fw-semibold">Due Date</label>
                        <input type="date" name="due_date" className="form-control" value={header.due_date} onChange={handleHeaderChange} />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label fw-semibold">Supplier Ref / Invoice #</label>
                        <input type="text" name="supplier_ref_number" className="form-control" value={header.supplier_ref_number} onChange={handleHeaderChange} placeholder="Supplier's invoice number" />
                    </div>
                    <div className="col-md-2">
                        <label className="form-label fw-semibold">Currency</label>
                        <select name="currency_id" className="form-select" value={header.currency_id} onChange={handleHeaderChange}>
                            {currencies.map(c => <option key={c.id} value={c.id}>{c.code} - {c.name}</option>)}
                        </select>
                    </div>
                    <div className="col-md-4">
                        <label className="form-label fw-semibold">Billing Address</label>
                        <textarea name="billing_address" className="form-control form-control-sm" rows="2" value={header.billing_address} onChange={handleHeaderChange} placeholder="Supplier address" />
                    </div>
                    <div className="col-md-6">
                        <label className="form-label fw-semibold">Remarks</label>
                        <textarea name="remarks" className="form-control form-control-sm" rows="2" value={header.remarks} onChange={handleHeaderChange} placeholder="Internal notes..." />
                    </div>
                </div>

                {/* ── Line Items ── */}
                <div className="table-responsive mb-4">
                    <table className="table table-bordered table-sm align-middle">
                        <thead className="table-light">
                            <tr>
                                <th style={{ width: '3%' }}>#</th>
                                <th style={{ width: '22%' }}>Product</th>
                                <th style={{ width: '18%' }}>Description</th>
                                <th style={{ width: '8%' }}>Box Qty</th>
                                <th style={{ width: '8%' }}>Pcs Qty</th>
                                <th style={{ width: '8%' }}>Unit Price</th>
                                <th style={{ width: '6%' }}>Tax %</th>
                                <th style={{ width: '8%' }}>Tax Amt</th>
                                <th style={{ width: '10%' }} className="text-end">Total</th>
                                <th style={{ width: '3%' }}></th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((item, idx) => (
                                <tr key={item.id}>
                                    <td className="text-center">{idx + 1}</td>
                                    <td>
                                        <ProductSelect
                                            products={products}
                                            value={item.product_id}
                                            onChange={val => handleItemChange(idx, 'product_id', val)}
                                            rowIndex={idx}
                                            selectRef={getProductRef(idx)}
                                            onReady={handleProductReady}
                                        />
                                        {(item.uom_name || item.pack_size_name) && (
                                            <div className="mt-1 d-flex gap-1 flex-wrap">
                                                {item.uom_name && <span className="badge bg-secondary bg-opacity-10 text-secondary border" style={{fontSize:'0.65rem'}}>{item.uom_name}</span>}
                                                {item.pack_size_name && <span className="badge bg-secondary bg-opacity-10 text-secondary border" style={{fontSize:'0.65rem'}}>{item.pack_size_name}</span>}
                                            </div>
                                        )}
                                    </td>
                                    <td>
                                        <input type="text" className="form-control form-control-sm"
                                            value={item.description}
                                            onChange={e => handleItemChange(idx, 'description', e.target.value)}
                                            placeholder="Description" />
                                    </td>
                                    <td>
                                        <input type="number" className="form-control form-control-sm text-center" min="0"
                                            ref={getInputRef(idx, 'box_qty')}
                                            value={item.box_qty}
                                            onChange={e => handleItemChange(idx, 'box_qty', e.target.value)} />
                                    </td>
                                    <td>
                                        <input type="number" className="form-control form-control-sm text-center" min="0"
                                            ref={getInputRef(idx, 'item_qty')}
                                            value={item.item_qty}
                                            onChange={e => handleItemChange(idx, 'item_qty', e.target.value)}
                                            onKeyDown={e => handleKeyDown(e, idx, 'item_qty')} />
                                    </td>
                                    <td>
                                        <input type="number" className="form-control form-control-sm text-end" min="0" step="0.01"
                                            ref={getInputRef(idx, 'unit_price')}
                                            value={item.unit_price}
                                            onChange={e => handleItemChange(idx, 'unit_price', e.target.value)}
                                            onKeyDown={e => handleKeyDown(e, idx, 'unit_price')} />
                                    </td>
                                    <td>
                                        <input type="number" className="form-control form-control-sm text-center" min="0" step="0.01"
                                            ref={getInputRef(idx, 'tax_rate')}
                                            value={item.tax_rate}
                                            onChange={e => handleItemChange(idx, 'tax_rate', e.target.value)}
                                            onKeyDown={e => handleKeyDown(e, idx, 'tax_rate')} />
                                    </td>
                                    <td>
                                        <input type="number" className="form-control form-control-sm text-end bg-light"
                                            value={item.tax_amount} readOnly />
                                    </td>
                                    <td className="text-end fw-bold">
                                        {currSymbol} {parseFloat(item.total_price || 0).toFixed(2)}
                                    </td>
                                    <td className="text-center">
                                        <button className="btn btn-sm text-danger" onClick={() => removeItem(idx)} tabIndex={-1}>
                                            <i className="ti ti-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    <button className="btn btn-sm btn-outline-primary" onClick={addItem}>
                        <i className="ti ti-plus me-1"></i> Add Product
                    </button>
                </div>

                {/* ── Summary ── */}
                <div className="row justify-content-end">
                    <div className="col-md-4">
                        <table className="table table-sm table-borderless">
                            <tbody>
                                <tr>
                                    <td className="fw-semibold">Subtotal</td>
                                    <td className="text-end">{currSymbol} {parseFloat(header.subtotal || 0).toFixed(2)}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <label className="form-label mb-0">Discount</label>
                                    </td>
                                    <td>
                                        <input type="number" name="discount_amount" className="form-control form-control-sm text-end"
                                            value={header.discount_amount} onChange={handleHeaderChange} min="0" step="0.01" />
                                    </td>
                                </tr>
                                <tr>
                                    <td className="fw-semibold">Tax</td>
                                    <td className="text-end">{currSymbol} {parseFloat(header.tax_amount || 0).toFixed(2)}</td>
                                </tr>
                                <tr className="border-top border-dark">
                                    <td className="fw-bold h5">Net Total</td>
                                    <td className="text-end fw-bold h5">{currSymbol} {parseFloat(header.net_total || 0).toFixed(2)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* ── Actions ── */}
                <div className="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
                    <button className="btn btn-light" onClick={() => window.history.back()}>Cancel</button>
                    <button className="btn btn-primary" onClick={handleSubmit} disabled={loading}>
                        {loading
                            ? <><i className="ti ti-loader me-1"></i> Saving...</>
                            : <><i className="ti ti-device-floppy me-1"></i> Save as Draft</>
                        }
                    </button>
                </div>
            </div>
        </div>
    );
};

// ── Mount ─────────────────────────────────────────────────────────────────────
const rootEl = document.getElementById('supplier-invoice-form-root');
if (rootEl) {
    const props = { ...rootEl.dataset };
    Object.keys(props).forEach(k => { try { props[k] = JSON.parse(props[k]); } catch (_) {} });
    createRoot(rootEl).render(<SupplierInvoiceForm {...props} />);
}

export default SupplierInvoiceForm;
