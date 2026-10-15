import React, { useRef, useEffect } from 'react';

const Select2 = ({ value, onChange, placeholder, ajaxUrl }) => {
    const selectRef = useRef(null);

    // Initialise Select2
    useEffect(() => {
        const $ = window.jQuery;
        if (!$ || !selectRef.current) return;

        const el = $(selectRef.current);

        el.select2({
            placeholder: placeholder || 'Select Customer...',
            allowClear: true,
            width: '100%',
            theme: 'bootstrap-5',
            dropdownParent: $(document.body),
            minimumInputLength: 0,
            ajax: {
                url: ajaxUrl,
                dataType: 'json',
                delay: 300,
                data: (params) => ({ term: params.term || '' }),
                processResults: (data) => ({ results: data.results }),
                cache: true,
            },
        });

        el.on('select2:select', function () {
            onChange($(this).val());
        });

        el.on('select2:unselect select2:clear', function () {
            onChange('');
        });

        return () => {
            el.off('select2:select select2:unselect select2:clear');
            if (el.data('select2')) {
                el.select2('destroy');
            }
        };
    }, []); // eslint-disable-line react-hooks/exhaustive-deps

    // Sync value from parent (e.g. reset filters)
    useEffect(() => {
        const $ = window.jQuery;
        if (!$ || !selectRef.current) return;
        const el = $(selectRef.current);
        if (!el.data('select2')) return;

        if (!value || value === '') {
            el.val(null).trigger('change.select2');
        }
    }, [value]);

    return (
        // Empty select — Select2 AJAX populates it dynamically
        <select ref={selectRef} className="form-select">
            <option value=""></option>
        </select>
    );
};

export default Select2;
