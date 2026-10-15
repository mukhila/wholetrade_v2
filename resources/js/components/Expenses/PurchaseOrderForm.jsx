import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';

// Helper Component for Select2 within a Row
const Select2Product = ({ value, options, onChange, placeholder = 'Select Product...' }) => {
    const selectRef = React.useRef(null);

    useEffect(() => {
        if (selectRef.current && window.$) {
            const $select = window.$(selectRef.current);

            // Init Select2
            $select.select2({
                theme: 'bootstrap-5',
                placeholder: placeholder,
                allowClear: true,
                width: '100%',
                data: options.map(p => ({
                    id: p.id,
                    text: `${p.sku} - ${p.name}`
                }))
            });

            // Set Initial Value
            if (value) {
                $select.val(value).trigger('change.select2');
            } else {
                $select.val('').trigger('change.select2');
            }

            // Bind Event
            $select.on('select2:select', (e) => {
                const val = e.params.data.id;
                onChange(val);
            });

            $select.on('select2:clear', (e) => {
                onChange('');
            });

            return () => {
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }
                $select.off('select2:select');
                $select.off('select2:clear');
            };
        }
    }, [options]); // Re-init if options change

    // Sync value change from parent (e.g. reset)
    useEffect(() => {
        if (selectRef.current && window.$) {
            const $select = window.$(selectRef.current);
            if ($select.val() != value) {
                $select.val(value).trigger('change.select2');
            }
        }
    }, [value]);

    return <select ref={selectRef} className="form-select form-select-sm"></select>;
};

