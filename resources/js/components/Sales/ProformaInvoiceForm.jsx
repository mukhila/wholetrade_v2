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

    // Watch for external value changes (e.g. from state update)
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

const ProformaInvoiceForm = ({ customers, currencies, products, salespeople, piNumber }) => {
    // Header State
    const [header, setHeader] = useState({
        customer_id: '',
        salesperson_id: '',
        pi_number: piNumber,
        pi_date: new Date().toISOString().split('T')[0],
        valid_till: '',
        currency_id: currencies.find(c => c.is_default == 1)?.id || currencies[0]?.id || '',
        exchange_rate: 1.0,
        billing_address: '',
        shipping_address: '',
        reference_number: '',
        remarks: '',
        // Financials
        subtotal: 0,
        discount_amount: 0,
        tax_amount: 0,
        net_total: 0,
    });

    // Items State
    const [items, setItems] = useState([
        {
            id: Date.now(),
            product_id: '',
            description: '',

            box_qty: 0,
            item_qty: 0,
            total_qty: 0,

            unit_price: 0,
            discount_amount: 0,
            tax_rate: 0,
            tax_amount: 0,

            total_price: 0
        }
    ]);

    const [loading, setLoading] = useState(false);
    const customerSelectRef = useRef(null);

    // Initialize Select2
    useEffect(() => {
        if (customerSelectRef.current && window.$) {
            const $select = window.$(customerSelectRef.current);
            $select.select2({
                theme: 'bootstrap-5',
                placeholder: 'Select Customer...',
                allowClear: true,
                width: '100%'
            });

            $select.on('select2:select', (e) => {
                const val = e.params.data.id;
                handleHeaderChange({ target: { name: 'customer_id', value: val } });
            });

            $select.on('select2:clear', (e) => {
                handleHeaderChange({ target: { name: 'customer_id', value: '' } });
            });

            return () => {
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }
                $select.off('select2:select');
                $select.off('select2:clear');
            };
        }
    }, [customers]);

    // Sync state
    useEffect(() => {
        if (customerSelectRef.current && window.$) {
            const $select = window.$(customerSelectRef.current);
            if ($select.val() != header.customer_id) {
                $select.val(header.customer_id).trigger('change.select2');
            }
        }
    }, [header.customer_id]);

    // Fetch Customer Details
    useEffect(() => {
        if (!header.customer_id) {
            setHeader(prev => ({ ...prev, billing_address: '', shipping_address: '' }));
            return;
        }
        const customer = customers.find(c => c.id == header.customer_id);
        if (customer) {
            let addr = customer.address_line_1 || '';
            if (customer.address_line_2) addr += ', ' + customer.address_line_2;
            if (customer.city) addr += ', ' + customer.city;

            setHeader(prev => ({
                ...prev,
                billing_address: addr,
                shipping_address: addr, // Default to same
                currency_id: customer.currency_id || prev.currency_id,
                // Maybe set salesperson from customer if linked?
            }));
        }
    }, [header.customer_id]);


    // Calculations (Line Level sum with tax_rate)
    useEffect(() => {
        // Re-calc Header totals for display
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

    }, [items]);

    const handleHeaderChange = (e) => {
        const { name, value } = e.target;
        setHeader(prev => ({ ...prev, [name]: value }));
    };

    const handleItemChange = (index, field, value) => {
        const newItems = [...items];
        newItems[index][field] = value;

        if (field === 'product_id' && value) {
            const prod = products.find(p => p.id == value);
            if (prod) {
                newItems[index].description = prod.name;
                newItems[index].unit_price = prod.price?.sales_price_item || 0;
                newItems[index].tax_rate = prod.price?.tax_rate || 0;
            }
        }

        // Recalc Line specifics
        const prod = products.find(p => p.id == newItems[index].product_id);
        const packSize = prod?.pack_size_qty || 1;

        const bQty = parseFloat(newItems[index].box_qty) || 0;
        const iQty = parseFloat(newItems[index].item_qty) || 0;
        const lineQty = (bQty * packSize) + iQty;

        const price = parseFloat(newItems[index].unit_price) || 0;
        const disc = parseFloat(newItems[index].discount_amount) || 0;
        const rate = parseFloat(newItems[index].tax_rate) || 0;

        const baseLine = (lineQty * price) - disc;
        const tax = baseLine * (rate / 100);
        const lineTotal = baseLine + tax;

        newItems[index].total_qty = lineQty;
        newItems[index].tax_amount = tax.toFixed(2);
        newItems[index].total_price = lineTotal.toFixed(2);

        setItems(newItems);
    };

    const addItem = () => {
        setItems([...items, { id: Date.now(), product_id: '', box_qty: 0, item_qty: 0, unit_price: 0, discount_amount: 0, tax_rate: 0, tax_amount: 0, total_price: 0 }]);
    };

    const removeItem = (index) => {
        if (items.length > 1) {
            setItems(items.filter((_, i) => i !== index));
        }
    };

    const handleSubmit = async (action) => {
        if (!header.customer_id) {
            alert('Please select a customer.');
            return;
        }
        setLoading(true);
        try {
            const payload = { ...header, items, action };
            const res = await axios.post('/proforma-invoices', payload);
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
                <h5 className="card-title mb-4">Proforma Invoice Details</h5>

                {/* Header Fields */}
                <div className="row g-3 mb-4">
                    <div className="col-md-3">
                        <label className="form-label">Customer <span className="text-danger">*</span></label>
                        <select className="form-select" name="customer_id" ref={customerSelectRef}>
                            <option value="">Select Customer...</option>
                            {customers.map(s => <option key={s.id} value={s.id}>{s.name} ({s.code})</option>)}
                        </select>
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">PI Number <span className="text-danger">*</span></label>
                        <input type="text" className="form-control" name="pi_number" value={header.pi_number} readOnly />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Date <span className="text-danger">*</span></label>
                        <input type="date" className="form-control" name="pi_date" value={header.pi_date} onChange={handleHeaderChange} />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Valid Till</label>
                        <input type="date" className="form-control" name="valid_till" value={header.valid_till} onChange={handleHeaderChange} />
                    </div>
                </div>

                <div className="row g-3 mb-4">
                    <div className="col-md-3">
                        <label className="form-label">Salesperson</label>
                        <select className="form-select" name="salesperson_id" value={header.salesperson_id} onChange={handleHeaderChange}>
                            <option value="">Select Salesperson...</option>
                            {salespeople.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Currency</label>
                        <select className="form-select" name="currency_id" value={header.currency_id} onChange={handleHeaderChange}>
                            {currencies.map(c => <option key={c.id} value={c.id}>{c.code} - {c.name}</option>)}
                        </select>
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Ref Number</label>
                        <input type="text" className="form-control" name="reference_number" value={header.reference_number} onChange={handleHeaderChange} placeholder="Cust Ref/PO" />
                    </div>
                </div>

                <div className="row g-3 mb-4">
                    <div className="col-md-6">
                        <label className="form-label">Billing Address</label>
                        <textarea className="form-control" rows="2" name="billing_address" value={header.billing_address} onChange={handleHeaderChange}></textarea>
                    </div>
                    <div className="col-md-6">
                        <label className="form-label">Shipping Address</label>
                        <textarea className="form-control" rows="2" name="shipping_address" value={header.shipping_address} onChange={handleHeaderChange}></textarea>
                    </div>
                </div>

                {/* Items Table */}
                <h5 className="card-title mb-2">Items</h5>
                <div className="table-responsive mb-4">
                    <table className="table table-bordered table-sm align-middle">
                        <thead className="table-light">
                            <tr>
                                <th style={{ width: '30%' }}>Product</th>
                                <th style={{ width: '14%' }}>Qty (Box / Pcs)</th>
                                <th style={{ width: '10%' }}>Unit Price</th>
                                <th style={{ width: '8%' }}>Disc</th>
                                <th style={{ width: '8%' }}>Tax %</th>
                                <th style={{ width: '10%' }}>Tax Amt</th>
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
                                        <div className="input-group input-group-sm">
                                            <input type="number" className="form-control" placeholder="Box" value={item.box_qty} onChange={(e) => handleItemChange(index, 'box_qty', e.target.value)} />
                                            <input type="number" className="form-control" placeholder="Pcs" value={item.item_qty} onChange={(e) => handleItemChange(index, 'item_qty', e.target.value)} />
                                        </div>
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
                                    <td>
                                        <input type="number" className="form-control form-control-sm text-end bg-light" value={item.tax_amount} readOnly />
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
                                <td colSpan="6" className="text-end fw-bold">Subtotal</td>
                                <td className="text-end fw-bold">{header.subtotal}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colSpan="6" className="text-end fw-bold">Tax Total</td>
                                <td className="text-end fw-bold">{header.tax_amount}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colSpan="6" className="text-end fw-bold fs-6">Net Total</td>
                                <td className="text-end fw-bold fs-6 text-primary">{header.net_total}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <button className="btn btn-sm btn-secondary" onClick={addItem}>+ Add Line</button>
                </div>

                <div className="mb-4">
                    <label className="form-label">Terms / Remarks</label>
                    <textarea className="form-control" name="remarks" value={header.remarks} onChange={handleHeaderChange}></textarea>
                </div>

                <div className="d-flex justify-content-end gap-2">
                    <button className="btn btn-light" onClick={() => handleSubmit('draft')} disabled={loading}>Save Draft</button>
                    <button className="btn btn-primary" onClick={() => handleSubmit('submit')} disabled={loading}>Submit Proforma</button>
                </div>

            </div>
        </div>
    );
};

const rootEl = document.getElementById('proforma-invoice-form-root');
if (rootEl) {
    const props = {
        customers: JSON.parse(rootEl.dataset.customers),
        currencies: JSON.parse(rootEl.dataset.currencies),
        products: JSON.parse(rootEl.dataset.products),
        salespeople: JSON.parse(rootEl.dataset.salespeople),
        piNumber: rootEl.dataset.piNumber
    };
    createRoot(rootEl).render(<ProformaInvoiceForm {...props} />);
}

export default ProformaInvoiceForm;
