import React, { useState, useEffect } from 'react';
import axios from 'axios';
import ReportFilters from './ReportFilters';
import KPICards from './KPICards';
import DataTable from './DataTable';

const ProductReport = ({ filterOptions }) => {
    const [loading, setLoading] = useState(false);
    const [data, setData] = useState([]);
    const [pagination, setPagination] = useState({});
    const [kpis, setKpis] = useState({});
    const [filters, setFilters] = useState({
        category_id: '',
        brand_id: '',
        supplier_id: '',
        status: '',
        date_from: '',
        date_to: '',
        search: '',
        per_page: 15,
    });

    useEffect(() => {
        fetchReport();
    }, [filters]);

    const fetchReport = async (page = 1) => {
        setLoading(true);
        try {
            const response = await axios.get('/api/reports/products', {
                params: { ...filters, page }
            });
            setData(response.data.data);
            setPagination(response.data.pagination);
            setKpis(response.data.kpis);
        } catch (error) {
            console.error('Error fetching product report:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleFilterChange = (key, value) => {
        setFilters(prev => ({ ...prev, [key]: value }));
    };

    const handlePrint = () => {
        const params = new URLSearchParams(filters).toString();
        window.open(`/reports/products/print?${params}`, '_blank');
    };

    const handleExport = () => {
        window.location.href = '/api/reports/products/export?' + new URLSearchParams(filters).toString();
    };

    const productFilters = [
        { type: 'select', label: 'Category', key: 'category_id', options: filterOptions.categories },
        { type: 'select', label: 'Brand', key: 'brand_id', options: filterOptions.brands },
        { type: 'select', label: 'Supplier', key: 'supplier_id', options: filterOptions.suppliers },
        {
            type: 'select', label: 'Status', key: 'status', options: [
                { id: 'Active', name: 'Active' },
                { id: 'Inactive', name: 'Inactive' }
            ]
        },
        { type: 'date', label: 'From Date', key: 'date_from' },
        { type: 'date', label: 'To Date', key: 'date_to' },
        { type: 'text', label: 'Search', key: 'search', placeholder: 'Search by SKU or Name' },
    ];

    const kpiData = [
        { title: 'Total Products', value: kpis.total_products || 0, icon: 'ti-package', color: 'primary' },
        { title: 'Active Products', value: kpis.active_products || 0, icon: 'ti-check-circle', color: 'success' },
        { title: 'Inactive Products', value: kpis.inactive_products || 0, icon: 'ti-x-circle', color: 'danger' },
        { title: 'High Margin (>30%)', value: kpis.high_margin_products || 0, icon: 'ti-trending-up', color: 'info' },
        { title: 'Low Margin (<10%)', value: kpis.low_margin_products || 0, icon: 'ti-trending-down', color: 'warning' },
    ];

    const columns = [
        { key: 'sku', label: 'SKU' },
        { key: 'name', label: 'Product Name' },
        { key: 'category.name', label: 'Category', render: (row) => row.category?.name || '-' },
        { key: 'brand.name', label: 'Brand', render: (row) => row.brand?.name || '-' },
        { key: 'supplier.name', label: 'Supplier', render: (row) => row.supplier?.name || '-' },
        { key: 'uom.name', label: 'UOM', render: (row) => row.uom?.name || '-' },
        {
            key: 'cost_price_item',
            label: 'Cost Price (Item)',
            render: (row) => row.price?.cost_price_item || '0.00'
        },
        {
            key: 'cost_price_box',
            label: 'Cost Price (Box)',
            render: (row) => row.price?.cost_price_box || '0.00'
        },
        {
            key: 'sales_price_item',
            label: 'Sales Price (Item)',
            render: (row) => row.price?.sales_price_item || '0.00'
        },
        {
            key: 'sales_price_box',
            label: 'Sales Price (Box)',
            render: (row) => row.price?.sales_price_box || '0.00'
        },
        {
            key: 'retail_price',
            label: 'Retail Price',
            render: (row) => row.price?.retail_price_item || '0.00'
        },
        {
            key: 'margin',
            label: 'Margin % (Item)',
            render: (row) => {
                const cost = parseFloat(row.price?.cost_price_item || 0);
                const sales = parseFloat(row.price?.sales_price_item || 0);
                const margin = cost > 0 ? ((sales - cost) / cost * 100).toFixed(2) : '0.00';
                return `${margin}%`;
            }
        },
        {
            key: 'status',
            label: 'Status',
            render: (row) => (
                <span className={`badge ${row.status ? 'bg-success' : 'bg-danger'}`}>
                    {row.status ? 'Active' : 'Inactive'}
                </span>
            )
        },
    ];

    return (
        <div className="product-report">
            <ReportFilters
                filters={productFilters}
                values={filters}
                onChange={handleFilterChange}
            />

            <KPICards data={kpiData} />

            <div className="card">
                <div className="card-body">
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h4 className="card-title">Product Report Data</h4>
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

export default ProductReport;