const PurchaseOrderForm = ({ suppliers, currencies, products, poNumber }) => {
    // Header State
    const [header, setHeader] = useState({
        supplier_id: '',
        po_number: poNumber,
        po_date: new Date().toISOString().split('T')[0],
        supplier_ref_no: '',
        expected_shipping_date: '',
        currency_id: currencies.find(c => c.code === 'USD')?.id || currencies[0]?.id || '',
        exchange_rate: 1.0,
        billing_address: '',
        shipping_address: '',
        remarks: '',
        // Financials
        subtotal: 0,
        discount_amount: 0,
        tax_amount: 0,
        net_total: 0,
    });

    // Tracks the selected supplier's GST option (E, I, or N)
    const [gstOption, setGstOption] = useState('E');

    // Items State
    const [items, setItems] = useState([
        {
            id: Date.now(),
            type: 'product', // 'product' or 'service'
            product_id: '',
            gl_account: '',
            description: '',
            narration: '',

            // Qty
            box_qty: 0,
            item_qty: 0,
            quantity: 1, // For service

            // Price & Tax
            unit_price: 0,
            discount_amount: 0,
            tax_rate: 0,

            // Calculated
            total_qty: 0,
            tax_amount: 0,
            total_price: 0
        }
    ]);

    const [loading, setLoading] = useState(false);

    // GST calculation matching POS logic:
    //   E (Exclusive) : tax = rate/100 * base       → total = base + tax
    //   I (Inclusive) : tax = rate/(100+rate) * base → total = base (tax already embedded)
    //   N (No GST)    : tax = 0                      → total = base
    const calcGst = (baseAmount, rate, option) => {
        if (option === 'N' || rate === 0) return 0;
        if (option === 'I') return (rate / (100 + rate)) * baseAmount;
        return (rate / 100) * baseAmount; // E (default)
    };

    // Fetch Supplier Details (including GST option)
    useEffect(() => {
        if (!header.supplier_id) {
            setHeader(prev => ({ ...prev, billing_address: '' }));
            setGstOption('E');
            return;
        }
        const supplier = suppliers.find(c => c.id == header.supplier_id);
        if (supplier) {
            let addr = supplier.address_line_1 || '';
            if (supplier.address_line_2) addr += ', ' + supplier.address_line_2;
            if (supplier.city) addr += ', ' + supplier.city;

            setGstOption(supplier.gst_option || 'E');
            setHeader(prev => ({
                ...prev,
                billing_address: addr,
                currency_id: supplier.currency_id || prev.currency_id,
            }));
        }
    }, [header.supplier_id]);


    // Recalculate header totals whenever items or gstOption changes
    useEffect(() => {
        // For Inclusive GST, the entered price already contains GST.
        // subtotal = pre-tax amount, tax_amount = extracted GST, net_total = their sum (= base).
        // For Exclusive GST, tax is added on top.
        // For No GST, tax = 0.
        const calcSub = items.reduce((acc, i) => acc + (parseFloat(i.total_price) || 0) - (parseFloat(i.tax_amount) || 0), 0);
        const calcTax = items.reduce((acc, i) => acc + (parseFloat(i.tax_amount) || 0), 0);
        const net = calcSub + calcTax;

        if (
            Math.abs(header.subtotal - calcSub) > 0.01 ||
            Math.abs(header.tax_amount - calcTax) > 0.01 ||
            Math.abs(header.net_total - net) > 0.01
        ) {
            setHeader(prev => ({
                ...prev,
                subtotal: calcSub.toFixed(2),
                tax_amount: calcTax.toFixed(2),
                net_total: net.toFixed(2)
            }));
        }
    }, [items, gstOption]);

    const handleHeaderChange = (e) => {
        const { name, value } = e.target;
        setHeader(prev => ({ ...prev, [name]: value }));
    };

    const handleItemChange = (index, field, value) => {
        const newItems = [...items];
        newItems[index][field] = value;

        // Logic for auto-filling etc
        const item = newItems[index];

        if (field === 'type') {
            // Reset fields
            item.product_id = '';
            item.gl_account = '';
            item.description = '';
            item.unit_price = 0;
        }

        if (field === 'product_id' && value) {
            const prod = products.find(p => p.id == value);
            if (prod) {
                item.description = prod.name;
                item.unit_price = prod.price?.cost_price_item || 0;
                item.tax_rate = prod.price?.tax_rate || 0;
            }
        }

        // Recalc Line
        let lineQty = 0;
        let packSize = 1;
        if (item.type === 'product' && item.product_id) {
            const prod = products.find(p => p.id == item.product_id);
            packSize = prod?.pack_size_qty || 1;
            lineQty = (parseFloat(item.box_qty) || 0) * packSize + (parseFloat(item.item_qty) || 0);
        } else {
            lineQty = parseFloat(item.quantity) || 0;
        }

        const price = parseFloat(item.unit_price) || 0;
        const discount = parseFloat(item.discount_amount) || 0;
        const rate = parseFloat(item.tax_rate) || 0;

        const baseAmount = (lineQty * price) - discount;
        const tax = calcGst(baseAmount, rate, gstOption);
        // For Inclusive GST the total stays at baseAmount (tax is extracted, not added).
        // For Exclusive GST the tax is added on top.
        const lineTotal = gstOption === 'I' ? baseAmount : baseAmount + tax;

        item.total_qty = lineQty;
        item.tax_amount = tax.toFixed(2);
        item.total_price = lineTotal.toFixed(2);

        setItems(newItems);
    };

    const addItem = () => {
        setItems([...items, { id: Date.now(), type: 'product', product_id: '', quantity: 1, box_qty: 0, item_qty: 0, unit_price: 0, discount_amount: 0, tax_rate: 0, total_price: 0 }]);
    };

    const removeItem = (index) => {
        if (items.length > 1) {
            setItems(items.filter((_, i) => i !== index));
        }
    };

    const handleSubmit = async (action) => {
        if (!header.supplier_id) {
            alert('Please select a supplier.');
            return;
        }
        setLoading(true);
        try {
            const payload = { ...header, items, action };
            const res = await axios.post('/purchase-orders', payload);
            if (res.data.success) {
                alert('Success: ' + res.data.message);
                window.location.href = res.data.redirect;
            }
        } catch (error) {
            console.error(error);
            alert('Error: ' + (error.response?.data?.message || error.message));
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="card">
            <div className="card-body">
                <h5 className="card-title mb-4">Purchase Order Details</h5>

                {/* Header Fields */}
                <div className="row g-3 mb-4">
                    <div className="col-md-3">
                        <label className="form-label">
                            Vendor <span className="text-danger">*</span>
                            {header.supplier_id && (
                                <span className={`ms-2 badge ${gstOption === 'N' ? 'bg-secondary' : gstOption === 'I' ? 'bg-info' : 'bg-success'}`}>
                                    {gstOption === 'N' ? 'No GST' : gstOption === 'I' ? 'GST Inclusive' : 'GST Exclusive'}
                                </span>
                            )}
                        </label>
                        <select className="form-select" name="supplier_id" value={header.supplier_id} onChange={handleHeaderChange}>
                            <option value="">Select Vendor...</option>
                            {suppliers.map(s => <option key={s.id} value={s.id}>{s.name} ({s.code})</option>)}
                        </select>
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">PO Number <span className="text-danger">*</span></label>
                        <input type="text" className="form-control" name="po_number" value={header.po_number} readOnly />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Date <span className="text-danger">*</span></label>
                        <input type="date" className="form-control" name="po_date" value={header.po_date} onChange={handleHeaderChange} />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Expected Delivery</label>
                        <input type="date" className="form-control" name="expected_shipping_date" value={header.expected_shipping_date} onChange={handleHeaderChange} />
                    </div>
                </div>

                <div className="row g-3 mb-4">
                    <div className="col-md-3">
                        <label className="form-label">Vendor Ref No</label>
                        <input type="text" className="form-control" name="supplier_ref_no" value={header.supplier_ref_no} onChange={handleHeaderChange} />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Currency</label>
                        <select className="form-select" name="currency_id" value={header.currency_id} onChange={handleHeaderChange}>
                            {currencies.map(c => <option key={c.id} value={c.id}>{c.code} - {c.name}</option>)}
                        </select>
                    </div>
                    <div className="col-md-6">
                        <label className="form-label">Billing Address</label>
                        <input type="text" className="form-control" name="billing_address" value={header.billing_address} onChange={handleHeaderChange} />
                    </div>
                </div>

                {/* Items Table */}
                <h5 className="card-title mb-2">Items</h5>
                <div className="table-responsive mb-4">
                    <table className="table table-bordered table-sm align-middle">
                        <thead className="table-light">
                            <tr>
                                <th style={{ width: '100px' }}>Type</th>
                                <th style={{ width: '25%' }}>Item / Account</th>
                                <th>Description</th>
                                <th style={{ width: '15%' }}>Qty</th>
                                <th>Rate</th>
                                <th>Disc</th>
                                <th>Tax %</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((item, index) => (
                                <tr key={item.id}>
                                    <td>
                                        <select className="form-select form-select-sm" value={item.type} onChange={(e) => handleItemChange(index, 'type', e.target.value)}>
                                            <option value="product">Product</option>
                                            <option value="service">Service</option>
                                        </select>
                                    </td>
                                    <td>
                                        {item.type === 'product' ? (
                                            <Select2Product
                                                value={item.product_id}
                                                options={products}
                                                onChange={(val) => handleItemChange(index, 'product_id', val)}
                                            />
                                        ) : (
                                            <input type="text" className="form-control form-control-sm" placeholder="GL Account" value={item.gl_account} onChange={(e) => handleItemChange(index, 'gl_account', e.target.value)} />
                                        )}
                                    </td>
                                    <td>
                                        <input type="text" className="form-control form-control-sm" placeholder="Desc" value={item.description} onChange={(e) => handleItemChange(index, 'description', e.target.value)} />
                                    </td>
                                    <td>
                                        {item.type === 'product' ? (
                                            <div className="input-group input-group-sm">
                                                <input type="number" className="form-control" placeholder="Box" value={item.box_qty} onChange={(e) => handleItemChange(index, 'box_qty', e.target.value)} />
                                                <input type="number" className="form-control" placeholder="Pcs" value={item.item_qty} onChange={(e) => handleItemChange(index, 'item_qty', e.target.value)} />
                                            </div>
                                        ) : (
                                            <input type="number" className="form-control form-control-sm" value={item.quantity} onChange={(e) => handleItemChange(index, 'quantity', e.target.value)} />
                                        )}
                                    </td>
                                    <td>
                                        <input type="number" className="form-control form-control-sm text-end" value={item.unit_price} onChange={(e) => handleItemChange(index, 'unit_price', e.target.value)} />
                                    </td>
                                    <td>
                                        <input type="number" className="form-control form-control-sm text-end" value={item.discount_amount} onChange={(e) => handleItemChange(index, 'discount_amount', e.target.value)} />
                                    </td>
                                    <td>
                                        <input type="number" className="form-control form-control-sm text-end" value={item.tax_rate} onChange={(e) => handleItemChange(index, 'tax_rate', e.target.value)} />
                                    </td>
                                    <td className="text-end fw-bold">
                                        {item.total_price}
                                    </td>
                                    <td className="text-center">
                                        <button className="btn btn-sm btn-soft-danger" onClick={() => removeItem(index)}><i className="ti ti-trash"></i></button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot className="table-light">
                            <tr>
                                <td colspan="7" className="text-end fw-bold">Subtotal</td>
                                <td className="text-end fw-bold">{header.subtotal}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="7" className="text-end fw-bold">Tax</td>
                                <td className="text-end fw-bold">{header.tax_amount}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="7" className="text-end fw-bold fs-6">Net Total</td>
                                <td className="text-end fw-bold fs-6 text-primary">{header.net_total}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <button className="btn btn-sm btn-secondary" onClick={addItem}>+ Add Line</button>
                </div>

                <div className="mb-4">
                    <label className="form-label">Remarks</label>
                    <textarea className="form-control" name="remarks" value={header.remarks} onChange={handleHeaderChange}></textarea>
                </div>

                <div className="d-flex justify-content-end gap-2">
                    <button className="btn btn-light" onClick={() => handleSubmit('draft')} disabled={loading}>Save Draft</button>
                    <button className="btn btn-primary" onClick={() => handleSubmit('submit')} disabled={loading}>Submit Order</button>
                </div>

            </div>
        </div>
    );
};

const rootEl = document.getElementById('purchase-order-form-root');
if (rootEl) {
    const props = {
        suppliers: JSON.parse(rootEl.dataset.suppliers),
        currencies: JSON.parse(rootEl.dataset.currencies),
        products: JSON.parse(rootEl.dataset.products),
        poNumber: rootEl.dataset.poNumber
    };
    createRoot(rootEl).render(<PurchaseOrderForm {...props} />);
}

export default PurchaseOrderForm;
