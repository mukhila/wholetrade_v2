import React, { useState, useEffect } from 'react';
import axios from 'axios';
import ProductReport from './ProductReport';
import SalesReport from './SalesReport';
import StockReport from './StockReport';

const ReportsApp = () => {
    const [activeTab, setActiveTab] = useState('products');
    const [filterOptions, setFilterOptions] = useState({
        categories: [],
        brands: [],
        suppliers: [],
        customers: [],
        salespersons: [],
        products: [],
    });

    useEffect(() => {
        fetchFilterOptions();
    }, []);

    const fetchFilterOptions = async () => {
        try {
            const response = await axios.get('/api/reports/filter-options');
            setFilterOptions(response.data);
        } catch (error) {
            console.error('Error fetching filter options:', error);
        }
    };

    return (
        <div className="reports-container">
            {/* Tabs Navigation */}
            <ul className="nav nav-tabs mb-3" role="tablist">
                <li className="nav-item" role="presentation">
                    <button
                        className={`nav-link ${activeTab === 'products' ? 'active' : ''}`}
                        onClick={() => setActiveTab('products')}
                        type="button"
                        role="tab"
                    >
                        <i className="ti ti-package me-1"></i> Product Report
                    </button>
                </li>
                <li className="nav-item" role="presentation">
                    <button
                        className={`nav-link ${activeTab === 'sales' ? 'active' : ''}`}
                        onClick={() => setActiveTab('sales')}
                        type="button"
                        role="tab"
                    >
                        <i className="ti ti-chart-line me-1"></i> Sales Report
                    </button>
                </li>
                <li className="nav-item" role="presentation">
                    <button
                        className={`nav-link ${activeTab === 'stock' ? 'active' : ''}`}
                        onClick={() => setActiveTab('stock')}
                        type="button"
                        role="tab"
                    >
                        <i className="ti ti-box me-1"></i> Stock Report
                    </button>
                </li>
            </ul>

            {/* Tab Content */}
            <div className="tab-content">
                {activeTab === 'products' && (
                    <ProductReport filterOptions={filterOptions} />
                )}
                {activeTab === 'sales' && (
                    <SalesReport filterOptions={filterOptions} />
                )}
                {activeTab === 'stock' && (
                    <StockReport filterOptions={filterOptions} />
                )}
            </div>
        </div>
    );
};

export default ReportsApp;
