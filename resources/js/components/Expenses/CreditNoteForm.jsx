import React, { useState, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';

// Reusable Product Select Component with Select2
const ProductSelect = ({ products, value, onChange }) => {
    const selectRef = useRef(null);

    useEffect(() => {
        if (!window.$) return;

        const $el = window.$(selectRef.current);

        $el.select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Product...',
            allowClear: true,
            width: '100%'
        });

        $el.on('select2:select', (e) => {
            onChange(e.params.data.id);
        });

        $el.on('select2:clear', (e) => {
            onChange('');
        });

        return () => {
            if ($el.data('select2')) {
                $el.select2('destroy');
            }
            $el.off('select2:select');
            $el.off('select2:clear');
        };
    }, []);

    // Watch for external value changes
    useEffect(() => {
        if (!window.$) return;
        const $el = window.$(selectRef.current);
        if ($el.val() != value) {
            $el.val(value).trigger('change');
        }
    }, [value]);

    return (
        <select ref={selectRef} className="form-select form-select-sm" defaultValue={value}>
            <option value="">Select Product...</option>
            {products.map(p => (
                <option key={p.id} value={p.id}>
                    {p.sku} - {p.name}
                </option>
            ))}
        </select>
    );
};

// Supplier Select Component with Select2
const SupplierSelect = ({ suppliers, value, onChange }) => {
    const selectRef = useRef(null);

    useEffect(() => {
        if (!window.$) return;

        const $el = window.$(selectRef.current);

        $el.select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Supplier...',
            allowClear: true,
            width: '100%'
        });

        $el.on('select2:select', (e) => {
            onChange({ target: { name: 'supplier_id', value: e.params.data.id } });
        });

        $el.on('select2:clear', (e) => {
            onChange({ target: { name: 'supplier_id', value: '' } });
        });

        return () => {
            if ($el.data('select2')) {
                $el.select2('destroy');
            }
            $el.off('select2:select');
            $el.off('select2:clear');
        };
    }, []);

    // Sync React state to Select2
    useEffect(() => {
        if (!window.$) return;
        const $el = window.$(selectRef.current);
        if ($el.val() != value) {
            $el.val(value).trigger('change.select2');
        }
    }, [value]);

    return (
        <select ref={selectRef} className="form-select" defaultValue={value}>
            <option value="">Select Supplier...</option>
            {suppliers.map(s => (
                <option key={s.id} value={s.id}>
                    {s.name} ({s.code})
                </option>
            ))}
        </select>
    );
};

