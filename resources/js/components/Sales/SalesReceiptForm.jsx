import React, { useState, useEffect, useRef, useCallback } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';

const SalesReceiptForm = ({ customers, currencies, banks, bankAccounts, paymodes, rcpNumber }) => {
    // Header State
    const [header, setHeader] = useState({
        customer_id: '',
        receipt_number: rcpNumber,
        receipt_date: new Date().toISOString().split('T')[0],
        currency_id: currencies.find(c => c.is_default == 1)?.id || currencies[0]?.id || '',
        bank_id: '',
        bank_account_id: '',
        paymode_id: '',
        payment_mode: '',
        pay_to: '',
        reference: '',
        cheque_date: '',
        cheque_no: '',
        remarks: '',
    });

    // Invoice & Allocation State
    const [unpaidInvoices, setUnpaidInvoices] = useState([]);
    const [selectedInvoices, setSelectedInvoices] = useState({}); // {invoice_id: {selected: bool, received_amount: number}}

    // Calculation State
    const [customerPaidAmount, setCustomerPaidAmount] = useState(0);
    const [payableAmount, setPayableAmount] = useState(0);
    const [excessAmount, setExcessAmount] = useState(0);
    const [writeOffAmount, setWriteOffAmount] = useState(0);

    // UI State
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [filteredBankAccounts, setFilteredBankAccounts] = useState([]);

    // Refs
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
                handleHeaderChange({ target: { name: 'customer_id', value: e.params.data.id } });
            });

            $select.on('select2:clear', () => {
                handleHeaderChange({ target: { name: 'customer_id', value: '' } });
            });

            return () => {
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }
                $select.off('select2:select select2:clear');
            };
        }
    }, [customers]);

    // Sync Select2 with state
    useEffect(() => {
        if (customerSelectRef.current && window.$) {
            const $select = window.$(customerSelectRef.current);
            if ($select.val() != header.customer_id) {
                $select.val(header.customer_id).trigger('change.select2');
            }
        }
    }, [header.customer_id]);

    // Filter bank accounts when bank changes
    useEffect(() => {
        if (header.bank_id) {
            const filtered = bankAccounts.filter(acc => acc.bank_id == header.bank_id);
            setFilteredBankAccounts(filtered);
            // Reset account selection if not in filtered list
            if (!filtered.find(acc => acc.id == header.bank_account_id)) {
                setHeader(prev => ({ ...prev, bank_account_id: '' }));
            }
        } else {
            setFilteredBankAccounts(bankAccounts);
        }
    }, [header.bank_id, bankAccounts]);

    // Fetch unpaid invoices when customer changes (from both old and new systems)
    useEffect(() => {
        if (!header.customer_id) {
            setUnpaidInvoices([]);
            setSelectedInvoices({});
            return;
        }

        setLoading(true);
        axios.get(`/api/sales-receipts/pending-invoices/${header.customer_id}`)
            .then(res => {
                setUnpaidInvoices(res.data || []);
                // Initialize selection state for all invoices
                // Use composite key: source_id to handle both old and new invoices
                const initialSelection = {};
                (res.data || []).forEach(inv => {
                    const key = `${inv.source}_${inv.id}`;
                    initialSelection[key] = {
                        selected: false,
                        received_amount: 0,
                        balance_due: parseFloat(inv.balance_due),
                        source: inv.source,
                        invoice_id: inv.id
                    };
                });
                setSelectedInvoices(initialSelection);
            })
            .catch(console.error)
            .finally(() => setLoading(false));
    }, [header.customer_id]);

    // Calculate payable and excess amounts
    useEffect(() => {
        let totalPayable = 0;
        Object.values(selectedInvoices).forEach(inv => {
            if (inv.selected) {
                totalPayable += parseFloat(inv.received_amount) || 0;
            }
        });
        setPayableAmount(totalPayable);

        const paid = parseFloat(customerPaidAmount) || 0;
        const excess = paid - totalPayable;
        setExcessAmount(Math.max(0, excess));
    }, [selectedInvoices, customerPaidAmount]);

    // Handle header field changes
    const handleHeaderChange = (e) => {
        const { name, value } = e.target;
        setHeader(prev => ({ ...prev, [name]: value }));
    };

    // Handle paymode change - set payment_mode text
    const handlePaymodeChange = (e) => {
        const paymodeId = e.target.value;
        const selectedPaymode = paymodes.find(p => p.id == paymodeId);
        setHeader(prev => ({
            ...prev,
            paymode_id: paymodeId,
            payment_mode: selectedPaymode?.name || ''
        }));
    };

    // Get composite key for invoice (source_id)
    const getInvoiceKey = (inv) => `${inv.source}_${inv.id}`;

    // Toggle invoice selection
    const toggleInvoiceSelection = (inv) => {
        const key = getInvoiceKey(inv);
        setSelectedInvoices(prev => {
            const current = prev[key];
            const isNowSelected = !current.selected;
            return {
                ...prev,
                [key]: {
                    ...current,
                    selected: isNowSelected,
                    // Auto-fill with balance when selected
                    received_amount: isNowSelected ? current.balance_due : 0
                }
            };
        });
    };

    // Handle received amount change
    const handleReceivedAmountChange = (inv, value) => {
        const key = getInvoiceKey(inv);
        const numValue = parseFloat(value) || 0;
        const balance = selectedInvoices[key]?.balance_due || 0;

        // Prevent received > balance
        const cappedValue = Math.min(numValue, balance);

        setSelectedInvoices(prev => ({
            ...prev,
            [key]: {
                ...prev[key],
                received_amount: cappedValue
            }
        }));
    };

    // Select all invoices
    const selectAllInvoices = () => {
        const allSelected = Object.values(selectedInvoices).every(inv => inv.selected);
        setSelectedInvoices(prev => {
            const updated = {};
            Object.entries(prev).forEach(([key, inv]) => {
                updated[key] = {
                    ...inv,
                    selected: !allSelected,
                    received_amount: !allSelected ? inv.balance_due : 0
                };
            });
            return updated;
        });
    };

    // Split button logic - distribute customer paid amount across selected invoices
    const handleSplit = () => {
        const paid = parseFloat(customerPaidAmount) || 0;
        if (paid <= 0) {
            alert('Please enter Customer Paid Amount first.');
            return;
        }

        // Get selected invoice keys
        const selectedKeys = Object.entries(selectedInvoices)
            .filter(([_, inv]) => inv.selected)
            .map(([key]) => key);

        if (selectedKeys.length === 0) {
            alert('Please select at least one invoice to split payment.');
            return;
        }

        // Sort by invoice date (oldest first - FIFO)
        const sortedInvoices = unpaidInvoices
            .filter(inv => selectedKeys.includes(getInvoiceKey(inv)))
            .sort((a, b) => new Date(a.invoice_date) - new Date(b.invoice_date));

        let remaining = paid;
        const newSelection = { ...selectedInvoices };

        // First, reset all selected invoices to 0
        sortedInvoices.forEach(inv => {
            const key = getInvoiceKey(inv);
            newSelection[key] = {
                ...newSelection[key],
                received_amount: 0
            };
        });

        // Distribute payment FIFO style
        sortedInvoices.forEach(inv => {
            if (remaining <= 0) return;

            const key = getInvoiceKey(inv);
            const balance = parseFloat(inv.balance_due);
            const allocation = Math.min(remaining, balance);

            newSelection[key] = {
                ...newSelection[key],
                received_amount: allocation
            };

            remaining -= allocation;
        });

        setSelectedInvoices(newSelection);
    };

    // Submit handler
    const handleSubmit = async () => {
        // Validation
        if (!header.customer_id) {
            alert('Please select a customer.');
            return;
        }

        const paid = parseFloat(customerPaidAmount) || 0;
        if (paid <= 0) {
            alert('Please enter a valid Customer Paid Amount.');
            return;
        }

        // Validate cheque fields if payment mode is Cheque
        if (header.payment_mode === 'Cheque') {
            if (!header.cheque_no) {
                alert('Please enter Cheque Number.');
                return;
            }
            if (!header.cheque_date) {
                alert('Please enter Cheque Date.');
                return;
            }
        }

        // Build allocations array with source identifier
        const allocations = Object.entries(selectedInvoices)
            .filter(([_, inv]) => inv.selected && inv.received_amount > 0)
            .map(([key, inv]) => ({
                invoice_id: inv.invoice_id,
                source: inv.source, // 'old' for invoice_references, 'new' for sales_invoices
                amount: inv.received_amount
            }));

        setSubmitting(true);
        try {
            const payload = {
                ...header,
                customer_paid_amount: customerPaidAmount,
                write_off_amount: writeOffAmount,
                allocations: allocations,
            };

            const res = await axios.post('/sales-receipts', payload);
            if (res.data.success) {
                alert('Success: ' + res.data.message);
                window.location.href = res.data.redirect;
            }
        } catch (error) {
            console.error(error);
            alert('Error: ' + (error.response?.data?.error || error.message));
        } finally {
            setSubmitting(false);
        }
    };

    // Check if cheque fields should show
    const showChequeFields = header.payment_mode === 'Cheque';

    // Format number
    const formatNumber = (num) => parseFloat(num || 0).toFixed(2);

    return (
        <div className="card">
            <div className="card-header bg-primary text-white">
                <h5 className="card-title mb-0">
                    <i className="ti ti-receipt me-2"></i>
                    New Payment Receipt
                </h5>
            </div>
            <div className="card-body">
                {/* Section 1: Receipt Header - Payment Details */}
                <div className="border rounded p-3 mb-4 bg-light">
                    <h6 className="border-bottom pb-2 mb-3">
                        <i className="ti ti-file-invoice me-2"></i>
                        Payment Details
                    </h6>

                    <div className="row g-3">
                        {/* Row 1 */}
                        <div className="col-md-4">
                            <label className="form-label">Customer <span className="text-danger">*</span></label>
                            <select className="form-select" name="customer_id" ref={customerSelectRef}>
                                <option value="">Select Customer...</option>
                                {customers.map(c => (
                                    <option key={c.id} value={c.id}>{c.name}</option>
                                ))}
                            </select>
                        </div>
                        <div className="col-md-4">
                            <label className="form-label">Receipt Number</label>
                            <input
                                type="text"
                                className="form-control bg-light"
                                name="receipt_number"
                                value={header.receipt_number}
                                onChange={handleHeaderChange}
                                readOnly
                            />
                        </div>
                        <div className="col-md-4">
                            <label className="form-label">Receipt Date</label>
                            <input
                                type="date"
                                className="form-control"
                                name="receipt_date"
                                value={header.receipt_date}
                                onChange={handleHeaderChange}
                            />
                        </div>

                        {/* Row 2 */}
                        <div className="col-md-4">
                            <label className="form-label">Bank</label>
                            <select
                                className="form-select"
                                name="bank_id"
                                value={header.bank_id}
                                onChange={handleHeaderChange}
                            >
                                <option value="">Select Bank...</option>
                                {banks.map(b => (
                                    <option key={b.id} value={b.id}>{b.name}</option>
                                ))}
                            </select>
                        </div>
                        <div className="col-md-4">
                            <label className="form-label">Account No</label>
                            <select
                                className="form-select"
                                name="bank_account_id"
                                value={header.bank_account_id}
                                onChange={handleHeaderChange}
                            >
                                <option value="">Select Account...</option>
                                {filteredBankAccounts.map(acc => (
                                    <option key={acc.id} value={acc.id}>
                                        {acc.account_type === 'cash' ? '(Cash) ' : ''}
                                        {acc.account_name}
                                        {acc.account_number ? ` - ${acc.account_number}` : ''}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="col-md-4">
                            <label className="form-label">Pay Mode <span className="text-danger">*</span></label>
                            <select
                                className="form-select"
                                name="paymode_id"
                                value={header.paymode_id}
                                onChange={handlePaymodeChange}
                            >
                                <option value="">Select Pay Mode...</option>
                                {paymodes.map(p => (
                                    <option key={p.id} value={p.id}>{p.name}</option>
                                ))}
                            </select>
                        </div>

                        {/* Conditional Cheque Fields */}
                        {showChequeFields && (
                            <>
                                <div className="col-md-4">
                                    <label className="form-label">Cheque Date <span className="text-danger">*</span></label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        name="cheque_date"
                                        value={header.cheque_date}
                                        onChange={handleHeaderChange}
                                    />
                                </div>
                                <div className="col-md-4">
                                    <label className="form-label">Cheque No <span className="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        name="cheque_no"
                                        value={header.cheque_no}
                                        onChange={handleHeaderChange}
                                        placeholder="Enter cheque number"
                                    />
                                </div>
                            </>
                        )}

                        {/* Row 3 */}
                        <div className="col-md-4">
                            <label className="form-label">Reference No</label>
                            <input
                                type="text"
                                className="form-control"
                                name="reference"
                                value={header.reference}
                                onChange={handleHeaderChange}
                                placeholder="Transaction ID / Reference"
                            />
                        </div>
                        <div className="col-md-4">
                            <label className="form-label">Pay To</label>
                            <input
                                type="text"
                                className="form-control"
                                name="pay_to"
                                value={header.pay_to}
                                onChange={handleHeaderChange}
                                placeholder="Payee name"
                            />
                        </div>
                        <div className="col-md-4">
                            <label className="form-label">Currency</label>
                            <select
                                className="form-select"
                                name="currency_id"
                                value={header.currency_id}
                                onChange={handleHeaderChange}
                            >
                                {currencies.map(c => (
                                    <option key={c.id} value={c.id}>{c.code} - {c.name}</option>
                                ))}
                            </select>
                        </div>

                        {/* Remarks */}
                        <div className="col-12">
                            <label className="form-label">Remarks</label>
                            <textarea
                                className="form-control"
                                name="remarks"
                                value={header.remarks}
                                onChange={handleHeaderChange}
                                rows="2"
                                placeholder="Additional notes..."
                            />
                        </div>
                    </div>
                </div>

                {/* Section 2: Invoice Reference History - UNPAID INVOICES */}
                {header.customer_id && (
                    <div className="border rounded p-3 mb-4">
                        <div className="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h6 className="mb-0">
                                <i className="ti ti-file-invoice me-2"></i>
                                Unpaid Invoices
                                <span className="badge bg-warning ms-2">{unpaidInvoices.length} pending</span>
                            </h6>
                            <div>
                                <button
                                    type="button"
                                    className="btn btn-sm btn-outline-primary me-2"
                                    onClick={selectAllInvoices}
                                >
                                    <i className="ti ti-checks me-1"></i>
                                    {Object.values(selectedInvoices).every(inv => inv.selected) ? 'Deselect All' : 'Select All'}
                                </button>
                            </div>
                        </div>

                        {loading ? (
                            <div className="text-center py-4">
                                <div className="spinner-border text-primary" role="status">
                                    <span className="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        ) : unpaidInvoices.length === 0 ? (
                            <div className="alert alert-info mb-0">
                                <i className="ti ti-info-circle me-2"></i>
                                No unpaid invoices found for this customer.
                            </div>
                        ) : (
                            <div className="table-responsive" style={{ maxHeight: '300px', overflowY: 'auto' }}>
                                <table className="table table-bordered table-hover table-sm align-middle mb-0">
                                    <thead className="table-dark sticky-top">
                                        <tr>
                                            <th style={{ width: '50px' }} className="text-center">Select</th>
                                            <th>Invoice No</th>
                                            <th style={{ width: '80px' }}>Source</th>
                                            <th>Invoice Date</th>
                                            <th className="text-end">Net Total</th>
                                            <th className="text-end">Balance Amount</th>
                                            <th className="text-end" style={{ width: '150px' }}>Received Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {unpaidInvoices.map(inv => {
                                            const key = getInvoiceKey(inv);
                                            const selection = selectedInvoices[key] || {};
                                            return (
                                                <tr key={key} className={selection.selected ? 'table-success' : ''}>
                                                    <td className="text-center">
                                                        <input
                                                            type="checkbox"
                                                            className="form-check-input"
                                                            checked={selection.selected || false}
                                                            onChange={() => toggleInvoiceSelection(inv)}
                                                        />
                                                    </td>
                                                    <td>
                                                        <strong>{inv.invoice_number}</strong>
                                                    </td>
                                                    <td className="text-center">
                                                        {inv.source === 'old' ? (
                                                            <span className="badge bg-secondary" title="From old system">Old</span>
                                                        ) : (
                                                            <span className="badge bg-primary" title="Current system">New</span>
                                                        )}
                                                    </td>
                                                    <td>{inv.invoice_date}</td>
                                                    <td className="text-end">{formatNumber(inv.net_total)}</td>
                                                    <td className="text-end text-danger fw-bold">
                                                        {formatNumber(inv.balance_due)}
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="number"
                                                            className="form-control form-control-sm text-end"
                                                            value={selection.received_amount || ''}
                                                            onChange={(e) => handleReceivedAmountChange(inv, e.target.value)}
                                                            disabled={!selection.selected}
                                                            min="0"
                                                            max={inv.balance_due}
                                                            step="0.01"
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                )}

                {/* Section 3: Final Calculation Section */}
                {header.customer_id && (
                    <div className="border rounded p-3 mb-4 bg-light">
                        <h6 className="border-bottom pb-2 mb-3">
                            <i className="ti ti-calculator me-2"></i>
                            Payment Calculation
                        </h6>

                        <div className="row g-3">
                            <div className="col-md-3">
                                <label className="form-label fw-bold">Outstanding Amount</label>
                                <input
                                    type="text"
                                    className="form-control form-control-lg text-end fw-bold bg-light text-danger"
                                    value={formatNumber(unpaidInvoices.reduce((sum, inv) => sum + (parseFloat(inv.balance_due) || 0), 0))}
                                    readOnly
                                    style={{ fontSize: '1.2rem' }}
                                />
                                <small className="text-muted">Total unpaid balance</small>
                            </div>

                            <div className="col-md-3">
                                <label className="form-label fw-bold">
                                    Customer Paid Amount <span className="text-danger">*</span>
                                </label>
                                <input
                                    type="number"
                                    className="form-control form-control-lg text-end fw-bold border-primary"
                                    value={customerPaidAmount}
                                    onChange={(e) => setCustomerPaidAmount(parseFloat(e.target.value) || 0)}
                                    min="0"
                                    step="0.01"
                                    style={{ fontSize: '1.2rem' }}
                                />
                                <small className="text-muted">Amount received from customer</small>
                            </div>

                            <div className="col-md-3">
                                <label className="form-label fw-bold">Payable Amount</label>
                                <input
                                    type="text"
                                    className="form-control form-control-lg text-end bg-info text-white fw-bold"
                                    value={formatNumber(payableAmount)}
                                    readOnly
                                    style={{ fontSize: '1.2rem' }}
                                />
                                <small className="text-muted">Total of selected invoices</small>
                            </div>

                            <div className="col-md-3">
                                <label className="form-label fw-bold">Excess Amount</label>
                                <input
                                    type="text"
                                    className={`form-control form-control-lg text-end fw-bold ${excessAmount > 0 ? 'bg-success text-white' : 'bg-secondary text-white'}`}
                                    value={formatNumber(excessAmount)}
                                    readOnly
                                    style={{ fontSize: '1.2rem' }}
                                />
                                <small className="text-muted">Advance / Credit balance</small>
                            </div>

                            <div className="col-md-3">
                                <label className="form-label fw-bold">Write-off Amount</label>
                                <input
                                    type="number"
                                    className="form-control form-control-lg text-end bg-warning fw-bold"
                                    value={writeOffAmount}
                                    onChange={(e) => setWriteOffAmount(parseFloat(e.target.value) || 0)}
                                    min="0"
                                    step="0.01"
                                    style={{ fontSize: '1.2rem' }}
                                />
                                <small className="text-muted">Amount to write off (if any)</small>
                            </div>
                        </div>

                        {/* Split Button */}
                        <div className="mt-3 pt-3 border-top">
                            <button
                                type="button"
                                className="btn btn-outline-primary"
                                onClick={handleSplit}
                                disabled={customerPaidAmount <= 0}
                            >
                                <i className="ti ti-arrows-split me-2"></i>
                                Split Payment
                            </button>
                            <small className="text-muted ms-3">
                                Auto-distribute paid amount across selected invoices (oldest first)
                            </small>
                        </div>
                    </div>
                )}

                {/* Section 4: Summary & Actions */}
                {header.customer_id && (
                    <div className="border rounded p-3 bg-white">
                        <div className="row align-items-center">
                            <div className="col-md-6">
                                <div className="d-flex gap-4">
                                    <div>
                                        <small className="text-muted d-block">Invoices Selected</small>
                                        <strong className="fs-5">
                                            {Object.values(selectedInvoices).filter(i => i.selected).length}
                                        </strong>
                                    </div>
                                    <div>
                                        <small className="text-muted d-block">Total Allocated</small>
                                        <strong className="fs-5 text-primary">
                                            {formatNumber(payableAmount)}
                                        </strong>
                                    </div>
                                    <div>
                                        <small className="text-muted d-block">Customer Paid</small>
                                        <strong className="fs-5 text-success">
                                            {formatNumber(customerPaidAmount)}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                            <div className="col-md-6 text-end">
                                <button
                                    type="button"
                                    className="btn btn-secondary me-2"
                                    onClick={() => window.location.href = '/sales-receipts'}
                                >
                                    <i className="ti ti-x me-1"></i>
                                    Close
                                </button>
                                <button
                                    type="button"
                                    className="btn btn-success btn-lg"
                                    onClick={handleSubmit}
                                    disabled={submitting || customerPaidAmount <= 0}
                                >
                                    {submitting ? (
                                        <>
                                            <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                                            Saving...
                                        </>
                                    ) : (
                                        <>
                                            <i className="ti ti-check me-1"></i>
                                            Submit Receipt
                                        </>
                                    )}
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

// Mount component
const rootEl = document.getElementById('sales-receipt-form-root');
if (rootEl) {
    const props = {
        customers: JSON.parse(rootEl.dataset.customers || '[]'),
        currencies: JSON.parse(rootEl.dataset.currencies || '[]'),
        banks: JSON.parse(rootEl.dataset.banks || '[]'),
        bankAccounts: JSON.parse(rootEl.dataset.bankAccounts || '[]'),
        paymodes: JSON.parse(rootEl.dataset.paymodes || '[]'),
        rcpNumber: rootEl.dataset.rcpNumber || ''
    };
    createRoot(rootEl).render(<SalesReceiptForm {...props} />);
}

export default SalesReceiptForm;
