import React, { useState, useEffect, useRef, useMemo } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';

// Reusable Product Select Component (Select2)
const ProductSelect = ({ products, options, value, onChange, rowIndex, onTabFromProduct, selectRef, onReady }) => {
    const internalRef = useRef(null);
    const ref = selectRef || internalRef;

    useEffect(() => {
        if (!window.$) return;

        const $el = window.$(ref.current);

        $el.select2({
            theme: 'bootstrap-5',
            placeholder: 'Code',
            allowClear: true,
            width: '100%',
            data: options || products.map(p => ({
                id: p.id,
                text: `${p.sku} - ${p.name}${p.uom ? ' [' + p.uom.name + ']' : ''}`
            }))
        });

        // Pre-select value if exists
        if (value) {
            $el.val(value).trigger('change.select2');
        }

        // Signal that Select2 is ready
        if (onReady) {
            onReady(rowIndex);
        }

        // Auto-focus search input whenever the dropdown opens (Tab or click)
        $el.on('select2:open', () => {
            setTimeout(() => {
                const searchField = document.querySelector('.select2-container--open .select2-search__field');
                if (searchField) searchField.focus();
            }, 10);
        });

        $el.on('select2:select', (e) => {
            onChange(e.params.data.id);
            // After selecting product, move to next field
            if (onTabFromProduct) {
                setTimeout(() => onTabFromProduct(rowIndex), 50);
            }
        });

        $el.on('select2:clear', (e) => {
            onChange('');
        });

        // Block re-open briefly after close so Escape doesn't immediately reopen
        let reopenBlocked = false;
        $el.on('select2:close', () => {
            reopenBlocked = true;
            setTimeout(() => { reopenBlocked = false; }, 200);
            setTimeout(() => {
                if (onTabFromProduct && document.activeElement?.tagName !== 'INPUT') {
                    onTabFromProduct(rowIndex);
                }
            }, 10);
        });

        // Auto-open on Tab/focus using document-level focusin (bubbles, unlike focus).
        // Use a unique namespace per instance so cleanup is precise.
        const uid = 'product_select_' + rowIndex + '_' + Date.now();
        const s2 = $el.data('select2');

        // Suppress triggers that fire during page initialisation (first 400ms after mount).
        let listenForFocus = false;
        const focusEnableTimer = setTimeout(() => { listenForFocus = true; }, 400);

        if (s2 && s2.$container) {
            window.$(document).on('focusin.' + uid, function (e) {
                if (!listenForFocus || reopenBlocked) return;
                if (!s2.$container[0].contains(e.target)) return;
                if (s2.$container.hasClass('select2-container--open')) return;
                $el.select2('open');
            });
        }

        return () => {
            clearTimeout(focusEnableTimer);
            window.$(document).off('focusin.' + uid);
            if ($el.data('select2')) {
                $el.select2('destroy');
            }
            $el.off('select2:open select2:select select2:clear select2:close');
        };
    }, []);

    // Watch for external value changes
    useEffect(() => {
        if (!window.$) return;
        const $el = window.$(ref.current);
        if ($el.val() != value) {
            $el.val(value).trigger('change');
        }
    }, [value]);

    return (
        <select ref={ref} className="form-select form-select-sm"></select>
    );
};