const CreditNoteForm = ({ suppliers, products, currencies, taxes, transactionNumber }) => {
    // Header State
    const [header, setHeader] = useState({
        supplier_id: '',
        transaction_number: transactionNumber,
        transaction_date: new Date().toISOString().split('T')[0],
        currency_id: currencies.find(c => c.code === 'USD')?.id || currencies[0]?.id || '',
        exchange_rate: 1.0,
        tax_id: '',
        billing_address: '',
        shipping_address: '',
        remarks: '',
        subtotal: 0,
        discount_percentage: 0,
        discount_amount: 0,
        total_tax_amount: 0,
        round_off: 0,
        net_total: 0
    });

    // Items State
    const [items, setItems] = useState([
        { id: Date.now(), product_id: '', gl_account: '', description: '', quantity: 1, unit_price: 0, tax_rate: 0, tax_amount: 0, total_price: 0 }
    ]);

    // Loading/Submitting
    const [loading, setLoading] = useState(false);

    // Fetch Supplier Details on Selection
    useEffect(() => {
        if (!header.supplier_id) {
            setHeader(prev => ({ ...prev, billing_address: '', shipping_address: '' }));
            return;
        }

        const supplier = suppliers.find(s => s.id == header.supplier_id);
        if (supplier) {
            let addr = supplier.address_line_1;
            if (supplier.address_line_2) addr += ', ' + supplier.address_line_2;
            if (supplier.city) addr += ', ' + supplier.city;

            setHeader(prev => ({
                ...prev,
                billing_address: addr,
                currency_id: supplier.currency_id || prev.currency_id,
                tax_id: supplier.tax_id || ''
            }));
        }
    }, [header.supplier_id]);

    // Calculations
    useEffect(() => {
        let sub = 0;
        let tax = 0;

        const newItems = items.map(item => {
            const qty = parseFloat(item.quantity) || 0;
            const price = parseFloat(item.unit_price) || 0;
            const tRate = parseFloat(item.tax_rate) || 0;

            const baseTotal = qty * price;
            const tAmt = (baseTotal * tRate) / 100;
            const lineTotal = baseTotal + tAmt;

            sub += baseTotal;
            tax += tAmt;

            return { ...item, tax_amount: tAmt.toFixed(2), total_price: lineTotal.toFixed(2) };
        });

        const discAmt = (sub * (parseFloat(header.discount_percentage) || 0)) / 100;
        const subAfterDisc = sub - discAmt;
        const finalNet = subAfterDisc + tax + (parseFloat(header.round_off) || 0);

        setHeader(prev => ({
            ...prev,
            subtotal: sub.toFixed(2),
            total_tax_amount: tax.toFixed(2),
            discount_amount: discAmt.toFixed(2),
            net_total: finalNet.toFixed(2)
        }));

        // We don't update 'items' here to avoid infinite loop unless we strictly check changes.
        // But for display purposes in the render, we calculate dynamic line values.
        // Ideally we update state but need break loop. 
        // For now, calculations run on render or specific field changes.
    }, [items, header.discount_percentage, header.round_off]);

    // Handlers
    const handleHeaderChange = (e) => {
        const { name, value } = e.target;
        setHeader(prev => ({ ...prev, [name]: value }));
    };

    const handleItemChange = (index, field, value) => {
        const newItems = [...items];
        newItems[index][field] = value;

        // Auto-fill from product
        if (field === 'product_id' && value) {
            const prod = products.find(p => p.id == value);
            if (prod) {
                newItems[index].description = prod.name;
                newItems[index].unit_price = prod.price?.cost_price_item || 0;
                newItems[index].tax_rate = prod.price?.tax_rate || 0;
            }
        }

        // Recalc line immediately for UI responsiveness
        const qty = parseFloat(newItems[index].quantity) || 0;
        const price = parseFloat(newItems[index].unit_price) || 0;
        const tRate = parseFloat(newItems[index].tax_rate) || 0;

        const base = qty * price;
        const tax = (base * tRate) / 100;
        newItems[index].tax_amount = tax.toFixed(2);
        newItems[index].total_price = (base + tax).toFixed(2);

        setItems(newItems);
    };

    const addItem = () => {
        setItems([...items, { id: Date.now(), product_id: '', gl_account: '', description: '', quantity: 1, unit_price: 0, tax_rate: 0, tax_amount: 0, total_price: 0 }]);
    };

    const removeItem = (index) => {
        if (items.length > 1) {
            setItems(items.filter((_, i) => i !== index));
        }
    };

    const handleSubmit = async (action) => {
        setLoading(true);
        try {
            const payload = { ...header, items, action };
            const res = await axios.post('/admin/credit-notes', payload);
            if (res.data.success) {
                alert('Success: ' + res.data.message);
                window.location.href = res.data.redirect;
            }
        } catch (error) {
            console.error(error);
            alert('Error: ' + (error.response?.data?.message || error.response?.data?.error || error.message));
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="card">
            <div className="card-body">
                {/* Header Section */}
                <h5 className="card-title mb-4">Credit Note Details</h5>
                <div className="row g-3 mb-4">
                    <div className="col-md-3">
                        <label className="form-label">Supplier <span className="text-danger">*</span></label>
                        <SupplierSelect
                            suppliers={suppliers}
                            value={header.supplier_id}
                            onChange={handleHeaderChange}
                        />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Transaction No. <span className="text-danger">*</span></label>
                        <input type="text" className="form-control" name="transaction_number" value={header.transaction_number} readOnly />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Date <span className="text-danger">*</span></label>
                        <input type="date" className="form-control" name="transaction_date" value={header.transaction_date} onChange={handleHeaderChange} />
                    </div>
                </div>

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

                {/* Items Section */}
                <h5 className="card-title mb-3">Line Items</h5>
                <div className="table-responsive mb-4">
                    <table className="table table-bordered table-sm align-middle">
                        <thead className="table-light">
                            <tr>
                                <th style={{ width: '20%' }}>Product / Service</th>
                                <th style={{ width: '25%' }}>Description</th>
                                <th style={{ width: '10%' }}>GL Acc</th>
                                <th style={{ width: '10%' }}>Qty</th>
                                <th style={{ width: '10%' }}>Price</th>
                                <th style={{ width: '8%' }}>Tax %</th>
                                <th style={{ width: '10%' }}>Total</th>
                                <th style={{ width: '5%' }}></th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((item, index) => (
                                <tr key={item.id}>
                                    <td>
                                        <ProductSelect
                                            products={products}
                                            value={item.product_id}
                                            onChange={(val) => handleItemChange(index, 'product_id', val)}
                                        />
                                    </td>
                                    <td>
                                        <input type="text" className="form-control form-control-sm" value={item.description} onChange={(e) => handleItemChange(index, 'description', e.target.value)} placeholder="Description" />
                                    </td>
                                    <td>
                                        <input type="text" className="form-control form-control-sm" value={item.gl_account} onChange={(e) => handleItemChange(index, 'gl_account', e.target.value)} placeholder="GL Code" />
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" className="form-control form-control-sm text-end" value={item.quantity} onChange={(e) => handleItemChange(index, 'quantity', e.target.value)} />
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" className="form-control form-control-sm text-end" value={item.unit_price} onChange={(e) => handleItemChange(index, 'unit_price', e.target.value)} />
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" className="form-control form-control-sm text-end" value={item.tax_rate} onChange={(e) => handleItemChange(index, 'tax_rate', e.target.value)} />
                                    </td>
                                    <td className="text-end bg-light">
                                        {item.total_price}
                                    </td>
                                    <td className="text-center">
                                        <button className="btn btn-sm btn-soft-danger" onClick={() => removeItem(index)}><i className="ti ti-trash"></i></button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    <button className="btn btn-sm btn-secondary" onClick={addItem}>+ Add Line</button>
                </div>

                {/* Footer Totals */}
                <div className="row justify-content-end">
                    <div className="col-md-4">
                        <table className="table table-sm table-borderless">
                            <tbody>
                                <tr>
                                    <td>Subtotal:</td>
                                    <td className="text-end fw-bold">{header.subtotal}</td>
                                </tr>
                                <tr>
                                    <td>Discount (%):</td>
                                    <td className="text-end">
                                        <input type="number" className="form-control form-control-sm d-inline-block text-end" style={{ width: '80px' }} name="discount_percentage" value={header.discount_percentage} onChange={handleHeaderChange} />
                                    </td>
                                </tr>
                                <tr>
                                    <td>Tax:</td>
                                    <td className="text-end">{header.total_tax_amount}</td>
                                </tr>
                                <tr>
                                    <td>Round Off:</td>
                                    <td className="text-end">
                                        <input type="number" step="0.01" className="form-control form-control-sm d-inline-block text-end" style={{ width: '80px' }} name="round_off" value={header.round_off} onChange={handleHeaderChange} />
                                    </td>
                                </tr>
                                <tr className="border-top">
                                    <td className="fs-16 fw-bold">Grand Total:</td>
                                    <td className="fs-16 fw-bold text-end text-primary">{header.net_total}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="mb-4">
                    <label className="form-label">Remarks</label>
                    <textarea className="form-control" name="remarks" value={header.remarks} onChange={handleHeaderChange}></textarea>
                </div>

                <div className="d-flex justify-content-end gap-2">
                    <button className="btn btn-light" onClick={() => handleSubmit('draft')} disabled={loading}>Save Draft</button>
                    <button className="btn btn-primary" onClick={() => handleSubmit('submit')} disabled={loading}>Submit & Post</button>
                </div>
            </div>
        </div>
    );
};

// Mount
const rootEl = document.getElementById('credit-note-form-root');
if (rootEl) {
    const props = {
        suppliers: JSON.parse(rootEl.dataset.suppliers),
        products: JSON.parse(rootEl.dataset.products),
        currencies: JSON.parse(rootEl.dataset.currencies),
        taxes: JSON.parse(rootEl.dataset.taxes),
        transactionNumber: rootEl.dataset.transactionNumber
    };
    createRoot(rootEl).render(<CreditNoteForm {...props} />);
}

export default CreditNoteForm;
