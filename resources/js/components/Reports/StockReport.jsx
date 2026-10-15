import React, { useState, useEffect } from 'react';
import axios from 'axios';
import ReportFilters from './ReportFilters';
import KPICards from './KPICards';
import DataTable from './DataTable';

const StockReport = ({ filterOptions }) => {
    const [loading, setLoading] = useState(false);
    const [data, setData] = useState([]);
    const [pagination, setPagination] = useState({});
    const [kpis, setKpis] = useState({});
    const [filters, setFilters] = useState({
        category_id: '',
        brand_id: '',
        inventory_status: '',
        batch_no: '',
        // expiry_from: '', // Removed from API for now
        // expiry_to: '',
        search: '',
        per_page: 15,
    });

    useEffect(() => {
        fetchReport();
    }, [filters]);

    const fetchReport = async (page = 1) => {
        setLoading(true);
        try {
            const response = await axios.get('/api/reports/stock', {
                params: { ...filters, page }
            });
            setData(response.data.data);
            setPagination(response.data.pagination);
            setKpis(response.data.kpis);
        } catch (error) {
            console.error('Error fetching stock report:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleFilterChange = (key, value) => {
        setFilters(prev => ({ ...prev, [key]: value }));
    };

    const handlePrint = () => {
        const params = new URLSearchParams(filters).toString();
        window.open(`/reports/stock/print?${params}`, '_blank');
    };

    const handleExport = () => {
        window.location.href = '/api/reports/stock/export?' + new URLSearchParams(filters).toString();
    };

    const stockFilters = [
        { type: 'select', label: 'Category', key: 'category_id', options: filterOptions.categories },
        { type: 'select', label: 'Brand', key: 'brand_id', options: filterOptions.brands },
        {
            type: 'select', label: 'Status', key: 'inventory_status', options: [
                { id: 'normal', name: 'Normal' },
                { id: 'low_stock', name: 'Low Stock' },
                { id: 'out_of_stock', name: 'Out of Stock' }
            ]
        },
        { type: 'text', label: 'Batch/Lot No', key: 'batch_no', placeholder: 'Search by Batch' },
        // { type: 'date', label: 'Expiry From', key: 'expiry_from' },
        // { type: 'date', label: 'Expiry To', key: 'expiry_to' },
        { type: 'text', label: 'Search', key: 'search', placeholder: 'Search by SKU or Name' },
    ];

    const kpiData = [
        { title: 'Total Stock Qty', value: (kpis.total_stock_qty || 0).toLocaleString(), icon: 'ti-box', color: 'primary' },
        { title: 'Total Stock Value', value: `$${(kpis.total_stock_value || 0).toLocaleString()}`, icon: 'ti-currency-dollar', color: 'success' },
        { title: 'Low Stock Items', value: kpis.low_stock_count || 0, icon: 'ti-alert-triangle', color: 'warning' },
        { title: 'Out of Stock', value: kpis.out_of_stock_count || 0, icon: 'ti-x-circle', color: 'danger' },
        { title: 'Expiring Soon', value: kpis.expiring_soon_count || 0, icon: 'ti-clock-alert', color: 'info' },
    ];

    const columns = [
        { key: 'product.sku', label: 'SKU', render: (row) => row.product?.sku || '-' },
        { key: 'product.name', label: 'Product Name', render: (row) => row.product?.name || '-' },
        { key: 'product.category.name', label: 'Category', render: (row) => row.product?.category?.name || '-' },
        { key: 'product.brand.name', label: 'Brand', render: (row) => row.product?.brand?.name || '-' },
        { key: 'stock_in_hand', label: 'Stock in Hand' },
        { key: 'product.min_stock_qty', label: 'Min Stock', render: (row) => row.product?.min_stock_qty || 0 },
        { key: 'product.reorder_qty', label: 'Reorder Qty', render: (row) => row.product?.reorder_qty || 0 },
        {
            key: 'stock_value',
            label: 'Stock Value',
            render: (row) => {
                const cost = parseFloat(row.product?.price?.cost_price_item || 0);
                const value = (row.stock_in_hand || 0) * cost;
                return `$${value.toFixed(2)}`;
            }
        },
        { key: 'lot_no', label: 'Batch/Lot No', render: (row) => row.lot_no || '-' },
        {
            key: 'status',
            label: 'Status',
            render: (row) => {
                let status = 'Normal';
                let color = 'success';
                const stock = row.stock_in_hand || 0;
                const minStock = row.product?.min_stock_qty || 0;

                if (stock <= 0) {
                    status = 'Out of Stock';
                    color = 'danger';
                } else if (stock <= minStock) {
                    status = 'Low Stock';
                    color = 'warning';
                }

                return <span className={`badge bg-${color}`}>{status}</span>;
            }
        },
    ];

    return (
        <div className="stock-report">
            <ReportFilters
                filters={stockFilters}
                values={filters}
                onChange={handleFilterChange}
            />

            <KPICards data={kpiData} />

            <div className="card">
                <div className="card-body">
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h4 className="card-title">Stock Report Data</h4>
                        <button className="btn btn-secondary me-2" onClick={handlePrint}>
                            <i className="ti ti-printer me-1"></i> Print
                        </button>
                        <button className="btn btn-success" onClick={handleExport}>
                            <i className="ti ti-download me-1"></i> Export CSV
                        </button>
                    </div>

                    <DataTable
                        columns={columns}
                        data={data}
                        loading={loading}
                        pagination={pagination}
                        onPageChange={fetchReport}
                    />
                </div>
            </div>
        </div>
    );
};

export default StockReport;
