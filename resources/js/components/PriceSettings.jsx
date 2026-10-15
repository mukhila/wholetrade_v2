import React, { useState, useEffect } from 'react';
import axios from 'axios';

const PriceSettings = () => {
    // Master Data States
    const [categories, setCategories] = useState([]);
    const [brands, setBrands] = useState([]);
    const [associateGroups, setAssociateGroups] = useState([]);
    const [associates, setAssociates] = useState([]);

    // Filter States
    const [filters, setFilters] = useState({
        priceType: 'STANDARD', // STANDARD, ASSOCIATE, GROUP, PROMOTION
        categoryId: '',
        brandId: '',
        associateId: '',
        groupId: '',
    });

    // Data State
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState('');

    // Fetch Master Data
    useEffect(() => {
        axios.get('/api/price-settings/master-data')
            .then(res => {
                setCategories(res.data.categories);
                setBrands(res.data.brands);
                setAssociateGroups(res.data.associateGroups);
                setAssociates(res.data.associates);
            })
            .catch(err => console.error(err));
    }, []);

    // Load Prices
    const loadPrices = () => {
        setLoading(true);
        axios.get('/api/price-settings/prices', { params: filters })
            .then(res => {
                setProducts(res.data);
                setLoading(false);
            })
            .catch(err => {
                console.error(err);
                setLoading(false);
            });
    };

    // Trigger load on filter change (debounce could be added, or explicit button)
    useEffect(() => {
        // Only load if valid context for types
        if (filters.priceType === 'ASSOCIATE' && !filters.associateId) return;
        if (filters.priceType === 'GROUP' && !filters.groupId) return;

        loadPrices();
    }, [filters.priceType, filters.categoryId, filters.brandId, filters.associateId, filters.groupId]);

    // Handle Input Change
    const handlePriceChange = (productId, field, value) => {
        setProducts(prev => prev.map(p => {
            if (p.productId === productId) {
                return {
                    ...p,
                    newPrices: {
                        ...p.newPrices,
                        [field]: value
                    },
                    isModified: true
                };
            }
            return p;
        }));
    };

    const handlePromotionChange = (productId, field, value) => {
        setProducts(prev => prev.map(p => {
            if (p.productId === productId) {
                return {
                    ...p,
                    promotion: {
                        ...p.promotion,
                        [field]: value
                    },
                    isModified: true
                };
            }
            return p;
        }));
    };

    // Save
    const handleSave = () => {
        const modified = products.filter(p => p.isModified);
        if (modified.length === 0) {
            setMessage('No changes to save.');
            return;
        }

        setSaving(true);
        axios.post('/api/price-settings/save', {
            priceType: filters.priceType,
            associateId: filters.associateId,
            groupId: filters.groupId,
            prices: modified
        })
            .then(res => {
                setSaving(false);
                setMessage('Prices saved successfully!');
                // Reload to reset 'isModified' and get latest state
                loadPrices();
                setTimeout(() => setMessage(''), 3000);
            })
            .catch(err => {
                setSaving(false);
                setMessage('Error saving prices: ' + (err.response?.data?.error || err.message));
            });
    };

    // Render Logic
    return (
        <div className="p-4">
            {/* Filters */}
            <div className="card mb-4">
                <div className="card-body">
                    <div className="row g-3">
                        <div className="col-md-3">
                            <label className="form-label">Pricing Type</label>
                            <select
                                className="form-select"
                                value={filters.priceType}
                                onChange={e => setFilters({ ...filters, priceType: e.target.value, associateId: '', groupId: '' })}
                            >
                                <option value="STANDARD">Standard Price</option>
                                <option value="ASSOCIATE">Associate Price</option>
                                <option value="GROUP">Group Price</option>
                                <option value="PROMOTION">Promotion</option>
                            </select>
                        </div>

                        {filters.priceType === 'ASSOCIATE' && (
                            <div className="col-md-3">
                                <label className="form-label">Associate</label>
                                <select
                                    className="form-select"
                                    value={filters.associateId}
                                    onChange={e => setFilters({ ...filters, associateId: e.target.value })}
                                >
                                    <option value="">Select Associate</option>
                                    {associates.map(a => <option key={a.id} value={a.id}>{a.name} ({a.code})</option>)}
                                </select>
                            </div>
                        )}

                        {filters.priceType === 'GROUP' && (
                            <div className="col-md-3">
                                <label className="form-label">Associate Group</label>
                                <select
                                    className="form-select"
                                    value={filters.groupId}
                                    onChange={e => setFilters({ ...filters, groupId: e.target.value })}
                                >
                                    <option value="">Select Group</option>
                                    {associateGroups.map(g => <option key={g.id} value={g.id}>{g.name}</option>)}
                                </select>
                            </div>
                        )}

                        <div className="col-md-2">
                            <label className="form-label">Category</label>
                            <select
                                className="form-select"
                                value={filters.categoryId}
                                onChange={e => setFilters({ ...filters, categoryId: e.target.value })}
                            >
                                <option value="">All Categories</option>
                                {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                            </select>
                        </div>

                        <div className="col-md-2">
                            <label className="form-label">Brand</label>
                            <select
                                className="form-select"
                                value={filters.brandId}
                                onChange={e => setFilters({ ...filters, brandId: e.target.value })}
                            >
                                <option value="">All Brands</option>
                                {brands.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {/* Message */}
            {message && <div className={`alert ${message.includes('Error') ? 'alert-danger' : 'alert-success'}`}>{message}</div>}

            {/* Grid */}
            <div className="card">
                <div className="card-body">
                    {loading ? (
                        <div className="text-center p-4">Loading...</div>
                    ) : (
                        <div className="table-responsive">
                            <table className="table table-bordered table-sm align-middle">
                                <thead className="table-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        {filters.priceType !== 'PROMOTION' ? (
                                            <>
                                                <th>Old Unit</th>
                                                <th>Old Box</th>
                                                <th>Old POS</th>
                                                <th className="bg-primary-subtle">New Unit</th>
                                                <th className="bg-primary-subtle">New Box</th>
                                                <th className="bg-primary-subtle">New POS</th>
                                                {/* <th className="bg-primary-subtle">New Price</th> */}
                                            </>
                                        ) : (
                                            <>
                                                <th>Standard Unit</th>
                                                <th>Min Qty</th>
                                                <th>Max Qty</th>
                                                <th className="bg-warning-subtle">Promo Unit</th>
                                                <th className="bg-warning-subtle">Promo Box</th>
                                                <th className="bg-warning-subtle">Promo POS</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                            </>
                                        )}
                                    </tr>
                                </thead>
                                <tbody>
                                    {products.map(p => (
                                        <tr key={p.productId}>
                                            <td>{p.itemCode}</td>
                                            <td>
                                                <div>{p.name}</div>
                                                <small className="text-muted">{p.brand} | {p.category}</small>
                                            </td>

                                            {filters.priceType !== 'PROMOTION' ? (
                                                <>
                                                    <td className="text-end bg-light">{p.oldPrices.unit_price}</td>
                                                    <td className="text-end bg-light">{p.oldPrices.box_price}</td>
                                                    <td className="text-end bg-light">{p.oldPrices.pos_price}</td>

                                                    <td>
                                                        <input type="number" className="form-control form-control-sm text-end"
                                                            value={p.newPrices.unit_price}
                                                            onChange={e => handlePriceChange(p.productId, 'unit_price', e.target.value)}
                                                        />
                                                    </td>
                                                    <td>
                                                        <input type="number" className="form-control form-control-sm text-end"
                                                            value={p.newPrices.box_price}
                                                            onChange={e => handlePriceChange(p.productId, 'box_price', e.target.value)}
                                                        />
                                                    </td>
                                                    <td>
                                                        <input type="number" className="form-control form-control-sm text-end"
                                                            value={p.newPrices.pos_price}
                                                            onChange={e => handlePriceChange(p.productId, 'pos_price', e.target.value)}
                                                        />
                                                    </td>
                                                    {/* <td>
                                                        <input type="number" className="form-control form-control-sm text-end"
                                                            value={p.newPrices.price}
                                                            onChange={e => handlePriceChange(p.productId, 'price', e.target.value)}
                                                        />
                                                    </td> */}
                                                </>
                                            ) : (
                                                <>
                                                    <td className="text-end">{p.oldPrices.unit_price}</td>
                                                    <td>
                                                        <input type="number" className="form-control form-control-sm" style={{ width: '60px' }}
                                                            value={p.promotion.minQty}
                                                            onChange={e => handlePromotionChange(p.productId, 'minQty', e.target.value)}
                                                        />
                                                    </td>
                                                    <td>
                                                        <input type="number" className="form-control form-control-sm" style={{ width: '60px' }}
                                                            value={p.promotion.maxQty || ''}
                                                            onChange={e => handlePromotionChange(p.productId, 'maxQty', e.target.value)}
                                                        />
                                                    </td>
                                                    <td>
                                                        <input type="number" className="form-control form-control-sm text-end"
                                                            value={p.promotion.unit || ''}
                                                            onChange={e => handlePromotionChange(p.productId, 'unit', e.target.value)}
                                                            placeholder={p.oldPrices.unit_price}
                                                        />
                                                    </td>
                                                    <td>
                                                        <input type="number" className="form-control form-control-sm text-end"
                                                            value={p.promotion.box || ''}
                                                            onChange={e => handlePromotionChange(p.productId, 'box', e.target.value)}
                                                        />
                                                    </td>
                                                    <td>
                                                        <input type="number" className="form-control form-control-sm text-end"
                                                            value={p.promotion.pos || ''}
                                                            onChange={e => handlePromotionChange(p.productId, 'pos', e.target.value)}
                                                        />
                                                    </td>
                                                    <td>
                                                        <input type="date" className="form-control form-control-sm"
                                                            value={p.promotion.startDate || ''}
                                                            onChange={e => handlePromotionChange(p.productId, 'startDate', e.target.value)}
                                                        />
                                                    </td>
                                                    <td>
                                                        <input type="date" className="form-control form-control-sm"
                                                            value={p.promotion.endDate || ''}
                                                            onChange={e => handlePromotionChange(p.productId, 'endDate', e.target.value)}
                                                        />
                                                    </td>
                                                </>
                                            )}
                                        </tr>
                                    ))}
                                    {products.length === 0 && (
                                        <tr>
                                            <td colSpan="10" className="text-center text-muted py-4">
                                                No products found or invalid context (Select Associate/Group).
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
                <div className="card-footer text-end">
                    <button className="btn btn-primary" onClick={handleSave} disabled={saving || loading}>
                        {saving ? 'Saving...' : 'Save All Price Changes'}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default PriceSettings;
