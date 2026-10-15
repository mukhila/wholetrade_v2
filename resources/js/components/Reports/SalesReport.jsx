import React, { useState, useEffect } from 'react';
import axios from 'axios';
import ReportFilters from './ReportFilters';
import KPICards from './KPICards';
import DataTable from './DataTable';

// ── Summary Report ─────────────────────────────────────────────────────────────

const SalesSummaryReport = ({ filterOptions }) => {
    const [loading, setLoading]         = useState(false);
    const [rows, setRows]               = useState([]);
    const [totals, setTotals]           = useState({});
    const [dateLoading, setDateLoading] = useState(false);
    const [dateRows, setDateRows]       = useState([]);
    const [dateTotals, setDateTotals]   = useState({});
    const [filters, setFilters] = useState({
        date_from:      '',
        date_to:        '',
        customer_id:    '',
        salesperson_id: '',
        payment_status: '',
        group_by:       'customer',
    });

    useEffect(() => { fetchSummary(); fetchDateWise(); }, [filters]);

    const fetchSummary = async () => {
        setLoading(true);
        try {
            const res = await axios.get('/api/reports/sales/summary', { params: filters });
            setRows(res.data.data);
            setTotals(res.data.totals);
        } catch (err) {
            console.error('Error fetching sales summary:', err);
        } finally {
            setLoading(false);
        }
    };

    const fetchDateWise = async () => {
        setDateLoading(true);
        try {
            const res = await axios.get('/api/reports/sales/summary/date-wise', { params: filters });
            setDateRows(res.data.data);
            setDateTotals(res.data.totals);
        } catch (err) {
            console.error('Error fetching date-wise summary:', err);
        } finally {
            setDateLoading(false);
        }
    };

    const handleFilterChange = (key, value) => {
        setFilters(prev => ({ ...prev, [key]: value }));
    };

    const handlePrint = () => {
        window.open('/reports/sales/summary/print?' + new URLSearchParams(filters).toString(), '_blank');
    };

    const handleExport = () => {
        window.location.href = '/api/reports/sales/summary/export?' + new URLSearchParams(filters).toString();
    };

    const handleDateWisePrint = () => {
        window.open('/reports/sales/summary/date-wise/print?' + new URLSearchParams(filters).toString(), '_blank');
    };

    const handleDateWiseExport = () => {
        window.location.href = '/api/reports/sales/summary/date-wise/export?' + new URLSearchParams(filters).toString();
    };

    const groupLabel = { customer: 'Customer', salesperson: 'Salesperson', month: 'Month' }[filters.group_by] || 'Customer';

    const summaryFilters = [
        { type: 'date', label: 'From Date', key: 'date_from' },
        { type: 'date', label: 'To Date', key: 'date_to' },
        {
            type: 'select', label: 'Group By', key: 'group_by', options: [
                { id: 'customer',    name: 'By Customer' },
                { id: 'salesperson', name: 'By Salesperson' },
                { id: 'month',       name: 'By Month' },
            ]
        },
        { type: 'autocomplete', label: 'Customer', key: 'customer_id' },
        { type: 'select', label: 'Salesperson', key: 'salesperson_id', options: filterOptions.salespersons },
        {
            type: 'select', label: 'Payment Status', key: 'payment_status', options: [
                { id: 'unpaid',  name: 'Unpaid' },
                { id: 'partial', name: 'Partial' },
                { id: 'paid',    name: 'Paid' },
            ]
        },
    ];

    const kpiData = [
        { title: 'Total Sales Value',  value: `$${(totals.total_sales   || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`, icon: 'ti-currency-dollar', color: 'success' },
        { title: 'Total Invoices',     value: totals.invoice_count || 0, icon: 'ti-file-invoice', color: 'primary' },
        { title: 'Total Collected',    value: `$${(totals.total_paid    || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`, icon: 'ti-wallet', color: 'info' },
        { title: 'Outstanding Balance',value: `$${(totals.total_balance || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`, icon: 'ti-alert-circle', color: 'warning' },
    ];

    const fmt = (v) => `$${parseFloat(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    const columns = [
        { key: 'group_name',     label: groupLabel },
        { key: 'invoice_count',  label: 'Invoices', render: (row) => <span className="badge bg-secondary">{row.invoice_count}</span> },
        { key: 'total_sales',    label: 'Total Sales',    render: (row) => <span className="fw-bold text-primary">{fmt(row.total_sales)}</span> },
        { key: 'total_paid',     label: 'Total Paid',     render: (row) => <span className="text-success">{fmt(row.total_paid)}</span> },
        {
            key: 'total_balance', label: 'Outstanding',
            render: (row) => {
                const bal = parseFloat(row.total_balance || 0);
                return <span className={bal > 0 ? 'text-danger fw-bold' : 'text-success'}>{fmt(bal)}</span>;
            }
        },
    ];

    const allDataSummary = [
        { label: 'Total Invoices',       value: totals.invoice_count || 0,                        icon: 'ti-file-invoice',    color: '#1a1a2e',   textColor: '#fff'     },
        { label: 'Total Sales Value',    value: fmt(totals.total_sales),                           icon: 'ti-currency-dollar', color: '#1e40af',   textColor: '#fff'     },
        { label: 'Total Collected',      value: fmt(totals.total_paid),                            icon: 'ti-wallet',          color: '#166534',   textColor: '#fff'     },
        { label: 'Total Outstanding',    value: fmt(totals.total_balance),                         icon: 'ti-alert-circle',    color: '#991b1b',   textColor: '#fff'     },
        { label: 'Gross Profit / Loss',  value: fmt(dateTotals.gross_profit),                      icon: 'ti-trending-up',     color: parseFloat(dateTotals.gross_profit || 0) >= 0 ? '#14532d' : '#7f1d1d', textColor: '#fff' },
        { label: 'Profit Margin %',      value: `${dateTotals.profit_pct || 0}%`,                  icon: 'ti-percentage',      color: '#1e3a5f',   textColor: '#fff'     },
        { label: `Total ${groupLabel}s`, value: rows.length,                                        icon: 'ti-users',           color: '#4a1d96',   textColor: '#fff'     },
        { label: 'Total Active Days',    value: dateRows.length,                                    icon: 'ti-calendar-stats',  color: '#065f46',   textColor: '#fff'     },
    ];

    return (
        <div>
            <ReportFilters filters={summaryFilters} values={filters} onChange={handleFilterChange} />
            <KPICards data={kpiData} />

            {/* All Data Summary Panel */}
            <div className="card mb-4 border-0 shadow-sm">
                <div className="card-header text-white fw-bold py-2 px-3" style={{ background: '#1a1a2e', letterSpacing: '1px', fontSize: '13px' }}>
                    <i className="ti ti-layout-dashboard me-2"></i> ALL DATA SUMMARY
                </div>
                <div className="card-body p-3">
                    <div className="row g-3">
                        {allDataSummary.map((item, idx) => (
                            <div key={idx} className="col-6 col-md-3">
                                <div className="d-flex align-items-center rounded p-3 gap-3" style={{ background: item.color }}>
                                    <div style={{ fontSize: '26px', color: item.textColor, opacity: 0.85 }}>
                                        <i className={`ti ${item.icon}`}></i>
                                    </div>
                                    <div>
                                        <div style={{ fontSize: '11px', color: item.textColor, opacity: 0.75, textTransform: 'uppercase', letterSpacing: '0.5px' }}>{item.label}</div>
                                        <div style={{ fontSize: '16px', fontWeight: 'bold', color: item.textColor }}>{item.value}</div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Breakdown Table */}
                    <div className="mt-4">
                        <table className="table table-sm table-bordered mb-0">
                            <thead style={{ background: '#f0f4ff' }}>
                                <tr>
                                    <th>Metric</th>
                                    <th className="text-end">Value</th>
                                    <th className="text-end">% of Sales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><i className="ti ti-currency-dollar me-1 text-primary"></i> Total Sales Value</td>
                                    <td className="text-end fw-bold text-primary">{fmt(totals.total_sales)}</td>
                                    <td className="text-end text-muted">100%</td>
                                </tr>
                                <tr>
                                    <td><i className="ti ti-wallet me-1 text-success"></i> Total Collected</td>
                                    <td className="text-end text-success">{fmt(totals.total_paid)}</td>
                                    <td className="text-end">
                                        {totals.total_sales > 0
                                            ? <span className="badge bg-success">{((totals.total_paid / totals.total_sales) * 100).toFixed(1)}%</span>
                                            : <span className="text-muted">—</span>
                                        }
                                    </td>
                                </tr>
                                <tr>
                                    <td><i className="ti ti-alert-circle me-1 text-danger"></i> Total Outstanding</td>
                                    <td className="text-end text-danger fw-bold">{fmt(totals.total_balance)}</td>
                                    <td className="text-end">
                                        {totals.total_sales > 0
                                            ? <span className="badge bg-danger">{((totals.total_balance / totals.total_sales) * 100).toFixed(1)}%</span>
                                            : <span className="text-muted">—</span>
                                        }
                                    </td>
                                </tr>
                                <tr>
                                    <td><i className="ti ti-trending-up me-1 text-info"></i> Gross Profit / Loss</td>
                                    <td className={`text-end fw-bold ${parseFloat(dateTotals.gross_profit || 0) >= 0 ? 'text-success' : 'text-danger'}`}>
                                        {fmt(dateTotals.gross_profit)}
                                    </td>
                                    <td className="text-end">
                                        {dateTotals.profit_pct > 0
                                            ? <span className="badge bg-success">{dateTotals.profit_pct}%</span>
                                            : dateTotals.loss_pct > 0
                                                ? <span className="badge bg-danger">-{dateTotals.loss_pct}%</span>
                                                : <span className="text-muted">—</span>
                                        }
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot style={{ background: '#1a1a2e', color: '#fff' }}>
                                <tr>
                                    <td className="fw-bold">Total Invoices Across All {groupLabel}s</td>
                                    <td className="text-end fw-bold" style={{ color: '#90d4ff' }}>{totals.invoice_count || 0} invoices</td>
                                    <td className="text-end text-muted">—</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {/* Date-wise Summary with Profit & Loss */}
            <div className="card mb-4">
                <div className="card-body">
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h4 className="card-title mb-0">Date-wise Sales Summary with Profit &amp; Loss</h4>
                        <div className="d-flex gap-2">
                            <button className="btn btn-secondary btn-sm" onClick={handleDateWisePrint}>
                                <i className="ti ti-printer me-1"></i> Print
                            </button>
                            <button className="btn btn-success btn-sm" onClick={handleDateWiseExport}>
                                <i className="ti ti-download me-1"></i> Export CSV
                            </button>
                        </div>
                    </div>
                    {dateLoading ? (
                        <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
                    ) : (
                        <div className="table-responsive">
                            <table className="table table-hover table-bordered table-nowrap mb-0">
                                <thead className="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Invoices</th>
                                        <th>Total Sales</th>
                                        <th>Total Paid</th>
                                        <th>Outstanding</th>
                                        <th>Gross Profit / Loss</th>
                                        <th className="text-success">Profit %</th>
                                        <th className="text-danger">Loss %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {dateRows.length === 0 ? (
                                        <tr><td colSpan={9} className="text-center text-muted py-4">No data found for the selected filters.</td></tr>
                                    ) : (
                                        dateRows.map((row, i) => {
                                            const profit = parseFloat(row.gross_profit || 0);
                                            const bal    = parseFloat(row.total_balance || 0);
                                            return (
                                                <tr key={i}>
                                                    <td className="text-muted">{i + 1}</td>
                                                    <td className="fw-semibold">{row.date}</td>
                                                    <td><span className="badge bg-secondary">{row.invoice_count}</span></td>
                                                    <td className="fw-bold text-primary">{fmt(row.total_sales)}</td>
                                                    <td className="text-success">{fmt(row.total_paid)}</td>
                                                    <td className={bal > 0 ? 'text-danger fw-bold' : 'text-success'}>{fmt(bal)}</td>
                                                    <td className={`fw-bold ${profit >= 0 ? 'text-success' : 'text-danger'}`}>
                                                        {fmt(profit)}
                                                    </td>
                                                    <td>
                                                        {row.profit_pct > 0
                                                            ? <span className="badge bg-success">{row.profit_pct}%</span>
                                                            : <span className="text-muted">—</span>
                                                        }
                                                    </td>
                                                    <td>
                                                        {row.loss_pct > 0
                                                            ? <span className="badge bg-danger">{row.loss_pct}%</span>
                                                            : <span className="text-muted">—</span>
                                                        }
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    )}
                                </tbody>
                                {dateRows.length > 0 && (
                                    <tfoot className="table-dark fw-bold">
                                        <tr>
                                            <td colSpan={2} className="text-end">Grand Total:</td>
                                            <td>{dateTotals.invoice_count}</td>
                                            <td>{fmt(dateTotals.total_sales)}</td>
                                            <td>{fmt(dateTotals.total_paid)}</td>
                                            <td className="text-warning">{fmt(dateTotals.total_balance)}</td>
                                            <td className={parseFloat(dateTotals.gross_profit || 0) >= 0 ? 'text-success' : 'text-warning'}>
                                                {fmt(dateTotals.gross_profit)}
                                            </td>
                                            <td>
                                                {dateTotals.profit_pct > 0
                                                    ? <span className="badge bg-success">{dateTotals.profit_pct}%</span>
                                                    : <span className="text-muted">—</span>
                                                }
                                            </td>
                                            <td>
                                                {dateTotals.loss_pct > 0
                                                    ? <span className="badge bg-danger">{dateTotals.loss_pct}%</span>
                                                    : <span className="text-muted">—</span>
                                                }
                                            </td>
                                        </tr>
                                    </tfoot>
                                )}
                            </table>
                        </div>
                    )}
                </div>
            </div>

            {/* Grouped Summary */}
            <div className="card">
                <div className="card-body">
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h4 className="card-title mb-0">Sales Summary — {groupLabel}</h4>
                        <div className="d-flex gap-2">
                            <button className="btn btn-secondary btn-sm" onClick={handlePrint}>
                                <i className="ti ti-printer me-1"></i> Print
                            </button>
                            <button className="btn btn-success btn-sm" onClick={handleExport}>
                                <i className="ti ti-download me-1"></i> Export CSV
                            </button>
                        </div>
                    </div>

                    {loading ? (
                        <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
                    ) : (
                        <>
                            <div className="table-responsive">
                                <table className="table table-hover table-nowrap mb-0">
                                    <thead className="table-light">
                                        <tr>
                                            <th>#</th>
                                            {columns.map(c => <th key={c.key}>{c.label}</th>)}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {rows.length === 0 ? (
                                            <tr><td colSpan={columns.length + 1} className="text-center text-muted py-4">No data found for the selected filters.</td></tr>
                                        ) : (
                                            rows.map((row, i) => (
                                                <tr key={i}>
                                                    <td className="text-muted">{i + 1}</td>
                                                    {columns.map(c => (
                                                        <td key={c.key}>{c.render ? c.render(row) : row[c.key]}</td>
                                                    ))}
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                    {rows.length > 0 && (
                                        <tfoot className="table-dark fw-bold">
                                            <tr>
                                                <td colSpan={2}>Grand Total ({rows.length} {groupLabel.toLowerCase()}s)</td>
                                                <td>{totals.invoice_count}</td>
                                                <td>{fmt(totals.total_sales)}</td>
                                                <td>{fmt(totals.total_paid)}</td>
                                                <td className="text-warning">{fmt(totals.total_balance)}</td>
                                            </tr>
                                        </tfoot>
                                    )}
                                </table>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
};

// ── Detailed Report ────────────────────────────────────────────────────────────

const SalesDetailedReport = ({ filterOptions }) => {
    const [loading, setLoading]       = useState(false);
    const [data, setData]             = useState([]);
    const [pagination, setPagination] = useState({});
    const [kpis, setKpis]             = useState({});
    const [filters, setFilters]       = useState({
        date_from:      '',
        date_to:        '',
        salesperson_id: '',
        customer_id:    '',
        payment_status: '',
        source:         'current',
        search:         '',
        per_page:       15,
    });

    useEffect(() => { fetchReport(); }, [filters]);

    const fetchReport = async (page = 1) => {
        setLoading(true);
        try {
            const response = await axios.get('/api/reports/sales', { params: { ...filters, page } });
            setData(response.data.data);
            setPagination(response.data.pagination);
            setKpis(response.data.kpis);
        } catch (error) {
            console.error('Error fetching sales report:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleFilterChange = (key, value) => setFilters(prev => ({ ...prev, [key]: value }));

    const handlePrint = () => {
        window.open('/reports/sales/print?' + new URLSearchParams(filters).toString(), '_blank');
    };

    const handlePrintByCustomer = () => {
        window.open('/reports/sales/customer-wise/print?' + new URLSearchParams(filters).toString(), '_blank');
    };

    const handleExport = () => {
        window.location.href = '/api/reports/sales/export?' + new URLSearchParams(filters).toString();
    };

    const salesFilters = [
        { type: 'date', label: 'From Date', key: 'date_from' },
        { type: 'date', label: 'To Date', key: 'date_to' },
        {
            type: 'select', label: 'Source', key: 'source', options: [
                { id: 'all',     name: 'All Invoices' },
                { id: 'current', name: 'Current Invoices' },
                { id: 'legacy',  name: 'Legacy Invoices' },
            ]
        },
        { type: 'select', label: 'Salesperson', key: 'salesperson_id', options: filterOptions.salespersons },
        { type: 'autocomplete', label: 'Customer', key: 'customer_id' },
        {
            type: 'select', label: 'Payment Status', key: 'payment_status', options: [
                { id: 'unpaid',  name: 'Unpaid' },
                { id: 'partial', name: 'Partial' },
                { id: 'paid',    name: 'Paid' },
            ]
        },
        { type: 'text', label: 'Search', key: 'search', placeholder: 'Search by Invoice No' },
    ];

    const kpiData = [
        { title: 'Total Sales Value',   value: `$${(kpis.total_sales_value || 0).toLocaleString()}`,   icon: 'ti-currency-dollar', color: 'success' },
        { title: 'Total Invoices',      value: kpis.total_invoices || 0,                               icon: 'ti-file-invoice',    color: 'primary' },
        { title: 'Avg Order Value',     value: `$${(kpis.avg_order_value  || 0).toLocaleString()}`,    icon: 'ti-chart-bar',       color: 'info'    },
        { title: 'Outstanding Balance', value: `$${(kpis.total_outstanding || 0).toLocaleString()}`,   icon: 'ti-alert-circle',    color: 'warning' },
    ];

    const getSourceBadge = (source) => {
        if (source === 'current') return <span className="badge bg-primary">Current</span>;
        if (source === 'legacy')  return <span className="badge bg-secondary">Legacy</span>;
        return null;
    };

    const getPaymentStatusBadge = (row) => {
        const status = row.payment_status_display || row.payment_status;
        const statusLower = (status || '').toLowerCase();
        const color = { unpaid: 'warning', open: 'warning', partial: 'info', paid: 'success' }[statusLower] || 'secondary';
        return <span className={`badge bg-${color}`}>{status}</span>;
    };

    const columns = [
        { key: 'source',          label: 'Source',       render: (row) => getSourceBadge(row.source) },
        { key: 'invoice_number',  label: 'Invoice No' },
        { key: 'invoice_date',    label: 'Invoice Date', render: (row) => row.invoice_date ? new Date(row.invoice_date).toLocaleDateString() : '-' },
        { key: 'customer_name',   label: 'Customer',     render: (row) => row.customer_name || 'N/A' },
        { key: 'salesperson_name',label: 'Salesperson',  render: (row) => row.salesperson_name || '-' },
        { key: 'total_qty',       label: 'Qty',          render: (row) => row.total_qty != null ? <span className="badge bg-info text-dark">{row.total_qty}</span> : <span className="text-muted">—</span> },
        { key: 'total',           label: 'Total',        render: (row) => `$${parseFloat(row.total || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` },
        { key: 'tax_amount',      label: 'GST',          render: (row) => row.tax_amount != null ? <span className="text-info">${parseFloat(row.tax_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span> : <span className="text-muted">—</span> },
        { key: 'paid_amount',     label: 'Paid',         render: (row) => `$${parseFloat(row.paid_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` },
        {
            key: 'balance', label: 'Balance',
            render: (row) => {
                const balance = parseFloat(row.balance || 0);
                return <span className={balance > 0 ? 'text-danger fw-bold' : 'text-success'}>${balance.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>;
            }
        },
        { key: 'payment_status',  label: 'Status',       render: (row) => getPaymentStatusBadge(row) },
    ];

    return (
        <div>
            <ReportFilters filters={salesFilters} values={filters} onChange={handleFilterChange} />
            <KPICards data={kpiData} />

            {(!filters.source || filters.source === 'all') && (
                <div className="row mb-3">
                    <div className="col-12">
                        <div className="alert alert-info d-flex align-items-center" role="alert">
                            <i className="ti ti-info-circle me-2"></i>
                            <div>
                                Showing <strong>{kpis.current_invoices_count || 0}</strong> current invoices and <strong>{kpis.legacy_invoices_count || 0}</strong> legacy invoices
                            </div>
                        </div>
                    </div>
                </div>
            )}

            <div className="row mb-3">
                <div className="col-md-6">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">Top Customers</h5>
                            <ul className="list-group list-group-flush">
                                {kpis.top_customers && kpis.top_customers.length > 0 ? (
                                    kpis.top_customers.map((c, i) => (
                                        <li key={i} className="list-group-item d-flex justify-content-between">
                                            <span>{c.customer_name || 'N/A'}</span>
                                            <span className="fw-bold">${parseFloat(c.total_sales || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                        </li>
                                    ))
                                ) : <li className="list-group-item text-muted">No data available</li>}
                            </ul>
                        </div>
                    </div>
                </div>
                <div className="col-md-6">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">Top Salespersons</h5>
                            <ul className="list-group list-group-flush">
                                {kpis.top_salespersons && kpis.top_salespersons.length > 0 ? (
                                    kpis.top_salespersons.map((s, i) => (
                                        <li key={i} className="list-group-item d-flex justify-content-between">
                                            <span>{s.salesperson_name || 'N/A'}</span>
                                            <span className="fw-bold">${parseFloat(s.total_sales || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                        </li>
                                    ))
                                ) : (
                                    <li className="list-group-item text-muted">
                                        {filters.source === 'legacy' ? 'Legacy invoices do not have salesperson data' : 'No data available'}
                                    </li>
                                )}
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div className="card">
                <div className="card-body">
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h4 className="card-title">Invoice List</h4>
                        <div className="d-flex gap-2">
                            <div className="dropdown">
                                <button className="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i className="ti ti-printer me-1"></i> Print
                                </button>
                                <ul className="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a className="dropdown-item" href="#" onClick={(e) => { e.preventDefault(); handlePrint(); }}>
                                            <i className="ti ti-list me-2"></i> Normal Print
                                        </a>
                                    </li>
                                    <li>
                                        <a className="dropdown-item" href="#" onClick={(e) => { e.preventDefault(); handlePrintByCustomer(); }}>
                                            <i className="ti ti-users me-2"></i> Customer-wise Print
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <button className="btn btn-success btn-sm" onClick={handleExport}>
                                <i className="ti ti-download me-1"></i> Export CSV
                            </button>
                        </div>
                    </div>
                    <DataTable columns={columns} data={data} loading={loading} pagination={pagination} onPageChange={fetchReport} />
                </div>
            </div>
        </div>
    );
};

// ── Profit & Loss Report ───────────────────────────────────────────────────────

const SalesProfitLossReport = ({ filterOptions }) => {
    const [loading, setLoading]           = useState(false);
    const [dayLoading, setDayLoading]     = useState(false);
    const [data, setData]                 = useState([]);
    const [totals, setTotals]             = useState({});
    const [pagination, setPagination]     = useState({});
    const [dayRows, setDayRows]           = useState([]);
    const [dayTotals, setDayTotals]       = useState({});
    const [filters, setFilters]           = useState({
        date_from:      '',
        date_to:        '',
        customer_id:    '',
        salesperson_id: '',
        search:         '',
        per_page:       15,
    });

    useEffect(() => { fetchReport(); fetchDaySummary(); }, [filters]);

    const fetchReport = async (page = 1) => {
        setLoading(true);
        try {
            const res = await axios.get('/api/reports/profit-loss', { params: { ...filters, page } });
            setData(res.data.data);
            setTotals(res.data.totals);
            setPagination(res.data.pagination);
        } catch (err) {
            console.error('P&L fetch error:', err);
        } finally {
            setLoading(false);
        }
    };

    const fetchDaySummary = async () => {
        setDayLoading(true);
        try {
            const res = await axios.get('/api/reports/profit-loss/day-summary', { params: filters });
            setDayRows(res.data.data);
            setDayTotals(res.data.totals);
        } catch (err) {
            console.error('P&L day summary error:', err);
        } finally {
            setDayLoading(false);
        }
    };

    const handleFilterChange = (key, value) => setFilters(prev => ({ ...prev, [key]: value }));

    const handlePrint = () => {
        window.open('/reports/profit-loss/print?' + new URLSearchParams(filters).toString(), '_blank');
    };

    const handleExport = () => {
        window.location.href = '/api/reports/profit-loss/export?' + new URLSearchParams(filters).toString();
    };

    const fmt = (v) => `$${parseFloat(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    const plFilters = [
        { type: 'date',         label: 'From Date',   key: 'date_from' },
        { type: 'date',         label: 'To Date',     key: 'date_to' },
        { type: 'autocomplete', label: 'Customer',    key: 'customer_id' },
        { type: 'select',       label: 'Salesperson', key: 'salesperson_id', options: filterOptions.salespersons },
        { type: 'text',         label: 'Search',      key: 'search', placeholder: 'Search by Invoice No' },
    ];

    const kpiData = [
        { title: 'Total Revenue (ex-Tax)', value: fmt(totals.total_revenue), icon: 'ti-currency-dollar', color: 'success' },
        { title: 'Total COGS',             value: fmt(totals.total_cost),    icon: 'ti-package',          color: 'danger'  },
        { title: 'Gross Profit',           value: fmt(totals.gross_profit),  icon: 'ti-trending-up',      color: parseFloat(totals.gross_profit || 0) >= 0 ? 'primary' : 'warning' },
        { title: 'Gross Margin',           value: `${totals.margin_pct || 0}%`, icon: 'ti-percentage',   color: 'info'    },
    ];

    const columns = [
        { key: 'invoice_number',   label: 'Invoice No' },
        { key: 'invoice_date',     label: 'Date',        render: (r) => r.invoice_date ? new Date(r.invoice_date).toLocaleDateString() : '-' },
        { key: 'customer_name',    label: 'Customer',    render: (r) => r.customer_name    || 'N/A' },
        { key: 'salesperson_name', label: 'Salesperson', render: (r) => r.salesperson_name || '-' },
        { key: 'net_total',        label: 'Invoice Total', render: (r) => fmt(r.net_total) },
        { key: 'revenue',          label: 'Revenue (ex-Tax)', render: (r) => fmt(r.revenue) },
        { key: 'total_cost',       label: 'COGS',        render: (r) => <span className="text-danger">{fmt(r.total_cost)}</span> },
        {
            key: 'gross_profit', label: 'Gross Profit',
            render: (r) => {
                const profit = parseFloat(r.gross_profit || 0);
                return <span className={`fw-bold ${profit >= 0 ? 'text-success' : 'text-danger'}`}>{fmt(profit)}</span>;
            }
        },
        {
            key: 'margin_pct', label: 'Margin %',
            render: (r) => {
                const pct = parseFloat(r.margin_pct || 0);
                return (
                    <span className={`badge ${pct >= 30 ? 'bg-success' : pct >= 10 ? 'bg-warning text-dark' : 'bg-danger'}`}>
                        {pct}%
                    </span>
                );
            }
        },
    ];

    return (
        <div>
            <div className="alert alert-info d-flex align-items-center mb-3" role="alert">
                <i className="ti ti-info-circle me-2"></i>
                <div>Profit &amp; Loss is calculated from <strong>current invoices only</strong>. Legacy invoices do not carry product cost data.</div>
            </div>

            <ReportFilters filters={plFilters} values={filters} onChange={handleFilterChange} />
            <KPICards data={kpiData} />

            {/* Day-wise Summary */}
            <div className="card mb-4">
                <div className="card-body">
                    <h4 className="card-title mb-3">Day-wise Profit &amp; Loss Summary</h4>
                    {dayLoading ? (
                        <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
                    ) : (
                        <div className="table-responsive">
                            <table className="table table-hover table-bordered table-nowrap mb-0">
                                <thead className="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Invoices</th>
                                        <th>Revenue (ex-Tax)</th>
                                        <th>COGS</th>
                                        <th>Gross Profit / Loss</th>
                                        <th className="text-success">Profit %</th>
                                        <th className="text-danger">Loss %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {dayRows.length === 0 ? (
                                        <tr><td colSpan={8} className="text-center text-muted py-4">No data found for the selected filters.</td></tr>
                                    ) : (
                                        dayRows.map((row, i) => {
                                            const profit = parseFloat(row.gross_profit || 0);
                                            return (
                                                <tr key={i}>
                                                    <td className="text-muted">{i + 1}</td>
                                                    <td>{row.date}</td>
                                                    <td><span className="badge bg-secondary">{row.invoice_count}</span></td>
                                                    <td className="fw-bold text-primary">{fmt(row.total_revenue)}</td>
                                                    <td className="text-danger">{fmt(row.total_cost)}</td>
                                                    <td className={`fw-bold ${profit >= 0 ? 'text-success' : 'text-danger'}`}>
                                                        {fmt(profit)}
                                                    </td>
                                                    <td>
                                                        {row.profit_pct > 0
                                                            ? <span className="badge bg-success">{row.profit_pct}%</span>
                                                            : <span className="text-muted">—</span>
                                                        }
                                                    </td>
                                                    <td>
                                                        {row.loss_pct > 0
                                                            ? <span className="badge bg-danger">{row.loss_pct}%</span>
                                                            : <span className="text-muted">—</span>
                                                        }
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    )}
                                </tbody>
                                {dayRows.length > 0 && (
                                    <tfoot className="table-dark fw-bold">
                                        <tr>
                                            <td colSpan={2} className="text-end">Grand Total:</td>
                                            <td>{dayTotals.invoice_count}</td>
                                            <td>{fmt(dayTotals.total_revenue)}</td>
                                            <td className="text-danger">{fmt(dayTotals.total_cost)}</td>
                                            <td className={parseFloat(dayTotals.gross_profit || 0) >= 0 ? 'text-success' : 'text-warning'}>
                                                {fmt(dayTotals.gross_profit)}
                                            </td>
                                            <td>
                                                {dayTotals.profit_pct > 0
                                                    ? <span className="badge bg-success">{dayTotals.profit_pct}%</span>
                                                    : <span className="text-muted">—</span>
                                                }
                                            </td>
                                            <td>
                                                {dayTotals.loss_pct > 0
                                                    ? <span className="badge bg-danger">{dayTotals.loss_pct}%</span>
                                                    : <span className="text-muted">—</span>
                                                }
                                            </td>
                                        </tr>
                                    </tfoot>
                                )}
                            </table>
                        </div>
                    )}
                </div>
            </div>

            {/* Invoice-level Detail */}
            <div className="card">
                <div className="card-body">
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h4 className="card-title mb-0">Invoice Profit &amp; Loss</h4>
                        <div className="d-flex gap-2">
                            <button className="btn btn-secondary btn-sm" onClick={handlePrint}>
                                <i className="ti ti-printer me-1"></i> Print
                            </button>
                            <button className="btn btn-success btn-sm" onClick={handleExport}>
                                <i className="ti ti-download me-1"></i> Export CSV
                            </button>
                        </div>
                    </div>

                    {loading ? (
                        <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>
                    ) : (
                        <>
                            <div className="table-responsive">
                                <table className="table table-hover table-nowrap mb-0">
                                    <thead className="table-light">
                                        <tr>
                                            <th>#</th>
                                            {columns.map(c => <th key={c.key}>{c.label}</th>)}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {data.length === 0 ? (
                                            <tr><td colSpan={columns.length + 1} className="text-center text-muted py-4">No invoices found for the selected filters.</td></tr>
                                        ) : (
                                            data.map((row, i) => (
                                                <tr key={i}>
                                                    <td className="text-muted">{((pagination.current_page - 1) * pagination.per_page) + i + 1}</td>
                                                    {columns.map(c => (
                                                        <td key={c.key}>{c.render ? c.render(row) : row[c.key]}</td>
                                                    ))}
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                    {data.length > 0 && (
                                        <tfoot className="table-dark fw-bold">
                                            <tr>
                                                <td colSpan={5} className="text-end">Grand Total ({totals.invoice_count} invoices):</td>
                                                <td>{fmt(totals.total_revenue)}</td>
                                                <td className="text-danger">{fmt(totals.total_cost)}</td>
                                                <td className={parseFloat(totals.gross_profit || 0) >= 0 ? 'text-success' : 'text-warning'}>
                                                    {fmt(totals.gross_profit)}
                                                </td>
                                                <td className={parseFloat(totals.margin_pct || 0) >= 0 ? 'text-info' : 'text-warning'}>
                                                    {totals.margin_pct}%
                                                </td>
                                            </tr>
                                        </tfoot>
                                    )}
                                </table>
                            </div>

                            {pagination.last_page > 1 && (
                                <div className="d-flex justify-content-between align-items-center mt-3">
                                    <small className="text-muted">
                                        Showing {pagination.from}–{pagination.to} of {pagination.total}
                                    </small>
                                    <div className="d-flex gap-1">
                                        {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map(p => (
                                            <button
                                                key={p}
                                                className={`btn btn-sm ${p === pagination.current_page ? 'btn-primary' : 'btn-outline-secondary'}`}
                                                onClick={() => fetchReport(p)}
                                            >
                                                {p}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </div>
    );
};

// ── Main Sales Report (Summary / Detailed / P&L toggle) ────────────────────────

const SalesReport = ({ filterOptions }) => {
    const [reportMode, setReportMode] = useState('summary');

    const tabs = [
        { key: 'summary',  label: 'Summary Report',   icon: 'ti-layout-list'  },
        { key: 'detailed', label: 'Detailed Report',  icon: 'ti-list-details' },
        { key: 'pnl',      label: 'Profit & Loss',    icon: 'ti-trending-up'  },
    ];

    return (
        <div className="sales-report">
            <div className="d-flex gap-2 mb-4">
                {tabs.map(t => (
                    <button
                        key={t.key}
                        className={`btn ${reportMode === t.key ? 'btn-primary' : 'btn-outline-secondary'}`}
                        onClick={() => setReportMode(t.key)}
                    >
                        <i className={`ti ${t.icon} me-1`}></i> {t.label}
                    </button>
                ))}
            </div>

            {reportMode === 'summary'  && <SalesSummaryReport     filterOptions={filterOptions} />}
            {reportMode === 'detailed' && <SalesDetailedReport    filterOptions={filterOptions} />}
            {reportMode === 'pnl'      && <SalesProfitLossReport  filterOptions={filterOptions} />}
        </div>
    );
};

export default SalesReport;
