import React, { useState, useEffect, useRef, useCallback } from 'react';
import axios from 'axios';

/**
 * Pure React customer autocomplete — no jQuery, no Select2 CDN dependency.
 * Works on any server (local or live).
 */
const CustomerAutocomplete = ({ value, onChange, placeholder = 'Search customer...' }) => {
    const [query, setQuery]           = useState('');
    const [results, setResults]       = useState([]);
    const [open, setOpen]             = useState(false);
    const [loading, setLoading]       = useState(false);
    const [selectedLabel, setSelectedLabel] = useState('');

    const wrapperRef  = useRef(null);
    const inputRef    = useRef(null);
    const debounceRef = useRef(null);

    // ── Fetch from API ────────────────────────────────────────────────────────
    const fetchResults = useCallback(async (term) => {
        setLoading(true);
        try {
            const res = await axios.get('/api/associates/search', {
                params: { term: term || '', type: 'Customer' },
            });
            setResults(res.data.results || []);
        } catch {
            setResults([]);
        } finally {
            setLoading(false);
        }
    }, []);

    // Debounce search on query change
    useEffect(() => {
        if (!open) return;
        clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => fetchResults(query), 300);
        return () => clearTimeout(debounceRef.current);
    }, [query, open, fetchResults]);

    // Close dropdown on outside click
    useEffect(() => {
        const handler = (e) => {
            if (wrapperRef.current && !wrapperRef.current.contains(e.target)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    // ── Handlers ──────────────────────────────────────────────────────────────
    const handleFocus = () => {
        setOpen(true);
        fetchResults(query);
    };

    const handleSelect = (item) => {
        setSelectedLabel(item.text);
        setQuery('');
        setOpen(false);
        onChange(String(item.id));
    };

    const handleClear = (e) => {
        e.stopPropagation();
        setSelectedLabel('');
        setQuery('');
        setResults([]);
        onChange('');
    };

    // ── Render ────────────────────────────────────────────────────────────────
    return (
        <div ref={wrapperRef} style={{ position: 'relative' }}>
            {/* Input / selected display */}
            <div className="position-relative">
                <input
                    ref={inputRef}
                    type="text"
                    className="form-control"
                    placeholder={selectedLabel || placeholder}
                    value={selectedLabel ? '' : query}
                    style={selectedLabel ? { color: 'transparent', caretColor: 'transparent' } : {}}
                    onChange={(e) => {
                        if (selectedLabel) return; // locked until cleared
                        setQuery(e.target.value);
                        setOpen(true);
                    }}
                    onFocus={handleFocus}
                    readOnly={!!selectedLabel}
                />
                {/* Display selected name as overlay */}
                {selectedLabel && (
                    <div
                        className="position-absolute top-0 start-0 d-flex align-items-center px-3"
                        style={{ height: '100%', pointerEvents: 'none', width: 'calc(100% - 32px)', overflow: 'hidden', whiteSpace: 'nowrap' }}
                    >
                        <span className="text-truncate">{selectedLabel}</span>
                    </div>
                )}
                {/* Clear (×) button */}
                {selectedLabel && (
                    <button
                        type="button"
                        onMouseDown={handleClear}
                        className="position-absolute top-50 translate-middle-y border-0 bg-transparent text-muted p-0"
                        style={{ right: '10px', fontSize: '1rem', lineHeight: 1, cursor: 'pointer' }}
                        title="Clear"
                    >
                        ×
                    </button>
                )}
            </div>

            {/* Dropdown */}
            {open && (
                <div
                    style={{
                        position: 'absolute', top: 'calc(100% + 2px)',
                        left: 0, right: 0, zIndex: 9999,
                        background: '#fff',
                        border: '1px solid #ced4da',
                        borderRadius: '0.375rem',
                        boxShadow: '0 4px 16px rgba(0,0,0,0.12)',
                        maxHeight: '240px',
                        overflowY: 'auto',
                    }}
                >
                    {loading ? (
                        <div className="py-2 px-3 text-muted small text-center">
                            <span className="spinner-border spinner-border-sm me-1"></span> Searching...
                        </div>
                    ) : results.length > 0 ? (
                        results.map((item) => (
                            <div
                                key={item.id}
                                onMouseDown={() => handleSelect(item)}
                                className="px-3 py-2 cursor-pointer"
                                style={{ cursor: 'pointer' }}
                                onMouseEnter={(e) => e.currentTarget.style.background = '#f0f4ff'}
                                onMouseLeave={(e) => e.currentTarget.style.background = ''}
                            >
                                {item.text}
                            </div>
                        ))
                    ) : (
                        <div className="py-2 px-3 text-muted small text-center">
                            {query.length > 0 ? `No customers found for "${query}"` : 'Type to search customers'}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};

export default CustomerAutocomplete;
