import React from 'react';

const DataTable = ({ columns, data, loading, pagination, onPageChange }) => {
    const renderCellValue = (column, row) => {
        if (column.render) {
            return column.render(row);
        }

        const keys = column.key.split('.');
        let value = row;
        for (const key of keys) {
            value = value?.[key];
        }
        return value || '-';
    };

    const renderPagination = () => {
        if (!pagination || !pagination.total) return null;

        const pages = [];
        const currentPage = pagination.current_page;
        const lastPage = pagination.last_page;

        // Always show first page
        pages.push(1);

        // Show pages around current page
        for (let i = Math.max(2, currentPage - 1); i <= Math.min(lastPage - 1, currentPage + 1); i++) {
            if (!pages.includes(i)) {
                pages.push(i);
            }
        }

        // Always show last page
        if (lastPage > 1 && !pages.includes(lastPage)) {
            pages.push(lastPage);
        }

        return (
            <nav aria-label="Page navigation">
                <ul className="pagination pagination-sm justify-content-end mb-0">
                    <li className={`page-item ${currentPage === 1 ? 'disabled' : ''}`}>
                        <button
                            className="page-link"
                            onClick={() => onPageChange(currentPage - 1)}
                            disabled={currentPage === 1}
                        >
                            Previous
                        </button>
                    </li>
                    {pages.map((page, index) => (
                        <React.Fragment key={page}>
                            {index > 0 && pages[index - 1] !== page - 1 && (
                                <li className="page-item disabled">
                                    <span className="page-link">...</span>
                                </li>
                            )}
                            <li className={`page-item ${currentPage === page ? 'active' : ''}`}>
                                <button
                                    className="page-link"
                                    onClick={() => onPageChange(page)}
                                >
                                    {page}
                                </button>
                            </li>
                        </React.Fragment>
                    ))}
                    <li className={`page-item ${currentPage === lastPage ? 'disabled' : ''}`}>
                        <button
                            className="page-link"
                            onClick={() => onPageChange(currentPage + 1)}
                            disabled={currentPage === lastPage}
                        >
                            Next
                        </button>
                    </li>
                </ul>
            </nav>
        );
    };

    return (
        <div>
            <div className="table-responsive">
                <table className="table table-striped table-hover">
                    <thead className="table-light">
                        <tr>
                            {columns.map((column) => (
                                <th key={column.key}>{column.label}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr>
                                <td colSpan={columns.length} className="text-center py-4">
                                    <div className="spinner-border text-primary" role="status">
                                        <span className="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        ) : data.length === 0 ? (
                            <tr>
                                <td colSpan={columns.length} className="text-center py-4">
                                    <p className="text-muted mb-0">No data found</p>
                                </td>
                            </tr>
                        ) : (
                            data.map((row, rowIndex) => (
                                <tr key={rowIndex}>
                                    {columns.map((column) => (
                                        <td key={`${rowIndex}-${column.key}`}>
                                            {renderCellValue(column, row)}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {pagination && pagination.total > 0 && (
                <div className="d-flex justify-content-between align-items-center mt-3">
                    <div className="text-muted">
                        Showing {((pagination.current_page - 1) * pagination.per_page) + 1} to{' '}
                        {Math.min(pagination.current_page * pagination.per_page, pagination.total)} of{' '}
                        {pagination.total} entries
                    </div>
                    {renderPagination()}
                </div>
            )}
        </div>
    );
};

export default DataTable;
