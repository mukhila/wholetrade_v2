import axios from "axios";

document.addEventListener("DOMContentLoaded", function () {
    console.log("Price Updates Script Loaded");

    const tableBody = document.getElementById("priceTableBody");
    const loadingIndicator = document.getElementById("loadingIndicator");
    const tableContainer = document.getElementById("priceTableContainer");
    const btnSave = document.getElementById("btnSave");

    // State
    let products = [];

    // Guard Clause to prevent running on other pages
    if (!tableBody || !loadingIndicator) return;

    // 1. Fetch Data
    function loadProducts() {
        if (!loadingIndicator) return; // Guard clause
        loadingIndicator.classList.remove('d-none');
        tableContainer.classList.add('d-none');

        axios.get('/api/price-updates/products')
            .then(response => {
                products = response.data.map(p => ({ ...p, isModified: false }));
                renderTable();
                loadingIndicator.classList.add('d-none');
                tableContainer.classList.remove('d-none');
                btnSave.disabled = false;
            })
            .catch(error => {
                console.error(error);
                alert('Error loading products: ' + (error.response?.data?.message || error.message));
                loadingIndicator.classList.add('d-none');
            });
    }

    // 2. Render Table
    function renderTable() {
        let rowsHtml = '';
        if (products.length === 0) {
            rowsHtml = `<tr><td colspan="8" class="text-center text-muted py-3">No products found.</td></tr>`;
        } else {
            products.forEach((p, index) => {
                rowsHtml += `<tr>
                    <td>${p.itemCode}</td>
                    <td>
                        <div class="fw-semibold">${p.name}</div>
                        <small class="text-muted" style="font-size: 0.75rem;">${p.brand || '-'} | ${p.category || '-'}</small>
                    </td>
                    
                    <td class="text-end bg-light align-middle">${p.oldPrices.unit_price}</td>
                    <td class="text-end bg-light align-middle">${p.oldPrices.box_price}</td>
                    <td class="text-end bg-light align-middle">${p.oldPrices.pos_price}</td>
                    
                    <td><input type="number" step="0.01" class="form-control form-control-sm text-end" value="${p.newPrices.unit_price}" onchange="window.updateProductPrice(${index}, 'unit_price', this.value)"></td>
                    <td><input type="number" step="0.01" class="form-control form-control-sm text-end" value="${p.newPrices.box_price}" onchange="window.updateProductPrice(${index}, 'box_price', this.value)"></td>
                    <td><input type="number" step="0.01" class="form-control form-control-sm text-end" value="${p.newPrices.pos_price}" onchange="window.updateProductPrice(${index}, 'pos_price', this.value)"></td>
                </tr>`;
            });
        }
        tableBody.innerHTML = rowsHtml;
    }

    // 3. Update Logic (Global)
    window.updateProductPrice = function (index, field, value) {
        if (!products[index]) return;
        products[index].isModified = true;
        products[index].newPrices[field] = value;
    };

    // 4. Save Logic
    if (btnSave) {
        btnSave.addEventListener('click', function () {
            const modifiedProducts = products.filter(p => p.isModified);

            if (modifiedProducts.length === 0) {
                alert('No changes to save.');
                return;
            }

            // Validation (Client Side)
            /*
            for (let p of modifiedProducts) {
                 if (p.newPrices.unit_price < 0 || p.newPrices.box_price < 0 ...) {
                     alert("Negative prices not allowed"); return;
                 }
            }
            */

            btnSave.disabled = true;
            const originalText = btnSave.innerHTML;
            btnSave.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...`;

            axios.post('/api/price-updates/save', {
                prices: modifiedProducts
            })
                .then(response => {
                    alert('Price updates saved successfully!');
                    loadProducts(); // Reload
                })
                .catch(error => {
                    console.error(error);
                    alert('Error saving prices: ' + (error.response?.data?.error || error.message));
                })
                .finally(() => {
                    btnSave.disabled = false;
                    btnSave.innerHTML = originalText;
                });
        });
    }

    // Initialize
    loadProducts();
});
