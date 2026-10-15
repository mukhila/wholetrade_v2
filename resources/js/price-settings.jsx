import axios from "axios";

document.addEventListener("DOMContentLoaded", function () {
    console.log("Price Settings Script Loaded");

    // DOM References
    const tableBody = document.getElementById("priceTableBody");
    const tableHeader = document.getElementById("tableHeaders");
    const priceTypeSelect = document.getElementById("priceType");
    const associateFilter = document.getElementById("associateFilter");
    const groupFilter = document.getElementById("groupFilter");
    const btnSave = document.getElementById("btnSave");
    const btnFilter = document.getElementById("btnFilter");
    const btnReset = document.getElementById("btnReset");

    // Early exit if required elements not found
    if (!btnFilter || !tableBody || !tableHeader) {
        console.error("Required DOM elements not found");
        return;
    }

    // Initialize Select2
    if (window.$ && window.$.fn.select2) {
        $('#associateId').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Associate',
            allowClear: true,
            width: '100%'
        });
    }

    // State
    let products = [];
    let priceType = 'STANDARD';

    // ============================================
    // SINGLE INITIALIZATION FUNCTION
    // Used by both initial load AND reset button
    // ============================================
    function initPage({ forceStandard = false } = {}) {
        console.log("Initializing page...");

        if (forceStandard) {
            priceType = 'STANDARD';
            if (priceTypeSelect) priceTypeSelect.value = 'STANDARD';
        } else {
            priceType = priceTypeSelect ? priceTypeSelect.value : 'STANDARD';
        }

        toggleFilters();
        resetValues();

        products = [];
        tableBody.innerHTML = '';

        window.currentPage = 1;
        const paginationWrapper = document.getElementById('paginationWrapper');
        if (paginationWrapper) paginationWrapper.innerHTML = '';

        renderTableHeaders();
        loadPrices();
    }


    // ============================================
    // UI HELPER FUNCTIONS
    // ============================================
    function toggleFilters() {
        if (associateFilter) {
            associateFilter.style.display = (priceType === 'ASSOCIATE') ? 'block' : 'none';
        }
        if (groupFilter) {
            groupFilter.style.display = (priceType === 'GROUP') ? 'block' : 'none';
        }
    }

    function resetValues() {
        const assocId = document.getElementById('associateId');
        const grpId = document.getElementById('groupId');
        const catId = document.getElementById('categoryId');
        const brandId = document.getElementById('brandId');

        if (assocId) {
            assocId.value = "";
            if (window.$ && $(assocId).data('select2')) {
                $(assocId).val('').trigger('change');
            }
        }
        if (grpId) grpId.value = "";
        if (catId) catId.value = "";
        if (brandId) brandId.value = "";
    }

    // ============================================
    // EVENT LISTENERS
    // ============================================

    // Price Type Change - Update UI but don't auto-load
    if (priceTypeSelect) {
        priceTypeSelect.addEventListener('change', function () {
            priceType = this.value;
            toggleFilters();
            resetValues();
            renderTableHeaders();
            tableBody.innerHTML = '';
            products = [];
            window.currentPage = 1;
        });
    }

    // Filter Button - Load data with current filters
    btnFilter.addEventListener('click', function (e) {
        e.preventDefault();
        console.log("Filter button clicked");
        window.currentPage = 1;
        loadPrices();
    });

    // Reset Button - Use same initPage() as initial load
    if (btnReset) {
        btnReset.addEventListener('click', function (e) {
            e.preventDefault();
            console.log("Reset button clicked");
            initPage({ forceStandard: true });
        });
    }

    // Save Button
    if (btnSave) {
        btnSave.addEventListener('click', function (e) {
            e.preventDefault();
            savePrices();
        });
    }

    // ============================================
    // DATA FUNCTIONS
    // ============================================
    function loadPrices() {
        console.log("Loading prices... priceType:", priceType);

        // Validation for context-specific types
        const associateId = document.getElementById('associateId')?.value || '';
        const groupId = document.getElementById('groupId')?.value || '';
        const brandId = document.getElementById('brandId')?.value || '';
        const categoryId = document.getElementById('categoryId')?.value || '';

        if (priceType === 'ASSOCIATE' && !associateId) {
            alert('Please select an Associate.');
            return;
        }
        if (priceType === 'GROUP' && !groupId) {
            alert('Please select an Associate Group.');
            return;
        }

        const loader = document.getElementById('loadingIndicator');
        const tableContainer = document.getElementById('priceTableContainer');
        const paginationWrapper = document.getElementById('paginationWrapper');

        // Show loading state
        if (loader) loader.classList.remove('d-none');
        if (tableContainer) tableContainer.classList.add('d-none');
        if (paginationWrapper) paginationWrapper.innerHTML = '';
        tableBody.innerHTML = '';

        axios.get('/api/price-settings/prices', {
            params: {
                priceType,
                associateId,
                groupId,
                brandId,
                categoryId,
                page: window.currentPage || 1
            }
        })
            .then(response => {
                console.log("Prices loaded:", response.data.data.length);
                products = response.data.data.map(p => ({ ...p, isModified: false }));
                window.pagination = response.data;

                // Hide loader and show container BEFORE rendering
                // This ensures browser calculates layout for inputs correctly
                if (loader) loader.classList.add('d-none');
                if (tableContainer) tableContainer.classList.remove('d-none');

                // Render after container is visible to ensure proper layout
                renderTable();
                renderPagination();
            })
            .catch(error => {
                console.error(error);
                alert('Error loading prices: ' + (error.response?.data?.message || error.message));
                // Also show container on error
                if (loader) loader.classList.add('d-none');
                if (tableContainer) tableContainer.classList.remove('d-none');
            });
    }

    function savePrices() {
        const modifiedProducts = products.filter(p => p.isModified);

        if (modifiedProducts.length === 0) {
            alert('No changes to save.');
            return;
        }

        btnSave.disabled = true;
        const originalText = btnSave.innerHTML;
        btnSave.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...`;

        axios.post('/api/price-settings/save', {
            priceType,
            associateId: document.getElementById('associateId').value,
            groupId: document.getElementById('groupId').value,
            prices: modifiedProducts
        })
            .then(response => {
                alert('Prices Saved Successfully!');
                loadPrices();
            })
            .catch(error => {
                console.error(error);
                alert('Error saving prices: ' + (error.response?.data?.error || error.message));
            })
            .finally(() => {
                btnSave.disabled = false;
                btnSave.innerHTML = originalText;
            });
    }

    // ============================================
    // RENDER FUNCTIONS
    // ============================================
    function renderTableHeaders() {
        let headerHtml = `
            <th>Code</th>
            <th>Name</th>
        `;

        if (priceType !== 'PROMOTION') {
            headerHtml += `
                <th>Cost (Item)</th>
                <th>Cost (Box)</th>
                <th>Old Unit</th>
                <th>Old Box</th>
                <th>Old Price</th>
                <th>Old POS</th>
                <th class="bg-primary-subtle" style="width: 100px;">New Unit</th>
                <th class="bg-primary-subtle" style="width: 100px;">New Box</th>
                <th class="bg-primary-subtle" style="width: 100px;">New Price</th>
                <th class="bg-primary-subtle" style="width: 100px;">New POS</th>
            `;
        } else {
            headerHtml += `
                <th>Std Unit</th>
                <th>Min Qty</th>
                <th>Max Qty</th>
                <th class="bg-warning-subtle">Promo Unit</th>
                <th class="bg-warning-subtle">Promo Box</th>
                <th class="bg-warning-subtle">Promo POS</th>
                <th>Start Date</th>
                <th>End Date</th>
            `;
        }
        tableHeader.innerHTML = headerHtml;
    }

    function renderTable() {
        // Ensure headers match current priceType
        //renderTableHeaders();

        // Render rows
        let rowsHtml = '';
        if (products.length === 0) {
            const colSpan = priceType === 'PROMOTION' ? 10 : 12;
            rowsHtml = `<tr><td colspan="${colSpan}" class="text-center text-muted py-3">No products found matching the criteria.</td></tr>`;
        } else {
            products.forEach((p, index) => {
                rowsHtml += `<tr>
                    <td>${escapeHtml(p.itemCode)}</td>
                    <td>
                        <div class="fw-semibold">${escapeHtml(p.name)}</div>
                        <small class="text-muted" style="font-size: 0.75rem;">${escapeHtml(p.brand || '-')} | ${escapeHtml(p.category || '-')}</small>
                    </td>
                `;

                if (priceType !== 'PROMOTION') {
                    rowsHtml += `
                        <td class="text-end align-middle font-monospace text-muted">${p.oldPrices.cost_price_item || '0'}</td>
                        <td class="text-end align-middle font-monospace text-muted">${p.oldPrices.cost_price_box || '0'}</td>

                        <td class="text-end bg-light align-middle">${p.oldPrices.unit_price}</td>
                        <td class="text-end bg-light align-middle">${p.oldPrices.box_price}</td>
                        <td class="text-end bg-light align-middle">${p.oldPrices.price}</td>
                        <td class="text-end bg-light align-middle">${p.oldPrices.pos_price}</td>

                        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" value="${p.newPrices.unit_price}" onchange="window.updateProduct(${index}, 'newPrices', 'unit_price', this.value)"></td>
                        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" value="${p.newPrices.box_price}" onchange="window.updateProduct(${index}, 'newPrices', 'box_price', this.value)"></td>
                        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" value="${p.newPrices.price}" onchange="window.updateProduct(${index}, 'newPrices', 'price', this.value)"></td>
                        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" value="${p.newPrices.pos_price}" onchange="window.updateProduct(${index}, 'newPrices', 'pos_price', this.value)"></td>
                    `;
                } else {
                    rowsHtml += `
                         <td class="text-end align-middle">${p.oldPrices.unit_price}</td>
                         <td><input type="number" min="1" class="form-control form-control-sm" style="width: 60px" value="${p.promotion.minQty}" onchange="window.updateProduct(${index}, 'promotion', 'minQty', this.value)"></td>
                         <td><input type="number" min="1" class="form-control form-control-sm" style="width: 60px" value="${p.promotion.maxQty || ''}" onchange="window.updateProduct(${index}, 'promotion', 'maxQty', this.value)"></td>

                         <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" value="${p.promotion.unit || ''}" placeholder="${p.oldPrices.unit_price}" onchange="window.updateProduct(${index}, 'promotion', 'unit', this.value)"></td>
                         <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" value="${p.promotion.box || ''}" onchange="window.updateProduct(${index}, 'promotion', 'box', this.value)"></td>
                         <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" value="${p.promotion.pos || ''}" onchange="window.updateProduct(${index}, 'promotion', 'pos', this.value)"></td>

                         <td><input type="date" class="form-control form-control-sm" value="${p.promotion.startDate || ''}" onchange="window.updateProduct(${index}, 'promotion', 'startDate', this.value)"></td>
                         <td><input type="date" class="form-control form-control-sm" value="${p.promotion.endDate || ''}" onchange="window.updateProduct(${index}, 'promotion', 'endDate', this.value)"></td>
                    `;
                }

                rowsHtml += `</tr>`;
            });
        }
        tableBody.innerHTML = rowsHtml;
    }

    function renderPagination() {
        const wrapper = document.getElementById('paginationWrapper');
        if (!wrapper || !window.pagination) return;

        const { current_page, last_page, total } = window.pagination;

        let html = `
            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-muted">Showing page ${current_page} of ${last_page} (${total} items)</span>
                <div>
                    <button class="btn btn-sm btn-outline-secondary me-1" ${current_page === 1 ? 'disabled' : ''} onclick="window.changePage(${current_page - 1})">Previous</button>
                    <button class="btn btn-sm btn-outline-secondary" ${current_page === last_page ? 'disabled' : ''} onclick="window.changePage(${current_page + 1})">Next</button>
                </div>
            </div>
        `;
        wrapper.innerHTML = html;
    }

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ============================================
    // GLOBAL FUNCTIONS (for inline event handlers)
    // ============================================
    window.changePage = function (page) {
        window.currentPage = page;
        loadPrices();
    };

    window.updateProduct = function (index, parentField, field, value) {
        if (!products[index]) return;

        products[index].isModified = true;
        if (parentField === 'newPrices') {
            products[index].newPrices[field] = value;
        } else if (parentField === 'promotion') {
            products[index].promotion[field] = value;
        }
    };

    // ============================================
    // INITIALIZE PAGE
    // Single entry point - same as Reset button
    // ============================================
    initPage();
});
