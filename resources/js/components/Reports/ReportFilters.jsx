import React from 'react';
import CustomerAutocomplete from './CustomerAutocomplete';

const ReportFilters = ({ filters, values, onChange }) => {
    const handleReset = () => {
        filters.forEach(f => onChange(f.key, f.key === 'source' ? 'all' : ''));
    };

    return (
        <div className="card mb-3">
            <div className="card-body">
                <h5 className="card-title mb-3">Filters</h5>
                <div className="row g-3">
                    {filters.map((filter) => (
                        <div key={filter.key} className="col-md-3">
                            <label className="form-label">{filter.label}</label>

                            {filter.type === 'select' && (
                                <select
                                    className="form-select"
                                    value={values[filter.key]}
                                    onChange={(e) => onChange(filter.key, e.target.value)}
                                >
                                    <option value="">All</option>
                                    {filter.options?.map((option) => (
                                        <option key={option.id} value={option.id}>
                                            {option.name}
                                        </option>
                                    ))}
                                </select>
                            )}

                            {filter.type === 'autocomplete' && (
                                <CustomerAutocomplete
                                    value={values[filter.key]}
                                    onChange={(val) => onChange(filter.key, val)}
                                    placeholder={`Search ${filter.label}...`}
                                    // key forces remount when value is reset to ''
                                    key={values[filter.key] ? filter.key : `${filter.key}-empty`}
                                />
                            )}

                            {filter.type === 'date' && (
                                <input
                                    type="date"
                                    className="form-control"
                                    value={values[filter.key]}
                                    onChange={(e) => onChange(filter.key, e.target.value)}
                                />
                            )}

                            {filter.type === 'text' && (
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder={filter.placeholder}
                                    value={values[filter.key]}
                                    onChange={(e) => onChange(filter.key, e.target.value)}
                                />
                            )}
                        </div>
                    ))}

                    <div className="col-md-3 d-flex align-items-end">
                        <button className="btn btn-secondary w-100" onClick={handleReset}>
                            <i className="ti ti-refresh me-1"></i> Reset Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ReportFilters;
