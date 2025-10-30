document.addEventListener('DOMContentLoaded', function () {
    // DOM Elements
    const elements = {
        tableBody: document.getElementById('product-table-body'),
        loadingSpinner: document.getElementById('loading-spinner'),
        searchInput: document.getElementById('search-input'),
        groupFilter: document.getElementById('group-filter'),
        thicknessFilter: document.getElementById('thickness-filter'),
        stockStatusFilter: document.getElementById('stock-status-filter'),
        refreshBtn: document.getElementById('refresh-btn'),
        currentTime: document.getElementById('current-time'),
        totalProducts: document.getElementById('total-products'),
        lowStockProducts: document.getElementById('low-stock-products'),
        outOfStockProducts: document.getElementById('out-of-stock-products'),
        totalStockValue: document.getElementById('total-stock-value'),
        kpiTotal: document.getElementById('kpi-total'),
        kpiLowStock: document.getElementById('kpi-low-stock'),
        kpiOutOfStock: document.getElementById('kpi-out-of-stock'),
        // New elements for the warning table
        warningTableBody: document.getElementById('warning-table-body'),
        noWarningsMessage: document.getElementById('no-warnings'),
    };

    // State
    let allProducts = [];
    let charts = {};

    // --- UTILITY FUNCTIONS ---
    const showToast = (message, isError = false) => {
        const toast = document.getElementById('toast-notification');
        const toastMessage = document.getElementById('toast-message');
        toastMessage.textContent = message;
        toast.className = `fixed bottom-5 right-5 text-white py-3 px-6 rounded-lg shadow-xl opacity-0 transform translate-y-10 z-50 ${isError ? 'bg-red-600' : 'bg-slate-800'}`;
        toast.classList.remove('opacity-0', 'translate-y-10');
        toast.classList.add('opacity-100', 'translate-y-0');
        setTimeout(() => {
            toast.classList.remove('opacity-100', 'translate-y-0');
            toast.classList.add('opacity-0', 'translate-y-10');
        }, 3000);
    };

    const updateClock = () => {
        elements.currentTime.textContent = new Date().toLocaleString('vi-VN', {
            dateStyle: 'full',
            timeStyle: 'short'
        });
    };

    const formatCurrency = (value) => {
        // Assuming 'GiaVon' is a property on your product object
        if (typeof value !== 'number') return '0 ₫';
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
    };

    // --- RENDERING FUNCTIONS ---
    const populateFilters = (filters) => {
        const populate = (select, options, defaultText) => {
            select.innerHTML = `<option value="">-- ${defaultText} --</option>`;
            options.forEach(opt => select.innerHTML += `<option value="${opt}">${opt}</option>`);
        };
        populate(elements.groupFilter, filters.productGroups, 'Lọc theo nhóm sản phẩm');
        populate(elements.thicknessFilter, filters.thicknesses, 'Lọc theo độ dày');
        elements.stockStatusFilter.innerHTML = `
            <option value="">-- Lọc theo trạng thái --</option>
            <option value="in_stock">Còn hàng</option>
            <option value="low_stock">Sắp hết hàng</option>
            <option value="out_of_stock">Hết hàng</option>
        `;
    };

    const renderTable = (products) => {
        elements.tableBody.innerHTML = '';
        if (products.length === 0) {
            elements.tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-slate-500">Không tìm thấy sản phẩm nào phù hợp.</td></tr>`;
            return;
        }
        products.forEach(p => {
            let rowClass = '';
            if (p.SoLuongTonKho <= 0) rowClass = 'out-of-stock';
            else if (p.SoLuongTonKho < p.DinhMucToiThieu) rowClass = 'low-stock-warning';

            elements.tableBody.innerHTML += `
                <tr class="product-row ${rowClass}" data-product-id="${p.SanPhamID}">
                    <td class="px-4 py-3 font-medium text-slate-800 whitespace-nowrap">${p.MaHang}</td>
                    <td class="px-4 py-3">${p.TenSanPham}</td>
                    <td class="px-4 py-3 text-xs text-slate-600">${p.NhomSanPham || ''}</td>
                    <td class="px-4 py-3 text-center text-sm text-slate-500">${p.DinhMucToiThieu}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="font-bold text-lg current-stock-value">${p.SoLuongTonKho}</span>
                        <span class="text-xs text-slate-500">${p.DonViTinh}</span>
                    </td>
                    <td class="px-4 py-3">
                        <input type="number" class="w-full border border-slate-300 rounded-md text-center py-1.5 quantity-input" placeholder="VD: 5, -2">
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button class="update-stock-btn px-3 py-1.5 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700 transition-colors shadow-sm" title="Cập nhật kho">
                            <i class="fas fa-check"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    };

    const renderWarningTable = (products) => {
        const lowStockProducts = products.filter(p => p.SoLuongTonKho < p.DinhMucToiThieu && p.SoLuongTonKho > 0)
            .sort((a, b) => (a.SoLuongTonKho / a.DinhMucToiThieu) - (b.SoLuongTonKho / b.DinhMucToiThieu));

        elements.warningTableBody.innerHTML = '';

        if (lowStockProducts.length === 0) {
            elements.noWarningsMessage.classList.remove('hidden');
        } else {
            elements.noWarningsMessage.classList.add('hidden');
            lowStockProducts.forEach(p => {
                elements.warningTableBody.innerHTML += `
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2 font-medium text-slate-700">${p.TenSanPham}</td>
                        <td class="px-3 py-2 text-center font-semibold text-yellow-600">${p.SoLuongTonKho}</td>
                        <td class="px-3 py-2 text-center text-slate-500">${p.DinhMucToiThieu}</td>
                    </tr>
                `;
            });
        }
    };

    const updateDashboard = (products) => {
        const lowStockCount = products.filter(p => p.SoLuongTonKho > 0 && p.SoLuongTonKho < p.DinhMucToiThieu).length;
        const outOfStockCount = products.filter(p => p.SoLuongTonKho <= 0).length;
        const totalValue = products.reduce((acc, p) => acc + (p.SoLuongTonKho * (p.GiaVon || 0)), 0);


        elements.totalProducts.textContent = products.length;
        elements.lowStockProducts.textContent = lowStockCount;
        elements.outOfStockProducts.textContent = outOfStockCount;
        elements.totalStockValue.textContent = formatCurrency(totalValue);


        // Update charts and the new warning table
        renderWarningTable(products);
        updateGroupChart(products);
        updateReorderChart(products);
    };

    // --- CHARTING FUNCTIONS ---
    const createOrUpdateChart = (id, type, data, options) => {
        const ctx = document.getElementById(id).getContext('2d');
        if (charts[id]) {
            charts[id].destroy();
        }
        charts[id] = new Chart(ctx, { type, data, options });
    };

    const updateGroupChart = (products) => {
        const groupCounts = products.reduce((acc, p) => {
            const group = p.NhomSanPham || 'Chưa phân loại';
            acc[group] = (acc[group] || 0) + 1;
            return acc;
        }, {});

        createOrUpdateChart('group-chart', 'doughnut', {
            labels: Object.keys(groupCounts),
            datasets: [{
                data: Object.values(groupCounts),
                backgroundColor: ['#3b82f6', '#10b981', '#f97316', '#8b5cf6', '#64748b', '#ef4444', '#eab308'],
                borderColor: '#ffffff',
                borderWidth: 2,
            }]
        }, { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } });
    };


    const updateReorderChart = (products) => {
        const reorderProducts = products
            .filter(p => p.SoLuongTonKho < p.DinhMucToiThieu)
            .sort((a, b) => (a.SoLuongTonKho / a.DinhMucToiThieu) - (b.SoLuongTonKho / b.DinhMucToiThieu))
            .slice(0, 5);

        createOrUpdateChart('reorder-chart', 'bar', {
            labels: reorderProducts.map(p => p.MaHang),
            datasets: [{
                label: 'Tồn kho hiện tại',
                data: reorderProducts.map(p => p.SoLuongTonKho),
                backgroundColor: '#f87171', // red-400
                borderRadius: 4,
            }, {
                label: 'Tồn kho tối thiểu',
                data: reorderProducts.map(p => p.DinhMucToiThieu),
                backgroundColor: '#fbbf24', // amber-400
                borderRadius: 4,
            }]
        }, {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: { y: { beginAtZero: true } },
            plugins: { tooltip: { callbacks: { title: (ctx) => reorderProducts[ctx[0].dataIndex].TenSanPham } } }
        });
    };


    // --- DATA & LOGIC FUNCTIONS ---
    const applyAllFilters = () => {
        const searchTerm = elements.searchInput.value.toLowerCase().trim();
        const selectedGroup = elements.groupFilter.value;
        const selectedThickness = elements.thicknessFilter.value;
        const selectedStockStatus = elements.stockStatusFilter.value;

        const filtered = allProducts.filter(p => {
            const matchesSearch = (p.TenSanPham || '').toLowerCase().includes(searchTerm) || (p.MaHang || '').toLowerCase().includes(searchTerm);
            const matchesGroup = !selectedGroup || p.NhomSanPham === selectedGroup;
            const matchesThickness = !selectedThickness || p.DoDay == selectedThickness;

            let matchesStockStatus = true;
            if (selectedStockStatus) {
                if (selectedStockStatus === 'low_stock') matchesStockStatus = p.SoLuongTonKho > 0 && p.SoLuongTonKho < p.DinhMucToiThieu;
                else if (selectedStockStatus === 'out_of_stock') matchesStockStatus = p.SoLuongTonKho <= 0;
                else if (selectedStockStatus === 'in_stock') matchesStockStatus = p.SoLuongTonKho >= p.DinhMucToiThieu;
            }
            return matchesSearch && matchesGroup && matchesThickness && matchesStockStatus;
        });
        renderTable(filtered);
    };

    const fetchProducts = async () => {
        elements.loadingSpinner.style.display = 'flex';
        elements.tableBody.innerHTML = '';
        try {
            // Đảm bảo đường dẫn này chính xác
            const response = await fetch('../api/get_products_with_stock.php');
            if (!response.ok) throw new Error(`Lỗi HTTP: ${response.status}`);
            const result = await response.json();
            if (result.success) {
                allProducts = result.data;
                populateFilters(result.filters);
                updateDashboard(allProducts);
                applyAllFilters();
            } else {
                throw new Error(result.message || "Dữ liệu trả về không hợp lệ.");
            }
        } catch (error) {
            showToast(`Lỗi khi tải dữ liệu: ${error.message}`, true);
            elements.tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-red-500">Không thể tải dữ liệu. Vui lòng thử lại.</td></tr>`;
        } finally {
            elements.loadingSpinner.style.display = 'none';
        }
    };

    const handleUpdateStock = async (e) => {
        const button = e.target.closest('.update-stock-btn');
        if (!button) return;

        const row = button.closest('.product-row');
        const productId = row.dataset.productId;
        const quantityInput = row.querySelector('.quantity-input');
        const quantity = parseInt(quantityInput.value, 10);

        if (isNaN(quantity) || quantity === 0) {
            showToast("Vui lòng nhập số lượng hợp lệ (âm để xuất, dương để nhập).", true);
            return;
        }

        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
            // Đảm bảo đường dẫn này chính xác
            const response = await fetch('../api/update_stock.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, quantity: quantity })
            });
            const result = await response.json();

            if (response.ok && result.success) {
                showToast(result.message);
                const productIndex = allProducts.findIndex(p => p.SanPhamID == productId);
                if (productIndex > -1) {
                    allProducts[productIndex].SoLuongTonKho = result.newStock;
                }
                updateDashboard(allProducts);
                applyAllFilters();
                quantityInput.value = '';
            } else {
                throw new Error(result.message || "Lỗi không xác định từ server.");
            }
        } catch (error) {
            showToast(error.message, true);
        } finally {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-check"></i>';
        }
    };

    // --- EVENT LISTENERS ---
    [elements.searchInput, elements.groupFilter, elements.thicknessFilter, elements.stockStatusFilter].forEach(el =>
        el.addEventListener('input', applyAllFilters)
    );
    elements.refreshBtn.addEventListener('click', fetchProducts);
    elements.tableBody.addEventListener('click', handleUpdateStock);
    [elements.kpiTotal, elements.kpiLowStock, elements.kpiOutOfStock].forEach(card => {
        card.addEventListener('click', () => {
            const status = card.id === 'kpi-low-stock' ? 'low_stock' : card.id === 'kpi-out-of-stock' ? 'out_of_stock' : '';
            elements.stockStatusFilter.value = status;
            applyAllFilters();
            document.getElementById('search-input').focus();
        });
    });

    // --- INITIALIZATION ---
    updateClock();
    setInterval(updateClock, 60000); // Update clock every minute
    fetchProducts();
});