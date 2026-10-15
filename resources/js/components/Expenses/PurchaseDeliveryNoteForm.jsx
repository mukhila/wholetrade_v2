import React, { useState, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';

// Reusable Product Select Component
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

const PurchaseDeliveryNoteForm = ({ suppliers, currencies, products, receivers, doNumber }) => {
    // Header State
    const [header, setHeader] = useState({
        supplier_id: '',
        receiver_id: '', // Logged in user ideally, but editable if needed
        do_number: doNumber,
        delivery_date: new Date().toISOString().split('T')[0],
        currency_id: currencies.find(c => c.code === 'USD')?.id || currencies[0]?.id || '',
        shipping_address: 'Main Warehouse', // Default our warehouse
        billing_address: '',
        supplier_do_reference: '',
        remarks: '',
        total_amount: 0 // Optional
    });

    // Items State
    const [items, setItems] = useState([
        { id: Date.now(), product_id: '', description: '', box_qty: 0, item_qty: 0, total_qty_loose: 0, unit_price: 0, tax_rate: 0, tax_amount: 0, total_price: 0 }
    ]);

    // Loading/Submitting
    const [loading, setLoading] = useState(false);
    const supplierSelectRef = useRef(null);

    // Initialize Select2 for Supplier
    useEffect(() => {
        if (supplierSelectRef.current && window.$) {
            const $select = window.$(supplierSelectRef.current);
            $select.select2({
                theme: 'bootstrap-5',
                placeholder: 'Select Supplier...',
                allowClear: true,
                width: '100%'
            });

            $select.on('select2:select', (e) => {
                const val = e.params.data.id;
                handleHeaderChange({ target: { name: 'supplier_id', value: val } });
            });

            $select.on('select2:clear', (e) => {
                handleHeaderChange({ target: { name: 'supplier_id', value: '' } });
            });

            return () => {
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }
                $select.off('select2:select');
                $select.off('select2:clear');
            };
        }
    }, [suppliers]);

    // Sync Select2 with State
    useEffect(() => {
        if (supplierSelectRef.current && window.$) {
            const $select = window.$(supplierSelectRef.current);
            if ($select.val() != header.supplier_id) {
                $select.val(header.supplier_id).trigger('change.select2');
            }
        }
    }, [header.supplier_id]);

    // Fetch Supplier Details on Selection
    useEffect(() => {
        if (!header.supplier_id) {
            setHeader(prev => ({ ...prev, billing_address: '' }));
            return;
        }

        const supplier = suppliers.find(c => c.id == header.supplier_id);
        if (supplier) {
            let addr = supplier.address_line_1;
            if (supplier.address_line_2) addr += ', ' + supplier.address_line_2;
            if (supplier.city) addr += ', ' + supplier.city;

            setHeader(prev => ({
                ...prev,
                billing_address: addr, // Supplier Address
                currency_id: supplier.currency_id || prev.currency_id,
            }));
        }
    }, [header.supplier_id]);

    // Calculations
    useEffect(() => {
        let total = 0;

        const newItems = items.map(item => {
            const bQty = parseFloat(item.box_qty) || 0;
            const iQty = parseFloat(item.item_qty) || 0;
            const price = parseFloat(item.unit_price) || 0;

            const prod = products.find(p => p.id == item.product_id);
            const packSize = prod?.pack_size_qty || 1;

            const totalQty = (bQty * packSize) + iQty;
            const lineTotal = totalQty * price;
            total += lineTotal;

            return { ...item, total_qty_loose: totalQty, total_price: lineTotal.toFixed(2) };
        });

        setHeader(prev => ({ ...prev, total_amount: total.toFixed(2) }));

    }, [items, products]);

    // Handlers
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
                newItems[index].unit_price = prod.price?.cost_price_item || 0;
                newItems[index].tax_rate = prod.price?.tax_rate || 0;
            }
        }

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
        if (!header.supplier_id) {
            alert('Please select a supplier.');
            return;
        }

        setLoading(true);
        try {
            const payload = { ...header, items, action };
            const res = await axios.post('/purchase-delivery-notes', payload);
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
                <h5 className="card-title mb-4">Inbound Delivery Note Details</h5>
                <div className="row g-3 mb-4">
                    <div className="col-md-3">
                        <label className="form-label">Supplier <span className="text-danger">*</span></label>
                        <select className="form-select" name="supplier_id" ref={supplierSelectRef}>
                            <option value="">Select Supplier...</option>
                            {suppliers.map(c => <option key={c.id} value={c.id}>{c.name} ({c.code})</option>)}
                        </select>
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Ref No <span className="text-danger">*</span></label>
                        <input type="text" className="form-control" name="do_number" value={header.do_number} readOnly />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Date Received <span className="text-danger">*</span></label>
                        <input type="date" className="form-control" name="delivery_date" value={header.delivery_date} onChange={handleHeaderChange} />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Supplier DO Ref #</label>
                        <input type="text" className="form-control" name="supplier_do_reference" value={header.supplier_do_reference} onChange={handleHeaderChange} placeholder="e.g. SUP-DO-123" />
                    </div>
                </div>

                <div className="row g-3 mb-4">
                    <div className="col-md-3">
                        <label className="form-label">Received By</label>
                        <select className="form-select" name="receiver_id" value={header.receiver_id} onChange={handleHeaderChange}>
                            <option value="">Select User...</option>
                            {receivers.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="col-md-4">
                        <label className="form-label">Our Warehouse Address</label>
                        <textarea className="form-control" rows="2" name="shipping_address" value={header.shipping_address} onChange={handleHeaderChange}></textarea>
                    </div>
                    <div className="col-md-5">
                        <label className="form-label">Supplier Address</label>
                        <textarea className="form-control" rows="2" name="billing_address" value={header.billing_address} onChange={handleHeaderChange} readOnly></textarea>
                    </div>
                </div>

                {/* Items Section */}
                <h5 className="card-title mb-3">Items Received</h5>
                <div className="table-responsive mb-4">
                    <table className="table table-bordered table-sm align-middle">
                        <thead className="table-light">
                            <tr>
                                <th style={{ width: '25%' }}>Product</th>
                                <th style={{ width: '8%' }}>Box Qty</th>
                                <th style={{ width: '8%' }}>Item Qty</th>
                                <th style={{ width: '8%' }}>Total Units</th>
                                <th style={{ width: '10%' }}>Cost Price</th>
                                <th style={{ width: '8%' }}>Tax %</th>
                                <th style={{ width: '10%' }}>Tax Amt</th>
                                <th style={{ width: '10%' }}>Total</th>
                                <th style={{ width: '5%' }}></th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((item, index) => {
                                const prod = products.find(p => p.id == item.product_id);
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
                                            {item.total_qty_loose}
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
                    <button className="btn btn-success" onClick={() => handleSubmit('submit')} disabled={loading}>Confirm Receipt (Add Stock)</button>
                </div>
            </div>
        </div>
    );
};

const rootEl = document.getElementById('purchase-delivery-note-form-root');
if (rootEl) {
    const props = {
        suppliers: JSON.parse(rootEl.dataset.suppliers),
        currencies: JSON.parse(rootEl.dataset.currencies),
        products: JSON.parse(rootEl.dataset.products),
        receivers: JSON.parse(rootEl.dataset.receivers),
        doNumber: rootEl.dataset.doNumber
    };
    createRoot(rootEl).render(<PurchaseDeliveryNoteForm {...props} />);
}

export default PurchaseDeliveryNoteForm;
