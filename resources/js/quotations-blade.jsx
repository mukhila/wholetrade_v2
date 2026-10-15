import axios from "axios";

document.addEventListener("DOMContentLoaded", function () {

    // Elements
    const productsGrid = document.getElementById('productsGrid');
    const loadingProducts = document.getElementById('loadingProducts');
    const filterCategory = document.getElementById('filterCategory');
    const filterBrand = document.getElementById('filterBrand');
    const searchProduct = document.getElementById('searchProduct');
    const btnSearch = document.getElementById('btnSearch');

    const cartBody = document.getElementById('cartBody');
    const cartTotalDisplay = document.getElementById('cartTotalDisplay');
    const emptyCartRow = document.getElementById('emptyCartRow');
    const quotationForm = document.getElementById('quotationForm');
    const btnSubmit = document.getElementById('btnSubmit');

    // State
    let cart = [];
    let currentPage = 1;

    // --- 1. Product Loading ---

    function loadProducts(page = 1) {
        if (!productsGrid) return;

        loadingProducts.classList.remove('d-none');
        productsGrid.innerHTML = '';

        const params = {
            page: page,
            categoryId: filterCategory.value,
            brandId: filterBrand.value,
            search: searchProduct.value
        };

        axios.get('/api/quotations/products', { params })
            .then(res => {
                const products = res.data.data;
                renderProducts(products);
                // Simple pagination handling if needed, or infinite scroll
                loadingProducts.classList.add('d-none');
            })
            .catch(err => {
                console.error(err);
                productsGrid.innerHTML = '<div class="col-12 text-center text-danger">Error loading products.</div>';
                loadingProducts.classList.add('d-none');
            });
    }

    function renderProducts(products) {
        if (products.length === 0) {
            productsGrid.innerHTML = '<div class="col-12 text-center text-muted py-5">No products found.</div>';
            return;
        }

        products.forEach(p => {
            const card = document.createElement('div');
            card.className = 'col-md-6 col-lg-4 col-xl-4';
            card.innerHTML = `
                <div class="card h-100 shadow-none border product-card hover-shadow">
                    <div class="position-relative">
                        <img src="${p.image}" class="card-img-top p-2" alt="${p.name}" style="height: 160px; object-fit: contain;">
                    </div>
                    <div class="card-body p-3">
                        <h5 class="card-title fs-15 mb-1 text-truncate" title="${p.name}">${p.name}</h5>
                        <p class="text-muted fs-12 mb-2">${p.sku}</p>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge bg-light text-dark border">Unit: ${p.prices.unit_price}</span>
                            <span class="badge bg-light text-dark border">Box: ${p.prices.box_price}</span>
                        </div>

                        <div class="row g-2 align-items-center mt-3">
                            <div class="col-4">
                                <input type="number" class="form-control form-control-sm text-center" value="1" min="1" id="qty-${p.id}">
                            </div>
                            <div class="col-4">
                                <select class="form-select form-select-sm" id="unit-${p.id}">
                                    <option value="Item">Unit</option>
                                    <option value="Box">Box</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <button class="btn btn-primary btn-sm w-100 btn-add-cart" data-id="${p.id}">
                                    <i class="ti ti-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            productsGrid.appendChild(card);

            // Attach event listener to the specific button
            // We store the product object in the button's data for easy access, or just lookup
            const btn = card.querySelector('.btn-add-cart');
            btn.addEventListener('click', () => {
                const qtyVal = document.getElementById(`qty-${p.id}`).value;
                const unitVal = document.getElementById(`unit-${p.id}`).value;
                addToCart(p, parseInt(qtyVal), unitVal);
            });
        });
    }

    // Filters
    if (btnSearch) {
        btnSearch.addEventListener('click', () => loadProducts(1));
        searchProduct.addEventListener('keyup', (e) => { if (e.key === 'Enter') loadProducts(1); });
        filterCategory.addEventListener('change', () => loadProducts(1));
        filterBrand.addEventListener('change', () => loadProducts(1));
    }


    // --- 2. Cart Management ---

    function addToCart(product, quantity, unitType) {
        if (quantity < 1) return;

        const existingItem = cart.find(item => item.id === product.id && item.unitType === unitType);
        const unitPrice = unitType === 'Box' ? product.prices.box_price : product.prices.unit_price;
        const taxRate = product.prices.tax_rate || 0;

        if (existingItem) {
            existingItem.quantity += quantity;
            const baseAmount = existingItem.quantity * existingItem.unitPrice;
            existingItem.taxAmount = baseAmount * (existingItem.taxRate / 100);
            existingItem.total = baseAmount + existingItem.taxAmount;
        } else {
            const baseAmount = quantity * unitPrice;
            const taxAmount = baseAmount * (taxRate / 100);
            cart.push({
                id: product.id,
                name: product.name,
                image: product.image,
                quantity: quantity,
                unitType: unitType,
                unitPrice: unitPrice,
                taxRate: taxRate,
                taxAmount: taxAmount,
                total: baseAmount + taxAmount
            });
        }
        renderCart();
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function updateCartQty(index, newQty) {
        const parsedQty = parseInt(newQty);
        if (parsedQty < 1) return;
        cart[index].quantity = parsedQty;
        const baseAmount = cart[index].quantity * cart[index].unitPrice;
        cart[index].taxAmount = baseAmount * ((cart[index].taxRate || 0) / 100);
        cart[index].total = baseAmount + cart[index].taxAmount;
        renderCart();
    }

    // Event delegation for cart actions (avoids polluting window scope)
    if (cartBody) {
        cartBody.addEventListener('click', function (e) {
            const removeLink = e.target.closest('[data-action="remove"]');
            if (removeLink) {
                removeFromCart(parseInt(removeLink.dataset.index, 10));
            }
        });

        cartBody.addEventListener('change', function (e) {
            const qtyInput = e.target.closest('[data-action="update-qty"]');
            if (qtyInput) {
                updateCartQty(parseInt(qtyInput.dataset.index, 10), qtyInput.value);
            }
        });
    }

    function renderCart() {
        if (cart.length === 0) {
            cartBody.innerHTML = '';
            cartBody.appendChild(emptyCartRow);
            cartTotalDisplay.innerText = '0.00';
            if (document.getElementById('cartTaxDisplay')) {
                document.getElementById('cartTaxDisplay').innerText = '0.00';
            }
            toggleSubmitButton(false);
            return;
        }

        let html = '';
        let grandTotal = 0;
        let totalTax = 0;

        cart.forEach((item, index) => {
            grandTotal += item.total;
            totalTax += item.taxAmount || 0;
            const taxInfo = item.taxRate > 0 ? ` +${item.taxRate}% tax` : '';
            html += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                             <div class="d-flex flex-column">
                                <span class="text-truncate fw-semibold" style="max-width: 120px;" title="${item.name}">${item.name}</span>
                                <span class="fs-11 text-muted">${item.unitType} @ ${item.unitPrice}${taxInfo}</span>
                             </div>
                        </div>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm px-1 py-0 text-center"
                               value="${item.quantity}" min="1"
                               style="height: 24px;"
                               data-action="update-qty" data-index="${index}">
                    </td>
                    <td class="text-end fw-medium">${item.total.toFixed(2)}</td>
                    <td class="text-end">
                        <a href="javascript:void(0);" class="text-danger" data-action="remove" data-index="${index}">
                            <i class="ti ti-x"></i>
                        </a>
                    </td>
                </tr>
            `;
        });

        cartBody.innerHTML = html;
        cartTotalDisplay.innerText = grandTotal.toFixed(2);
        if (document.getElementById('cartTaxDisplay')) {
            document.getElementById('cartTaxDisplay').innerText = totalTax.toFixed(2);
        }
        toggleSubmitButton(true);
    }

    function toggleSubmitButton(enable) {
        if (btnSubmit) btnSubmit.disabled = !enable;
    }


    // --- 3. Submission ---

    if (quotationForm) {
        quotationForm.addEventListener('submit', function (e) {
            e.preventDefault();

            if (cart.length === 0) {
                alert('Your cart is empty.');
                return;
            }

            const customerId = document.getElementById('customerId').value;
            if (!customerId) {
                alert('Please select a customer.');
                return;
            }

            const total = parseFloat(cartTotalDisplay.innerText);
            const totalTax = cart.reduce((sum, item) => sum + (item.taxAmount || 0), 0);
            const subtotal = total - totalTax;

            // Prepare Payload
            const payload = {
                customer_id: customerId,
                subtotal: subtotal,
                grand_total: total,
                tax_amount: totalTax,
                remarks: document.getElementById('remarks').value,
                items: cart.map(item => ({
                    product_id: item.id,
                    quantity: item.quantity,
                    unit_type: item.unitType,
                    unit_price: item.unitPrice,
                    tax_rate: item.taxRate || 0,
                    tax_amount: item.taxAmount || 0,
                    total_price: item.total
                }))
            };

            // Loading state
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Submitting...';

            axios.post('/api/quotations/submit', payload)
                .then(res => {
                    alert('Quotation submitted successfully!');
                    if (res.data.redirect) {
                        window.location.href = res.data.redirect;
                    } else {
                        // Reset
                        cart = [];
                        renderCart();
                        quotationForm.reset();
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = '<i class="ti ti-check me-1"></i> Submit Quotation';
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error submitting quotation: ' + (err.response?.data?.error || err.message));
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<i class="ti ti-check me-1"></i> Submit Quotation';
                });
        });
    }

    // Initial Load
    loadProducts();

});
