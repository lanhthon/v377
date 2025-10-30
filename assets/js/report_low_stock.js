// assets/js/report_low_stock.js
function initializeLowStockReportPage(mainContentContainer) {
    // DOM Elements
    const reportBody = $('#low-stock-report-body');
    const filterGroup = $('#filter-group');
    const filterType = $('#filter-type'); // MỚI: Bộ lọc loại sản phẩm
    const filterThickness = $('#filter-thickness');
    const filterWidth = $('#filter-width'); // MỚI: Bộ lọc bản rộng
    
    // State
    let allProductsData = [];

    // Utilities
    const formatNumber = App.formatNumber;
    const showMessageModal = App.showMessageModal;

    /**
     * Hiển thị dữ liệu sản phẩm đã lọc ra bảng báo cáo.
     * @param {Array} productsToRender - Mảng các đối tượng sản phẩm cần hiển thị.
     */
    function renderReport(productsToRender) {
        reportBody.empty();
        if (!productsToRender || productsToRender.length === 0) {
            // CẬP NHẬT: Thay đổi colspan thành 9 để khớp với số cột mới
            reportBody.html('<tr><td colspan="9" class="text-center p-6 text-gray-500">Không tìm thấy sản phẩm nào phù hợp với điều kiện lọc.</td></tr>');
            return;
        }

        productsToRender.forEach(p => {
            const tonKho = parseInt(p.currentStock, 10);
            const mucToiThieu = parseInt(p.minimum_stock_level, 10);
            let statusHtml;

            if (tonKho <= 0) {
                statusHtml = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Hết hàng</span>';
            } else {
                statusHtml = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Dưới định mức</span>';
            }

            // CẬP NHẬT: Thêm các cột mới cho Loại SP, Độ dày, và Bản rộng
            const row = `
                <tr class="hover:bg-gray-50">
                    <td class="p-3 border-b">${p.code}</td>
                    <td class="p-3 border-b font-medium text-gray-800">${p.name}</td>
                    <td class="p-3 border-b text-gray-600">${p.group_name || 'N/A'}</td>
                    <td class="p-3 border-b text-gray-600">${p.TenLoai || 'N/A'}</td>
                    <td class="p-3 border-b text-gray-600">${p.thickness || 'N/A'}</td>
                    <td class="p-3 border-b text-gray-600">${p.width || 'N/A'}</td>
                    <td class="p-3 border-b text-right font-bold text-red-600">${formatNumber(tonKho)}</td>
                    <td class="p-3 border-b text-right text-gray-600">${formatNumber(mucToiThieu)}</td>
                    <td class="p-3 border-b text-center">${statusHtml}</td>
                </tr>`;
            reportBody.append(row);
        });
    }

    /**
     * Áp dụng các lựa chọn lọc hiện tại và vẽ lại báo cáo.
     */
    function applyFiltersAndRender() {
        const selectedGroup = filterGroup.val();
        const selectedType = filterType.val(); // MỚI
        const selectedThickness = filterThickness.val();
        const selectedWidth = filterWidth.val(); // MỚI

        // 1. Bắt đầu với các sản phẩm dưới mức tồn kho tối thiểu
        let filteredProducts = allProductsData.filter(p => 
            parseInt(p.currentStock, 10) <= parseInt(p.minimum_stock_level, 10)
        );

        // 2. Lọc theo nhóm sản phẩm
        if (selectedGroup) {
            filteredProducts = filteredProducts.filter(p => p.group_name === selectedGroup);
        }

        // MỚI: 3. Lọc theo loại sản phẩm
        if (selectedType) {
            filteredProducts = filteredProducts.filter(p => p.TenLoai === selectedType);
        }

        // 4. Lọc theo độ dày
        if (selectedThickness) {
            filteredProducts = filteredProducts.filter(p => p.thickness === selectedThickness);
        }
        
        // MỚI: 5. Lọc theo bản rộng
        if (selectedWidth) {
            filteredProducts = filteredProducts.filter(p => p.width === selectedWidth);
        }

        renderReport(filteredProducts);
    }
    
    /**
     * Tải dữ liệu ban đầu và điền dữ liệu cho các ô lọc.
     */
    function loadReportData() {
        reportBody.html('<tr><td colspan="9" class="text-center p-10"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i></td></tr>');
        $.getJSON('api/get_products_with_stock.php')
            .done(function (response) {
                if (response.success) {
                    allProductsData = response.data;
                    
                    // Điền dữ liệu cho các dropdown lọc
                    if (response.filters) {
                        response.filters.productGroups.forEach(group => {
                            filterGroup.append(`<option value="${group}">${group}</option>`);
                        });
                        // MỚI: Điền dữ liệu cho bộ lọc loại sản phẩm
                        response.filters.productTypes.forEach(type => {
                            filterType.append(`<option value="${type}">${type}</option>`);
                        });
                        response.filters.thicknesses.forEach(thick => {
                            filterThickness.append(`<option value="${thick}">${thick}</option>`);
                        });
                        // MỚI: Điền dữ liệu cho bộ lọc bản rộng
                        response.filters.widths.forEach(width => {
                            filterWidth.append(`<option value="${width}">${width}</option>`);
                        });
                    }
                    
                    // Lọc và hiển thị lần đầu
                    applyFiltersAndRender();
                } else {
                    showMessageModal('Không thể tải dữ liệu báo cáo: ' + response.message, 'error');
                }
            })
            .fail(function () {
                showMessageModal('Lỗi kết nối khi tải dữ liệu báo cáo.', 'error');
            });
    }

    // --- GẮN CÁC SỰ KIỆN ---
    $('#apply-filters-btn').on('click', applyFiltersAndRender);

    $('#export-excel-btn').on('click', function() {
        // CẬP NHẬT: Bao gồm tất cả các bộ lọc trong URL xuất file Excel
        const params = new URLSearchParams({
            group: filterGroup.val(),
            type: filterType.val(),
            thickness: filterThickness.val(),
            width: filterWidth.val()
        });
        
        const url = `api/export_low_stock_excel.php?${params.toString()}`;
        window.location.href = url;
    });

    $('#create-po-btn').on('click', function() {
        // Chuyển hướng đến trang tạo lệnh sản xuất cho tồn kho
        window.location.href = '?page=lenhsanxuat_create_stock';
    });

    // --- KHỞI CHẠY ---
    loadReportData();
}