const SalesInvoiceForm = ({ customers, currencies, products, salespeople, invNumber, editMode = false, invoiceId = null, initialData = null, invoiceType = 'gst' }) => {

    const isNonGst = invoiceType === 'non_gst';

    // Header State
    const [header, setHeader] = useState(() => {
        const base = {
            customer_id: '',
            salesperson_id: '',
            invoice_number: invNumber,
            invoice_date: new Date().toISOString().split('T')[0],
            due_date: new Date().toISOString().split('T')[0],
            currency_id: currencies.find(c => c.is_default == 1)?.id || currencies[0]?.id || '',
            exchange_rate: 1.0,
            billing_address: '',
            shipping_address: '',
            contact_person: '',
            reference_number: '',
            customer_outstanding_balance: 0,
            remarks: '',
            // Summary Fields
            gross_total: 0,
            discount_amount: 0,
            discount_type: 'percent', // Default to percent or fixed?
            discount_percent: 0,
            subtotal: 0,
            tax_amount: 0,
            round_off: 0,
            net_total: 0,
            manual_total_active: false,
            manual_tax_active: false,
            customer_gst_option: invoiceType === 'non_gst' ? 'N' : '',  // 'E' = exclusive, 'I' = inclusive, 'N' = no GST
            customer_tax_rate: 0      // tax percentage from customer's linked Tax record
        };
        if (editMode && initialData?.header) {
            return { ...base, ...initialData.header };
        }
        return base;
    });

    const defaultItem = {
        id: Date.now(),
        product_id: '',
        select_id: '',
        alias_id: null,
        product_code: '',
        product_name: '',
        uom_name: '',
        pack_size_name: '',
        description: '',
        stock_qty_pcs: 0,
        stock_qty_box: 0,
        box_qty: 0,
        item_qty: 0,
        total_qty: 0,
        unit_price: 0,
        cost_price: 0,
        default_price: 0,
        is_custom_price: false,
        price_modified: false,
        total_price: 0,
        tax_rate: 0,
        tax_amount: 0,
        discount_amount: 0
    };

    const [items, setItems] = useState(() => {
        if (editMode && initialData?.items?.length > 0) {
            return initialData.items.map(item => ({
                ...defaultItem,
                ...item,
                id: item.id || Date.now() + Math.random(),
                select_id: item.select_id || (item.alias_id ? `a${item.alias_id}` : String(item.product_id || ''))
            }));
        }
        return [defaultItem];
    });

    const [loading, setLoading] = useState(false);
    const [thermalPrintUrl, setThermalPrintUrl] = useState(null);

    // Build Select2 options including aliases (memoized — products prop is fixed)
    const productOptions = useMemo(() => {
        const opts = [];
        products.forEach(p => {
            opts.push({
                id: String(p.id),
                text: `${p.sku} - ${p.name}${p.uom ? ' [' + p.uom.name + ']' : ''}`
            });
            (p.aliases || []).forEach(alias => {
                opts.push({
                    id: `a${alias.id}`,
                    text: `${alias.alias_sku || p.sku} - ${alias.alias_name}${p.uom ? ' [' + p.uom.name + ']' : ''}`
                });
            });
        });
        return opts;
    }, [products]);

    useEffect(() => {
        document.body.style.overflow = thermalPrintUrl ? 'hidden' : '';
        return () => { document.body.style.overflow = ''; };
    }, [thermalPrintUrl]);

    useEffect(() => {
        const onKey = (e) => { if (e.key === 'Escape') setThermalPrintUrl(null); };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, []);

    // References
    const customerSelectRef = useRef(null);
    const itemInputRefs = useRef({});
    const productSelectRefs = useRef({});
    const [pendingFocusRow, setPendingFocusRow] = useState(null);
    const readySelectRefs = useRef(new Set());

    // Ref to track current customer_id (avoids stale closure issues)
    const customerIdRef = useRef(header.customer_id);
    // Track whether the customer_id effect is firing for the first time (initial load)
    const isInitialCustomerLoad = useRef(true);

    // --- Select2 Helper Methods ---
    const openAndFocusSelect2 = (ref) => {
        if (ref?.current && window.$) {
            window.$(ref.current).select2('open');
            // search input focus is handled by the select2:open event in ProductSelect
        }
    };

    const handleProductSelectReady = (rowIndex) => {
        readySelectRefs.current.add(rowIndex);
        if (pendingFocusRow === rowIndex) {
            setTimeout(() => {
                openAndFocusSelect2(productSelectRefs.current[rowIndex]);
                setPendingFocusRow(null);
            }, 10);
        }
    };

    const getProductSelectRef = (rowIndex) => {
        if (!productSelectRefs.current[rowIndex]) {
            productSelectRefs.current[rowIndex] = React.createRef();
        }
        return productSelectRefs.current[rowIndex];
    };

    const getInputRef = (rowIndex, field) => {
        const key = `${rowIndex}-${field}`;
        if (!itemInputRefs.current[key]) {
            itemInputRefs.current[key] = React.createRef();
        }
        return itemInputRefs.current[key];
    };

    const focusField = (rowIndex, field) => {
        if (field === 'product') {
            const ref = productSelectRefs.current[rowIndex];
            if (ref?.current && window.$) window.$(ref.current).select2('open');
        } else {
            const ref = itemInputRefs.current[`${rowIndex}-${field}`];
            if (ref?.current) {
                ref.current.focus();
                ref.current.select();
            }
        }
    };

    // Navigation Order
    const FIELD_ORDER = ['item_qty', 'unit_price'];

    const handleTabFromProduct = (rowIndex) => {
        focusField(rowIndex, 'item_qty');
    };

    const handleItemKeyDown = (e, rowIndex, field) => {
        if (e.key === 'Tab' && !e.shiftKey) {
            const currentIdx = FIELD_ORDER.indexOf(field);
            if (currentIdx === FIELD_ORDER.length - 1) {
                // Last field
                e.preventDefault();
                if (rowIndex === items.length - 1) {
                    addItem();
                    setPendingFocusRow(items.length);
                } else {
                    focusField(rowIndex + 1, 'product');
                }
            }
        } else if (e.key === 'Enter') {
            if (rowIndex === items.length - 1) {
                e.preventDefault();
                addItem();
                setPendingFocusRow(items.length);
            }
        }
    };

    // --- Logic Methods ---

    // Keep customerIdRef in sync with header.customer_id
    useEffect(() => {
        customerIdRef.current = header.customer_id;
    }, [header.customer_id]);

    // Initialize Customer Select2
    useEffect(() => {
        if (customerSelectRef.current && window.$) {
            const $select = window.$(customerSelectRef.current);
            $select.select2({ theme: 'bootstrap-5', placeholder: 'Select Customer...', allowClear: true, width: '100%' });
            $select.on('select2:select', (e) => handleHeaderChange({ target: { name: 'customer_id', value: e.params.data.id } }));
            $select.on('select2:clear', () => handleHeaderChange({ target: { name: 'customer_id', value: '' } }));
            if (header.customer_id) $select.val(header.customer_id).trigger('change.select2');
        }
    }, [customers]); // Add customers dependency

    // Fetch Customer Details & Re-price Items
    useEffect(() => {
        if (!header.customer_id) return;

        const customer = customers.find(c => c.id == header.customer_id);
        if (customer) {
            // For non-GST invoices, always force No GST regardless of customer settings
            const gstOption = isNonGst ? 'N' : (customer.gst_option || 'N');
            const taxRate = gstOption === 'E' ? (parseFloat(customer.tax?.percentage) || 0) : 0;

            let updates = {
                customer_gst_option: gstOption,
                customer_tax_rate: taxRate,
            };

            // Reset manual tax override when customer changes away from GST Exclusive
            if (gstOption !== 'E') {
                updates.manual_tax_active = false;
            }

            const customerAddr = [customer.address_line_1, customer.address_line_2, customer.city, customer.postal_code].filter(Boolean).join(', ');

            if (!editMode) {
                // Billing Address
                if (!header.billing_address) {
                    updates.billing_address = customerAddr;
                    updates.shipping_address = customerAddr;
                }

                // Currency
                if (customer.currency_id) {
                    updates.currency_id = customer.currency_id;
                }

                // Contact Person
                if (customer.primary_contact_name || customer.contact_person) {
                    updates.contact_person = customer.primary_contact_name || customer.contact_person;
                }
            } else if (!isInitialCustomerLoad.current) {
                // Edit mode: update billing/shipping address only when customer is changed by user (not on initial load)
                updates.billing_address = customerAddr;
                updates.shipping_address = customerAddr;
            }

            isInitialCustomerLoad.current = false;
            setHeader(prev => ({ ...prev, ...updates }));
        }

        // Fetch Outstanding Balance
        if (header.customer_id) {
            axios.get(`/api/sales-invoices/customer-last-credit/${header.customer_id}`)
                .then(res => {
                    if (res.data.success) {
                        setHeader(prev => ({ ...prev, customer_outstanding_balance: res.data.balance || 0 }));
                    } else {
                        setHeader(prev => ({ ...prev, customer_outstanding_balance: 0 }));
                    }
                })
                .catch(err => console.error('Error fetching balance:', err));
        } else {
            setHeader(prev => ({ ...prev, customer_outstanding_balance: 0 }));
        }

        // Re-fetch prices for all existing items for this customer
        items.forEach((item, index) => {
            if (item.product_id) {
                fetchProductDetails(item.product_id, index, item.alias_id);
            }
        });

    }, [header.customer_id]);

    const fetchProductDetails = (productId, index, aliasId = null) => {
        // Use ref to get current customer_id (avoids stale closure)
        const customerId = customerIdRef.current;
        const params = [];
        if (customerId) params.push(`associate_id=${customerId}`);
        if (aliasId) params.push(`alias_id=${aliasId}`);
        const url = `/api/sales-invoices/product-stock/${productId}${params.length ? '?' + params.join('&') : ''}`;
        axios.get(url)
            .then(res => {
                if (res.data.success) {
                    const p = res.data.product;
                    setItems(prev => {
                        const newItems = [...prev];
                        // prevent overwrite if row was deleted
                        if (!newItems[index]) return prev;

                        newItems[index] = {
                            ...newItems[index],
                            stock_qty_pcs: p.stock_qty_pcs || 0,
                            stock_qty_box: p.stock_qty_box || 0,
                            tax_rate: p.tax_rate || 0,
                            cost_price: p.cost_price || 0,
                            // Customer-specific pricing: API returns correct price in 'sales_price_item'
                            unit_price: p.sales_price_item || p.sales_price || 0,
                            default_price: p.default_price || p.sales_price_item || 0,
                            is_custom_price: p.is_custom_price || false,
                            product_name: p.name,
                            product_code: p.sku,
                            uom_name: p.uom_name || '',
                            pack_size_name: p.pack_size_name || '',
                            // When alias is used, keep description as alias name
                            ...(p.alias_name ? { description: p.alias_name } : {})
                        };
                        return calculateLineTotals(newItems);
                    });
                }
            })
            .catch(err => console.error(err));
    };

    const handleItemChange = (index, field, value) => {
        const newItems = [...items];
        newItems[index][field] = value;

        if (field === 'select_id') {
            newItems[index].select_id = value;
            if (value) {
                const strVal = String(value);
                if (strVal.startsWith('a')) {
                    // Alias selected
                    const aliasId = parseInt(strVal.substring(1));
                    let foundAlias = null, parentProd = null;
                    for (const p of products) {
                        const a = (p.aliases || []).find(al => al.id === aliasId);
                        if (a) { foundAlias = a; parentProd = p; break; }
                    }
                    if (foundAlias && parentProd) {
                        newItems[index].product_id    = parentProd.id;
                        newItems[index].alias_id      = aliasId;
                        newItems[index].description   = foundAlias.alias_name;
                        newItems[index].product_name  = foundAlias.alias_name;
                        newItems[index].product_code  = foundAlias.alias_sku || parentProd.sku;
                        newItems[index].uom_name      = parentProd.uom?.name || '';
                        newItems[index].pack_size_name = parentProd.pack_size?.name || '';
                        fetchProductDetails(parentProd.id, index, aliasId);
                    }
                } else {
                    // Regular product selected
                    const p = products.find(prod => prod.id == value);
                    if (p) {
                        newItems[index].product_id    = p.id;
                        newItems[index].alias_id      = null;
                        newItems[index].description   = '';
                        newItems[index].product_code  = p.sku;
                        newItems[index].product_name  = p.name;
                        newItems[index].uom_name      = p.uom?.name || '';
                        newItems[index].pack_size_name = p.pack_size?.name || '';
                    }
                    fetchProductDetails(value, index, null);
                }
            } else {
                // Cleared
                newItems[index].product_id  = '';
                newItems[index].alias_id    = null;
                newItems[index].description = '';
            }
        }

        // Track when price is manually changed
        if (field === 'unit_price') {
            const currentItem = newItems[index];
            const inputPrice = parseFloat(value) || 0;
            const expectedPrice = currentItem.is_custom_price ? currentItem.unit_price : currentItem.default_price;
            // Mark as modified if different from what was loaded
            newItems[index].price_modified = Math.abs(inputPrice - expectedPrice) > 0.001;
        }

        setItems(calculateLineTotals(newItems));
    };

    const calculateLineTotals = (currentItems) => {
        return currentItems.map(item => {
            if (!item.product_id) return item;

            const packSize = products.find(p => p.id == item.product_id)?.pack_size_qty || 1;
            const lineQty = (parseFloat(item.box_qty) || 0) * packSize + (parseFloat(item.item_qty) || 0);

            // Row Calculation
            // Total = (Qty * Unit Price) - Row Discount
            // Wait, usually Discount is applied per unit or total?
            // "Total = (Qty × Unit Price)"
            const grossLine = lineQty * (parseFloat(item.unit_price) || 0);
            // Row discount removed
            const totalLine = Math.max(0, grossLine);

            // Tax (just for info, or used in net?)
            // If tax is exclusive:
            const taxAmt = totalLine * ((parseFloat(item.tax_rate) || 0) / 100);

            return {
                ...item,
                total_qty: lineQty,
                total_price: totalLine.toFixed(2),
                tax_amount: taxAmt.toFixed(2)
            };
        });
    };

    // Calculate Footer Totals
    // Formula: Net Total = Subtotal - Discount + Tax
    useEffect(() => {
        // 1. Subtotal = Sum of line items
        const sumLines = items.reduce((acc, i) => acc + (parseFloat(i.total_price) || 0), 0);
        const subtotal = header.manual_total_active ? parseFloat(header.subtotal) || 0 : sumLines;

        // 2. Bill Discount
        let discAmt = parseFloat(header.discount_amount) || 0;
        if (header.discount_type === 'percent') {
            discAmt = subtotal * ((parseFloat(header.discount_percent) || 0) / 100);
        }

        // 3. Tax — only calculate if customer is GST Exclusive ('E')
        const isGstExclusive = header.customer_gst_option === 'E';
        const customerTaxRate = parseFloat(header.customer_tax_rate) || 0;
        // GST = (subtotal - discount) × customer rate%; falls back to per-item sum if no rate linked
        const taxableBase = Math.max(0, subtotal - discAmt);
        const autoTax = isGstExclusive
            ? (customerTaxRate > 0 ? taxableBase * customerTaxRate / 100
                : items.reduce((acc, i) => acc + (parseFloat(i.tax_amount) || 0), 0))
            : 0;
        const taxVal = !isGstExclusive ? 0
            : (header.manual_tax_active ? parseFloat(header.tax_amount) || 0 : autoTax);

        // 4. Net Total = Subtotal - Discount + Tax
        const net = Math.max(0, subtotal - discAmt + taxVal);

        // Only update if changes to avoid loop
        if (
            (Math.abs(subtotal - header.subtotal) > 0.01 && !header.manual_total_active) ||
            (!isGstExclusive && parseFloat(header.tax_amount) > 0) ||
            (isGstExclusive && Math.abs(taxVal - header.tax_amount) > 0.01 && !header.manual_tax_active) ||
            Math.abs(net - header.net_total) > 0.01
        ) {
            setHeader(prev => ({
                ...prev,
                gross_total: subtotal.toFixed(2),
                subtotal: header.manual_total_active ? prev.subtotal : subtotal.toFixed(2),
                discount_amount: discAmt.toFixed(2),
                tax_amount: !isGstExclusive ? '0.00'
                    : (header.manual_tax_active ? prev.tax_amount : taxVal.toFixed(2)),
                manual_tax_active: !isGstExclusive ? false : prev.manual_tax_active,
                round_off: 0,
                net_total: net.toFixed(2)
            }));
        }
    }, [items, header.manual_total_active, header.subtotal, header.manual_tax_active, header.tax_amount, header.discount_amount, header.discount_percent, header.customer_gst_option, header.customer_tax_rate]);

    const handleHeaderChange = (e) => {
        const { name, value } = e.target;
        setHeader(prev => {
            const updates = { [name]: value };
            if (name === 'subtotal') {
                updates.manual_total_active = true;
            }
            if (name === 'tax_amount') {
                updates.manual_tax_active = true;
            }
            if (name === 'discount_percent') {
                const subtotal = parseFloat(prev.subtotal) || 0;
                updates.discount_amount = (subtotal * (parseFloat(value) / 100)).toFixed(2);
                updates.discount_type = 'percent';
            }
            if (name === 'discount_amount') {
                updates.discount_type = 'fixed';
                const subtotal = parseFloat(prev.subtotal) || 1;
                updates.discount_percent = ((parseFloat(value) / subtotal) * 100).toFixed(2);
            }
            return { ...prev, ...updates };
        });
    };

    const addItem = () => setItems([...items, { ...defaultItem, id: Date.now() }]);
    const removeItem = (idx) => {
        if (items.length === 1) {
            setItems([{ ...defaultItem, id: Date.now() }]);
        } else {
            setItems(items.filter((_, i) => i !== idx));
        }
    };

    const handleSubmit = async (action) => {
        if (!header.customer_id) return alert('Select a customer');
        if (items.length === 0 || !items[0].product_id) return alert('Add at least one product');

        const overStockItems = items.filter(item =>
            item.product_id && parseFloat(item.item_qty) > parseFloat(item.stock_qty_pcs)
        );
        if (overStockItems.length > 0) {
            return alert(`Insufficient stock for: ${overStockItems.map(i => i.product_name || i.product_code).join(', ')}`);
        }

        setLoading(true);
        const payload = { ...header, items, action, invoice_type: invoiceType };

        try {
            const url = editMode && invoiceId ? `/sales-invoices/${invoiceId}` : '/sales-invoices';
            const method = editMode && invoiceId ? 'put' : 'post';
            const res = await axios[method](url, payload);

            if (res.data.success) {
                // Correctly use the ID returned from backend
                const newId = res.data.id || invoiceId;

                // Handling Action Redirection
                if (action === 'save_pay') {
                    window.location.href = `/sales-invoices/${newId}/pay`;
                } else if (action === 'save_print') {
                    setThermalPrintUrl(`/sales-invoices/${newId}/thermal-print`);
                } else {
                    window.location.href = res.data.redirect;
                }
            }
        } catch (error) {
            alert('Error: ' + (error.response?.data?.error || error.message));
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="card">
            <div className="card-body">
                {/* Header Section */}
                <div className="row g-3 mb-4">
                    <div className="col-md-3">
                        <label className="form-label">Customer <span className="text-danger">*</span></label>
                        <select className="form-select" ref={customerSelectRef}>
                            <option value="">Select Associate...</option>
                            {customers.map(c => <option key={c.id} value={c.id}>{c.name} - {c.company_name}</option>)}
                        </select>
                    </div>
                    <div className="col-md-2">
                        <label className="form-label">Currency</label>
                        <select className="form-select" name="currency_id" value={header.currency_id} onChange={handleHeaderChange}>
                            {currencies.map(c => <option key={c.id} value={c.id}>{c.code} - {c.name}</option>)}
                        </select>
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Billing Address</label>
                        <textarea className="form-control form-control-sm" rows="1" name="billing_address" value={header.billing_address} onChange={handleHeaderChange} placeholder="Billing Address"></textarea>
                    </div>
                    <div className="col-md-3">
                        <label className="form-label">Delivery Address</label>
                        <textarea className="form-control form-control-sm" rows="1" name="shipping_address" value={header.shipping_address} onChange={handleHeaderChange} placeholder="Delivery Address"></textarea>
                    </div>
                </div>

                <div className="row g-3 mb-4">
                    <div className="col-md-3">
                        <label className="form-label">Contact Person</label>
                        <input type="text" className="form-control" name="contact_person" value={header.contact_person} onChange={handleHeaderChange} placeholder="Contact Person" />
                    </div>
                    <div className="col-md-4">
                        <label className="form-label">Remarks</label>
                        <input type="text" className="form-control" name="remarks" value={header.remarks} onChange={handleHeaderChange} placeholder="Remarks / Notes" />
                    </div>
                    <div className="col-md-2">
                        <label className="form-label fw-bold text-danger">Outstanding Balance</label>
                        <input type="text" className="form-control fw-bold bg-light text-danger" value={header.customer_outstanding_balance} readOnly />
                    </div>
                    <div className="col-md-3">
                        <div className="row">
                            <div className="col-6">
                                <label className="form-label">Invoice Date</label>
                                <input type="date" className="form-control" name="invoice_date" value={header.invoice_date} onChange={handleHeaderChange} />
                            </div>
                            <div className="col-6">
                                <label className="form-label">Ref No.</label>
                                <input type="text" className="form-control" name="reference_number" value={header.reference_number} onChange={handleHeaderChange} />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Product Table */}
                <div className="table-responsive mb-4">
                    <table className="table table-bordered table-sm align-middle">
                        <thead className="table-light">
                            <tr>
                                <th style={{ width: '3%' }} className="text-center">#</th>
                                <th style={{ width: '15%' }}>Product Code</th>
                                <th style={{ width: '20%' }}>Product Name</th>
                                <th style={{ width: '8%' }} className="text-center bg-light">Stock Pcs</th>
                                {/* <th style={{ width: '8%' }} className="bg-light">Stock Box</th> */}
                                {/* <th style={{ width: '8%' }}>Box Qty</th> */}
                                <th style={{ width: '8%' }}>Pcs Qty</th>
                                <th style={{ width: '8%' }}>Cost Price</th>
                                <th style={{ width: '10%' }}>Unit Price</th>
                                <th style={{ width: '10%' }}>Total</th>
                                <th style={{ width: '5%' }}></th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((item, index) => (
                                <tr key={item.id}>
                                    <td className="text-center">{index + 1}</td>
                                    <td>
                                        <ProductSelect
                                            products={products}
                                            options={productOptions}
                                            value={item.select_id}
                                            onChange={(val) => handleItemChange(index, 'select_id', val)}
                                            rowIndex={index}
                                            onTabFromProduct={handleTabFromProduct}
                                            selectRef={getProductSelectRef(index)}
                                            onReady={handleProductSelectReady}
                                        />
                                    </td>
                                    <td>
                                        <input type="text" className="form-control form-control-sm bg-light" readOnly value={item.product_name} tabIndex={-1} />
                                        {(item.uom_name || item.pack_size_name) && (
                                            <div className="mt-1 d-flex gap-1 flex-wrap">
                                                {item.uom_name && <span className="badge bg-secondary bg-opacity-10 text-secondary border" style={{fontSize:'0.65rem'}}>{item.uom_name}</span>}
                                                {item.pack_size_name && <span className="badge bg-secondary bg-opacity-10 text-secondary border" style={{fontSize:'0.65rem'}}>{item.pack_size_name}</span>}
                                            </div>
                                        )}
                                    </td>
                                    <td className="text-center bg-light">{parseFloat(item.stock_qty_pcs || 0).toFixed(0)}</td>
                                    {/* <td className="text-center bg-light">{parseFloat(item.stock_qty_box || 0).toFixed(0)}</td> */}
                                    {/* <td>
                                        <input
                                            ref={getInputRef(index, 'box_qty')}
                                            type="number" className="form-control form-control-sm" placeholder="Box"
                                            value={item.box_qty} onChange={e => handleItemChange(index, 'box_qty', e.target.value)}
                                            onKeyDown={e => handleItemKeyDown(e, index, 'box_qty')}
                                        />
                                    </td> */}
                                    <td>
                                        {(() => {
                                            const isOverStock = item.product_id && parseFloat(item.item_qty) > parseFloat(item.stock_qty_pcs);
                                            return (
                                                <>
                                                    <input
                                                        ref={getInputRef(index, 'item_qty')}
                                                        type="number"
                                                        className={`form-control form-control-sm${isOverStock ? ' border-danger text-danger' : ''}`}
                                                        placeholder="Pcs"
                                                        value={item.item_qty}
                                                        onChange={e => handleItemChange(index, 'item_qty', e.target.value)}
                                                        onKeyDown={e => handleItemKeyDown(e, index, 'item_qty')}
                                                    />
                                                    {isOverStock && (
                                                        <div className="text-danger" style={{ fontSize: '0.68rem', marginTop: '2px' }}>
                                                            Max: {parseFloat(item.stock_qty_pcs).toFixed(0)}
                                                        </div>
                                                    )}
                                                </>
                                            );
                                        })()}
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            className="form-control form-control-sm bg-light text-end"
                                            value={item.cost_price || 0}
                                            readOnly
                                            title="Cost Price"
                                        />
                                    </td>
                                    <td>
                                        <div className="position-relative">
                                            <input
                                                ref={getInputRef(index, 'unit_price')}
                                                type="number"
                                                className={`form-control form-control-sm text-end ${item.is_custom_price ? 'border-success bg-success bg-opacity-10' : ''}`}
                                                value={item.unit_price}
                                                onChange={e => handleItemChange(index, 'unit_price', e.target.value)}
                                                onKeyDown={e => handleItemKeyDown(e, index, 'unit_price')}
                                                title={item.is_custom_price ? `Custom price for this customer (Default: ${item.default_price})` : 'Default price'}
                                            />
                                            {item.is_custom_price && (
                                                <span className="position-absolute top-0 end-0 translate-middle badge rounded-pill bg-success" style={{ fontSize: '0.6rem', marginTop: '2px', marginRight: '-5px' }}>
                                                    C
                                                </span>
                                            )}
                                        </div>
                                    </td>
                                    <td className="text-end fw-bold">{item.total_price}</td>
                                    <td className="text-center">
                                        <button className="btn btn-sm text-danger" onClick={() => removeItem(index)} tabIndex={-1}><i className="ti ti-trash"></i></button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Summary Section */}
                {/* Formula: Net Total = Subtotal - Discount + Tax */}
                <div className="row justify-content-end">
                    <div className="col-md-4">
                        <table className="table table-sm table-borderless">
                            <tbody>
                                <tr>
                                    <td className="fw-bold">Subtotal</td>
                                    <td>
                                        <input type="number" className="form-control form-control-sm text-end"
                                            name="subtotal" value={header.subtotal} onChange={handleHeaderChange} />
                                    </td>
                                </tr>
                                <tr>
                                    <td>Discount</td>
                                    <td className="d-flex">
                                        <input type="number" className="form-control form-control-sm me-2" placeholder="Amt"
                                            name="discount_amount" value={header.discount_amount} onChange={handleHeaderChange} />
                                        <input type="number" className="form-control form-control-sm" placeholder="%" style={{ width: '70px' }}
                                            name="discount_percent" value={header.discount_percent} onChange={handleHeaderChange} />
                                        <span className="ms-1 align-self-center">%</span>
                                    </td>
                                </tr>
                                {!isNonGst && (
                                <tr>
                                    <td>
                                        Tax
                                        {header.customer_gst_option === 'E' && (
                                            <small className="text-success ms-1">
                                                {header.customer_tax_rate > 0 ? `(${header.customer_tax_rate}% Excl.)` : '(Excl.)'}
                                            </small>
                                        )}
                                        {header.customer_gst_option === 'I' && <small className="text-muted ms-1">(Incl.)</small>}
                                        {header.customer_gst_option === 'N' && <small className="text-muted ms-1">(No GST)</small>}
                                    </td>
                                    <td>
                                        <input type="number"
                                            className={`form-control form-control-sm text-end ${header.customer_gst_option !== 'E' ? 'bg-light' : ''}`}
                                            name="tax_amount"
                                            value={header.tax_amount}
                                            onChange={handleHeaderChange}
                                            readOnly={header.customer_gst_option !== 'E'}
                                        />
                                    </td>
                                </tr>
                                )}
                                <tr className="border-top border-dark">
                                    <td className="fw-bold h5">Net Total</td>
                                    <td className="text-end fw-bold h5">{header.net_total}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Footer Actions */}
                <div className="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <button className="btn btn-light" onClick={() => window.history.back()}>Close</button>
                    <button className="btn btn-primary" onClick={() => handleSubmit('save_pay')} disabled={loading}>
                        <i className="ti ti-cash me-1"></i> Save & Pay
                    </button>
<button className="btn btn-success" onClick={() => handleSubmit('submit')} disabled={loading}>
                        <i className="ti ti-check me-1"></i> Submit
                    </button>
                </div>
            </div>

            {/* Thermal Print Overlay */}
            {thermalPrintUrl && (
                <div style={{ position: 'fixed', inset: 0, zIndex: 9999, background: '#111827' }}>
                    <button
                        onClick={() => setThermalPrintUrl(null)}
                        title="Close"
                        style={{
                            position: 'absolute', top: 12, right: 16, zIndex: 10000,
                            background: '#374151', border: '1px solid #6b7280', color: '#f3f4f6',
                            fontSize: 18, lineHeight: 1, padding: '4px 10px',
                            borderRadius: 4, cursor: 'pointer'
                        }}
                    >&times;</button>
                    <iframe
                        src={thermalPrintUrl}
                        style={{ width: '100%', height: '100%', border: 'none', display: 'block' }}
                    />
                </div>
            )}
        </div>
    );
};

if (document.getElementById('sales-invoice-form-root')) {
    const root = createRoot(document.getElementById('sales-invoice-form-root'));
    const props = { ...document.getElementById('sales-invoice-form-root').dataset };
    Object.keys(props).forEach(key => {
        try { props[key] = JSON.parse(props[key]); } catch (e) { }
    });
    root.render(<SalesInvoiceForm {...props} />);
}

export default SalesInvoiceForm;
