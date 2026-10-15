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

const DeliveryOrderForm = ({ customers, currencies, products, salespeople, doNumber }) => {
    // Header State
    const [header, setHeader] = useState({
        customer_id: '',
        salesperson_id: '',
        quotation_id: '', // Sales Order Ref
        do_number: doNumber,
        delivery_date: new Date().toISOString().split('T')[0],
        currency_id: currencies.find(c => c.code === 'USD')?.id || currencies[0]?.id || '',
        exchange_rate: 1.0,
        billing_address: '',
        shipping_address: '',
        remarks: '',
        total_amount: 0 // Optional
    });

    // Valid Sales Orders for dropdown
    const [salesOrders, setSalesOrders] = useState([]);

    // Items State
    const [items, setItems] = useState([
        { id: Date.now(), product_id: '', description: '', box_qty: 0, item_qty: 0, total_qty_loose: 0, unit_price: 0, tax_rate: 0, tax_amount: 0, total_price: 0 }
    ]);

    // Loading/Submitting
    const [loading, setLoading] = useState(false);
    const customerSelectRef = useRef(null);

    // Initialize Select2 for Customer
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

    // Sync state for Customer
    useEffect(() => {
        if (customerSelectRef.current && window.$) {
            const $select = window.$(customerSelectRef.current);
            if ($select.val() != header.customer_id) {
                $select.val(header.customer_id).trigger('change.select2');
            }
        }
    }, [header.customer_id]);

    // Fetch Customer Details on Selection
    useEffect(() => {
        if (!header.customer_id) {
            setHeader(prev => ({ ...prev, billing_address: '', shipping_address: '', quotation_id: '' }));
            setSalesOrders([]);
            return;
        }

        const customer = customers.find(c => c.id == header.customer_id);
        if (customer) {
            let addr = customer.address_line_1;
            if (customer.address_line_2) addr += ', ' + customer.address_line_2;
            if (customer.city) addr += ', ' + customer.city;

            setHeader(prev => ({
                ...prev,
                billing_address: addr,
                shipping_address: addr, // Default shipping to billing
                currency_id: customer.currency_id || prev.currency_id,
            }));

            // Fetch Pending Sales Orders
            fetchSalesOrders(customer.id);
        }
    }, [header.customer_id]);

    const fetchSalesOrders = async (customerId) => {
        try {
            const res = await axios.get(`/api/delivery-orders/customer-orders/${customerId}`);
            setSalesOrders(res.data || []);
        } catch (err) {
            console.error(err);
        }
    };

    // Auto-populate Items when Sales Order is selected
    useEffect(() => {
        if (!header.quotation_id) return;

        const order = salesOrders.find(o => o.id == header.quotation_id);
        if (order && order.items) {
            // Map SO items to DO items
            // If we had a mechanism to track "remaining qty", we would use it here. 
            // For now, we populate 'box_qty' and 'item_qty' as requested on SO. User can reduce for partial.
            const mappedItems = order.items.map(soItem => {
                // Try to match product by ID
                const prod = products.find(p => p.id == soItem.product_id);
                return {
                    id: Date.now() + Math.random(),
                    product_id: soItem.product_id,
                    description: prod ? prod.name : 'Unknown Product',
                    box_qty: soItem.box_qty || 0,
                    item_qty: soItem.item_qty || 0,
                    total_qty_loose: 0, // Will settle in validation effect below
                    unit_price: soItem.unit_price || 0,
                    total_price: 0
                };
            });
            setItems(mappedItems);
        }
    }, [header.quotation_id]);


    // Calculations (Primarily for validation and total value)
    useEffect(() => {
        let total = 0;

        const newItems = items.map(item => {
            const bQty = parseFloat(item.box_qty) || 0;
            const iQty = parseFloat(item.item_qty) || 0;
            const price = parseFloat(item.unit_price) || 0;

            // Get Pack Size
            const prod = products.find(p => p.id == item.product_id);
            const packSize = prod?.pack_size_qty || 1;

            const totalQty = (bQty * packSize) + iQty;

            // Assuming unit_price is per loose item. If box price logic needed, it can be added. 
            // For DO, usually simple validation is key.
            const lineTotal = totalQty * price;
            total += lineTotal;

            return { ...item, total_qty_loose: totalQty, total_price: lineTotal.toFixed(2) };
        });

        // Optimization: Only update state if values fundamentally changed to avoid render loop if we were strict deps
        // But since we map every time, we shouldn't setState(items) here directly unless we diff.
        // We will just update header total.
        setHeader(prev => ({ ...prev, total_amount: total.toFixed(2) }));

        // NOTE: We are NOT calling setItems(newItems) here to avoid infinite loop. 
        // We trust the display calculation in render or 'handleItemChange' to keep 'total_qty_loose' synced in state eventually.
        // Or we can rely on render-time calc for 'total_qty_loose' display.

    }, [items, products]);

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
                newItems[index].unit_price = prod.price?.sales_price_item || 0;
                newItems[index].tax_rate = prod.price?.tax_rate || 0;
            }
        }

        // Recalc Loose Qty and Tax immediately for UI
        const prod = products.find(p => p.id == newItems[index].product_id);
        const packSize = prod?.pack_size_qty || 1;

        const bQty = parseFloat(newItems[index].box_qty) || 0;
        const iQty = parseFloat(newItems[index].item_qty) || 0;
        const price = parseFloat(newItems[index].unit_price) || 0;
        const rate = parseFloat(newItems[index].tax_rate) || 0;

        const totalQty = (bQty * packSize) + iQty;
        const baseAmount = totalQty * price;
        const taxAmount = baseAmount * (rate / 100);
        const lineTotal = baseAmount + taxAmount;

        newItems[index].total_qty_loose = totalQty;
        newItems[index].tax_amount = taxAmount.toFixed(2);
        newItems[index].total_price = lineTotal.toFixed(2);

        setItems(newItems);
    };

    const addItem = () => {
        setItems([...items, { id: Date.now(), product_id: '', description: '', box_qty: 0, item_qty: 0, total_qty_loose: 0, unit_price: 0, tax_rate: 0, tax_amount: 0, total_price: 0 }]);
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
            const res = await axios.post('/delivery-orders', payload);
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
                <h5 className="card-title mb-4">Delivery Order Details</h5>
                <div className="row g-3 mb-4">
                    <div className="col-md-3">
                        <label className="form-label">Customer <span className="text-danger">*</span></label>
                        <select className="form-select" name="customer_id" ref={customerSelectRef}>
                            <option value="">Select Customer...</option>
                            {customers.map(c => <option key={c.id} value={c.id}>{c.name} ({c.code})</option>)}
                        </select>
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Sales Order Reference</label>
                        <select className="form-select" name="quotation_id" value={header.quotation_id} onChange={handleHeaderChange} disabled={!header.customer_id}>
                            <option value="">-- Manual Entry --</option>
                            {salesOrders.map(o => (
                                <option key={o.id} value={o.id}>
                                    {o.quotation_number} ({o.transaction_date || o.created_at?.substring(0, 10)})
                                </option>
                            ))}
                        </select>
                        {header.quotation_id && <small className="text-muted">Populates items from order</small>}
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">DO Number <span className="text-danger">*</span></label>
                        <input type="text" className="form-control" name="do_number" value={header.do_number} readOnly />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Delivery Date <span className="text-danger">*</span></label>
                        <input type="date" className="form-control" name="delivery_date" value={header.delivery_date} onChange={handleHeaderChange} />
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
                    <div className="col-md-4">
                        <label className="form-label">Shipping Address</label>
                        <textarea className="form-control" rows="2" name="shipping_address" value={header.shipping_address} onChange={handleHeaderChange}></textarea>
                    </div>
                    <div className="col-md-5">
                        <label className="form-label">Billing Address (Ref)</label>
                        <textarea className="form-control" rows="2" name="billing_address" value={header.billing_address} onChange={handleHeaderChange} readOnly></textarea>
                    </div>
                </div>

                {/* Items Section */}
                <h5 className="card-title mb-3">Line Items</h5>
                <div className="table-responsive mb-4">
                    <table className="table table-bordered table-sm align-middle">
                        <thead className="table-light">
                            <tr>
                                <th style={{ width: '25%' }}>Product</th>
                                <th style={{ width: '8%' }}>Box Qty</th>
                                <th style={{ width: '8%' }}>Item Qty</th>
                                <th style={{ width: '8%' }}>Total Units</th>
                                <th style={{ width: '10%' }}>Unit Price</th>
                                <th style={{ width: '8%' }}>Tax %</th>
                                <th style={{ width: '10%' }}>Tax Amt</th>
                                <th style={{ width: '10%' }}>Total</th>
                                <th style={{ width: '5%' }}></th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((item, index) => {
                                const prod = products.find(p => p.id == item.product_id);
                                const packSize = prod?.pack_size_qty || 1;
                                const displayLoose = item.total_qty_loose > 0 ? item.total_qty_loose : (parseFloat(item.box_qty || 0) * packSize + parseFloat(item.item_qty || 0));

                                return (
                                    <tr key={item.id}>
                                        <td>
                                            <ProductSelect
                                                products={products}
                                                value={item.product_id}
                                                onChange={(val) => handleItemChange(index, 'product_id', val)}
                                            />
                                        </td>
                                        <td>
                                            <input type="number" className="form-control form-control-sm text-end" value={item.box_qty} onChange={(e) => handleItemChange(index, 'box_qty', e.target.value)} />
                                        </td>
                                        <td>
                                            <input type="number" className="form-control form-control-sm text-end" value={item.item_qty} onChange={(e) => handleItemChange(index, 'item_qty', e.target.value)} />
                                        </td>
                                        <td className="text-end fw-bold bg-light">
                                            {displayLoose}
                                        </td>
                                        <td>
                                            <input type="number" className="form-control form-control-sm text-end" value={item.unit_price} onChange={(e) => handleItemChange(index, 'unit_price', e.target.value)} />
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
                                );
                            })}
                        </tbody>
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

// Mount
const rootEl = document.getElementById('delivery-order-form-root');
if (rootEl) {
    const props = {
        customers: JSON.parse(rootEl.dataset.customers),
        currencies: JSON.parse(rootEl.dataset.currencies),
        products: JSON.parse(rootEl.dataset.products),
        salespeople: JSON.parse(rootEl.dataset.salespeople),
        doNumber: rootEl.dataset.doNumber
    };
    createRoot(rootEl).render(<DeliveryOrderForm {...props} />);
}

export default DeliveryOrderForm;
