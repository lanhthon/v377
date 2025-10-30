// assets/js/kho.js

function initializeKhoPage(mainContentContainer) {
    // === STATE VARIABLES ===
    let inventoryCurrentPage = 1;
    let historyCurrentPage = 1;
    const itemsPerPage = 15;
    let inventoryDataCache = [];
    let filteredInventoryData = [];
    let reportDataCache = []; // To store report data for export

    // === DOM ELEMENTS ===
    const inventoryListBody = $('#inventory-list-body');
    const historyListBody = $('#history-list-body');
    const inventoryPagination = $('#inventory-pagination');
    const historyPagination = $('#history-pagination');
    const searchInput = $('#inventory-search-input');
    const groupFilter = $('#inventory-group-filter');
    const thicknessFilter = $('#inventory-thickness-filter');
    const widthFilter = $('#inventory-width-filter'); // ADDED: Width filter element
    const typeFilter = $('#inventory-type-filter'); 

    // Modals
    const adjustModal = $('#adjust-stock-modal');
    const adjustForm = $('#adjust-stock-form');
    const editMinStockModal = $('#edit-min-stock-modal');
    const editMinStockForm = $('#edit-min-stock-form');

    // History filter elements
    const historyStartDate = $('#history-start-date');
    const historyEndDate = $('#history-end-date');
    const historyTypeFilter = $('#history-type-filter');
    const historyFilterBtn = $('#history-filter-btn');
    const exportHistoryExcelBtn = $('#export-history-excel-btn');

    // Report elements
    const reportStartDateInput = $('#report-start-date');
    const reportEndDateInput = $('#report-end-date');
    const generateReportBtn = $('#generate-report-btn');
    const inventoryReportBody = $('#inventory-report-body');
    const exportReportExcelBtn = $('#export-report-excel-btn');

    // Action buttons
    const exportInventoryExcelBtn = $('#export-inventory-excel-btn');


    // === UTILITY FUNCTIONS ===
    const formatNumber = window.App ? App.formatNumber : (num) => num;
    const showMessageModal = window.App ? App.showMessageModal : (msg, type) => alert(`[${type}] ${msg}`);
    const handleRouting = window.App ? App.handleRouting : () => {};

    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // === RENDER FUNCTIONS ===
    function renderPagination(container, totalItems, currentPage, onPageClick) {
        container.empty();
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        if (totalPages <= 1) return;

        let paginationHtml = '<div class="flex items-center space-x-1 text-sm">';

        if (currentPage > 1) {
            paginationHtml += `<button class="page-btn px-3 py-1 rounded-md bg-gray-200 hover:bg-gray-300" data-page="1">Đầu</button>`;
            paginationHtml += `<button class="page-btn px-3 py-1 rounded-md bg-gray-200 hover:bg-gray-300" data-page="${currentPage - 1}">&laquo;</button>`;
        } else {
            paginationHtml += `<button class="px-3 py-1 rounded-md bg-gray-100 text-gray-400 cursor-not-allowed">Đầu</button>`;
            paginationHtml += `<button class="px-3 py-1 rounded-md bg-gray-100 text-gray-400 cursor-not-allowed">&laquo;</button>`;
        }

        const maxPagesToShow = 5;
        let startPage, endPage;
        if (totalPages <= maxPagesToShow) {
            startPage = 1;
            endPage = totalPages;
        } else {
            const maxPagesBeforeCurrent = Math.floor(maxPagesToShow / 2);
            const maxPagesAfterCurrent = Math.ceil(maxPagesToShow / 2) - 1;
            if (currentPage <= maxPagesBeforeCurrent) {
                startPage = 1;
                endPage = maxPagesToShow;
            } else if (currentPage + maxPagesAfterCurrent >= totalPages) {
                startPage = totalPages - maxPagesToShow + 1;
                endPage = totalPages;
            } else {
                startPage = currentPage - maxPagesBeforeCurrent;
                endPage = currentPage + maxPagesAfterCurrent;
            }
        }

        if (startPage > 1) paginationHtml += `<span class="px-3 py-1">...</span>`;
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `<button class="page-btn px-3 py-1 rounded-md ${i === currentPage ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'}" data-page="${i}">${i}</button>`;
        }
        if (endPage < totalPages) paginationHtml += `<span class="px-3 py-1">...</span>`;

        if (currentPage < totalPages) {
            paginationHtml += `<button class="page-btn px-3 py-1 rounded-md bg-gray-200 hover:bg-gray-300" data-page="${currentPage + 1}">&raquo;</button>`;
            paginationHtml += `<button class="page-btn px-3 py-1 rounded-md bg-gray-200 hover:bg-gray-300" data-page="${totalPages}">Cuối</button>`;
        } else {
            paginationHtml += `<button class="px-3 py-1 rounded-md bg-gray-100 text-gray-400 cursor-not-allowed">&raquo;</button>`;
            paginationHtml += `<button class="px-3 py-1 rounded-md bg-gray-100 text-gray-400 cursor-not-allowed">Cuối</button>`;
        }

        paginationHtml += '</div>';
        container.html(paginationHtml);

        container.off('click', '.page-btn').on('click', '.page-btn', function(e) {
            e.preventDefault();
            const page = parseInt($(this).data('page'));
            if (page && page !== currentPage) onPageClick(page);
        });
    }

    function renderInventoryList(products) {
        inventoryListBody.empty();
        if (!products || products.length === 0) {
            inventoryListBody.html('<tr><td colspan="11" class="text-center p-6 text-gray-500">Không tìm thấy sản phẩm nào phù hợp.</td></tr>');
            return;
        }

        products.forEach(p => {
            const tonKho = parseInt(p.currentStock, 10);
            const mucToiThieu = parseInt(p.minimum_stock_level, 10);
            let statusHtml;

            if (tonKho <= 0) {
                statusHtml = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Hết hàng</span>';
            } else if (tonKho <= mucToiThieu) {
                statusHtml = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Cần nhập</span>';
            } else {
                statusHtml = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Đủ hàng</span>';
            }

            const row = `
                <tr class="hover:bg-gray-50">
                    <td class="p-3 border-b">${p.code}</td>
                    <td class="p-3 border-b font-medium text-gray-800">${p.name}</td>
                    <td class="p-3 border-b text-gray-600">${p.group_name || 'N/A'}</td>
                    <td class="p-3 border-b text-gray-600">${p.TenLoai || 'N/A'}</td>
                    <td class="p-3 border-b text-center">${p.id_thongso || 'N/A'}</td>
                    <td class="p-3 border-b text-center">${p.thickness || 'N/A'}</td>
                    <td class="p-3 border-b text-center">${p.width || 'N/A'}</td>
                    <td class="p-3 border-b text-right font-bold text-blue-600">${formatNumber(tonKho)}</td>
                    <td class="p-3 border-b text-right text-gray-600">${formatNumber(mucToiThieu)}</td>
                    <td class="p-3 border-b text-center">${statusHtml}</td>
                    <td class="p-3 border-b text-center no-print">
                        <button class="adjust-stock-btn text-blue-500 hover:text-blue-700 p-1" title="Điều chỉnh số lượng">
                            <i class="fas fa-calculator"></i>
                        </button>
                        <button class="edit-min-stock-btn text-green-500 hover:text-green-700 p-1 ml-2" title="Sửa định mức tối thiểu">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>`;
            const $row = $(row);
            $row.data('product-data', p);
            inventoryListBody.append($row);
        });
    }

    function applyInventoryFiltersAndRender() {
        let filtered = [...inventoryDataCache];
        const searchTerm = (searchInput.val() || '').toLowerCase().trim();
        const group = groupFilter.val();
        const thickness = thicknessFilter.val();
        const width = widthFilter.val(); // ADDED: Get width value
        const type = typeFilter.val();

        if (searchTerm) {
            filtered = filtered.filter(p =>
                (p.name && p.name.toLowerCase().includes(searchTerm)) ||
                (p.code && p.code.toLowerCase().includes(searchTerm))
            );
        }
        if (group) filtered = filtered.filter(p => p.group_name === group);
        if (thickness) filtered = filtered.filter(p => p.thickness == thickness);
        if (width) filtered = filtered.filter(p => p.width == width); // ADDED: Apply width filter
        if (type) filtered = filtered.filter(p => p.TenLoai === type);

        filteredInventoryData = filtered;
        const totalItems = filteredInventoryData.length;
        
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        if (inventoryCurrentPage > totalPages && totalPages > 0) {
            inventoryCurrentPage = totalPages;
        }
        
        const startIndex = (inventoryCurrentPage - 1) * itemsPerPage;
        const paginatedItems = filteredInventoryData.slice(startIndex, startIndex + itemsPerPage);

        renderInventoryList(paginatedItems);
        renderPagination(inventoryPagination, totalItems, inventoryCurrentPage, (page) => {
            inventoryCurrentPage = page;
            applyInventoryFiltersAndRender();
        });
    }

    function renderHistoryList(history, totalItems) {
        historyListBody.empty();
        if (!history || history.length === 0) {
            historyListBody.html('<tr><td colspan="8" class="text-center p-6 text-gray-500">Không có dữ liệu lịch sử phù hợp.</td></tr>');
            return;
        }

        history.forEach(h => {
            const isPositive = parseInt(h.SoLuongThayDoi, 10) >= 0;
            const changeClass = isPositive ? 'text-green-600' : 'text-red-600';
            const changePrefix = isPositive ? '+' : '';
            const row = `
                <tr>
                    <td class="p-3 border-b">${new Date(h.NgayGiaoDich).toLocaleString('vi-VN')}</td>
                    <td class="p-3 border-b">${h.variant_sku}</td>
                    <td class="p-3 border-b font-medium">${h.variant_name}</td>
                    <td class="p-3 border-b">${h.LoaiGiaoDich}</td>
                    <td class="p-3 border-b text-right font-bold ${changeClass}">${changePrefix}${formatNumber(h.SoLuongThayDoi)}</td>
                    <td class="p-3 border-b text-right font-semibold">${formatNumber(h.SoLuongSauGiaoDich)}</td>
                    <td class="p-3 border-b text-gray-600">${h.GhiChu || ''}</td>
                    <td class="p-3 border-b text-gray-500 text-xs">${h.MaThamChieu || ''}</td>
                </tr>`;
            historyListBody.append(row);
        });

        renderPagination(historyPagination, totalItems, historyCurrentPage, (page) => {
            historyCurrentPage = page;
            loadHistoryData();
        });
    }
    
    function renderReport(reportData) {
        inventoryReportBody.empty();
        reportDataCache = reportData;
        if (!reportData || reportData.length === 0) {
            inventoryReportBody.html('<tr><td colspan="6" class="text-center p-6 text-gray-500">Không có dữ liệu cho khoảng ngày đã chọn.</td></tr>');
            return;
        }
        reportData.forEach(item => {
            const row = `
                <tr class="hover:bg-gray-50">
                    <td class="p-3 border-b">${item.variant_sku}</td>
                    <td class="p-3 border-b font-medium">${item.variant_name}</td>
                    <td class="p-3 border-b text-right">${formatNumber(item.TonDauKy)}</td>
                    <td class="p-3 border-b text-right text-green-600 font-semibold">+${formatNumber(item.TongNhap)}</td>
                    <td class="p-3 border-b text-right text-red-600 font-semibold">-${formatNumber(item.TongXuat)}</td>
                    <td class="p-3 border-b text-right font-bold">${formatNumber(item.TonCuoiKy)}</td>
                </tr>
            `;
            inventoryReportBody.append(row);
        });
    }

    // === DATA LOADING FUNCTIONS ===
    function loadTransactionTypes() {
        $.ajax({
            url: 'api/get_transaction_types.php',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    historyTypeFilter.find('option:not(:first)').remove();
                    response.data.forEach(type => {
                        const typeText = type.replace(/_/g, ' ');
                        historyTypeFilter.append($('<option>', { value: type, text: typeText }));
                    });
                }
            },
            error: (xhr, status, error) => console.error("AJAX Error loading transaction types:", status, error)
        });
    }

    function loadInventoryData(preserveState = false) {
        inventoryListBody.html('<tr><td colspan="11" class="text-center p-10"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i></td></tr>');
        
        let currentSearch, currentGroup, currentThickness, currentWidth, currentType;
        
        if (preserveState) {
            currentSearch = searchInput.val();
            currentGroup = groupFilter.val();
            currentThickness = thicknessFilter.val();
            currentWidth = widthFilter.val(); // ADDED
            currentType = typeFilter.val();
        }
        
        $.ajax({
            url: 'api/get_products_with_stock.php',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    inventoryDataCache = response.data;
                    
                    // Update filter options
                    groupFilter.html('<option value="">-- Lọc theo nhóm SP --</option>');
                    response.filters.productGroups.forEach(g => groupFilter.append(`<option value="${g}">${g}</option>`));
                    
                    thicknessFilter.html('<option value="">-- Lọc theo độ dày --</option>');
                    response.filters.thicknesses.forEach(t => thicknessFilter.append(`<option value="${t}">${t}</option>`));
                    
                    // ADDED: Populate width filter
                    widthFilter.html('<option value="">-- Lọc theo bản rộng --</option>');
                    response.filters.widths.forEach(w => widthFilter.append(`<option value="${w}">${w}</option>`));
                    
                    typeFilter.html('<option value="">-- Lọc theo loại SP --</option>');
                    response.filters.productTypes.forEach(t => typeFilter.append(`<option value="${t}">${t}</option>`));

                    if (preserveState) {
                        searchInput.val(currentSearch);
                        groupFilter.val(currentGroup);
                        thicknessFilter.val(currentThickness);
                        widthFilter.val(currentWidth); // ADDED
                        typeFilter.val(currentType);
                    } else {
                        inventoryCurrentPage = 1;
                    }
                    
                    applyInventoryFiltersAndRender();
                } else {
                    showMessageModal('Không thể tải dữ liệu tồn kho: ' + response.message, 'error');
                }
            },
            error: () => showMessageModal('Lỗi kết nối khi tải dữ liệu tồn kho.', 'error')
        });
    }

    function loadHistoryData() {
        historyListBody.html('<tr><td colspan="8" class="text-center p-10"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i></td></tr>');
        const params = {
            page: historyCurrentPage,
            limit: itemsPerPage,
            start_date: historyStartDate.val(),
            end_date: historyEndDate.val(),
            type: historyTypeFilter.val()
        };

        $.ajax({
            url: 'api/get_inventory_history.php',
            data: params,
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    renderHistoryList(response.data, response.total_items);
                } else {
                    historyListBody.html(`<tr><td colspan="8" class="text-center p-6 text-red-500">${response.message}</td></tr>`);
                }
            },
            error: () => showMessageModal('Lỗi kết nối khi tải lịch sử kho.', 'error')
        });
    }

    // === MODAL MANAGEMENT ===
    function showAdjustModal(product) {
        adjustForm[0].reset();
        $('#adjust-product-id').val(product.productId);
        $('#adjust-product-name').text(`${product.code} - ${product.name}`);
        $('#adjust-current-stock').val(formatNumber(product.currentStock));
        $('#adjust-new-stock').val(product.currentStock);
        adjustModal.removeClass('hidden');
    }

    function hideAdjustModal() { adjustModal.addClass('hidden'); }

    function showEditMinStockModal(product) {
        editMinStockForm[0].reset();
        $('#edit-min-stock-product-id').val(product.productId);
        $('#edit-min-stock-product-name').text(`${product.code} - ${product.name}`);
        $('#edit-min-stock-current').val(formatNumber(product.minimum_stock_level));
        $('#edit-min-stock-new').val(product.minimum_stock_level);
        editMinStockModal.removeClass('hidden');
    }

    function hideEditMinStockModal() { editMinStockModal.addClass('hidden'); }

    // === EXPORT & REPORT FUNCTIONS ===
    function exportInventoryToExcel() {
        const dataToExport = filteredInventoryData.map(p => {
            // Lấy giá trị tồn kho và định mức, chuyển đổi sang số và mặc định là 0 nếu không hợp lệ
            const tonKho = parseFloat(p.currentStock) || 0;
            const dinhMucKg = parseFloat(p.dinh_muc_kg_bo) || 0;

            // Tính tổng kg
            const tongKg = tonKho * dinhMucKg;

            return {
                'Mã SP': p.code,
                'Tên SP': p.name,
                'Nhóm SP': p.group_name,
                'Loại SP': p.TenLoai,
                'ID Thông Số': p.id_thongso,
                'Độ Dày': p.thickness,
                'Bản Rộng': p.width,
                'Tồn Kho': tonKho,
                'Định Mức Tối Thiểu': parseInt(p.minimum_stock_level, 10),
                'Định mức kg': dinhMucKg, 
                // BẮT ĐẦU CẬP NHẬT --
                // Làm tròn rồi chuyển lại thành số để Excel hiểu đúng định dạng
                'Tổng kg': parseFloat(tongKg.toFixed(2)) 
                // KẾT THÚC CẬP NHẬT --
            };
        });

        if (dataToExport.length === 0) {
            showMessageModal('Không có dữ liệu tồn kho để xuất.', 'warning');
            return;
        }

        const worksheet = XLSX.utils.json_to_sheet(dataToExport);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "TonKho");

        worksheet["!cols"] = Object.keys(dataToExport[0] || {}).map(key => ({
            wch: Math.max(...dataToExport.map(item => (item[key] || "").toString().length), key.length) + 2
        }));

        XLSX.writeFile(workbook, "BaoCaoTonKho.xlsx");
    }

// assets/js/kho.js

// ... (giữ nguyên các hàm khác) ...

    function exportHistoryToExcel() {
        const btn = exportHistoryExcelBtn;
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang xuất...');
        
        const params = {
            export: 'true', // Yêu cầu API trả về tất cả dữ liệu, không phân trang
            start_date: historyStartDate.val(),
            end_date: historyEndDate.val(),
            type: historyTypeFilter.val()
        };

        $.ajax({
            url: 'api/get_inventory_history.php',
            data: params,
            dataType: 'json',
            success: function(response) {
                // --- BẮT ĐẦU CẬP NHẬT ---

                if (!response.success || response.data.length === 0) {
                    showMessageModal('Không có dữ liệu lịch sử để xuất theo bộ lọc đã chọn.', 'warning');
                    return;
                }

                // 1. Chuẩn bị dữ liệu cho sheet chính "LichSuKho"
                const historyDataToExport = response.data.map(h => ({
                    'Thời Gian': new Date(h.NgayGiaoDich).toLocaleString('vi-VN'),
                    'Mã SP': h.variant_sku,
                    'Tên SP': h.variant_name,
                    'Loại GD': h.LoaiGiaoDich,
                    'SL Thay Đổi': parseFloat(h.SoLuongThayDoi) || 0,
                    'SL Sau GD': parseFloat(h.SoLuongSauGiaoDich) || 0,
                    'Ghi Chú': h.GhiChu,
                    'Mã Tham Chiếu': h.MaThamChieu
                }));

                // 2. Chuẩn bị dữ liệu cho sheet "Chú thích"
                // Lọc ra các bản ghi lịch sử của các sản phẩm có 'old_id'
                // **LƯU Ý QUAN TRỌNG**: API của bạn cần trả về thuộc tính `old_id` trong mỗi đối tượng của `response.data`
                const itemsWithOldIds = response.data.filter(h => h.old_id && h.old_id.trim() !== '');
                
                let uniqueNotes = {}; // Dùng để tránh lặp lại chú thích cho cùng một sản phẩm
                if (itemsWithOldIds.length > 0) {
                    itemsWithOldIds.forEach(h => {
                        // Chỉ thêm chú thích nếu sản phẩm này chưa có
                        if (!uniqueNotes[h.variant_sku]) {
                             uniqueNotes[h.variant_sku] = {
                                'Mã SP Hiện Tại': h.variant_sku,
                                'Tên SP': h.variant_name,
                                'Ghi Chú': `Sản phẩm này được chuyển đổi từ mã cũ: ${h.old_id}`
                            };
                        }
                    });
                }
                const notesData = Object.values(uniqueNotes);

                // 3. Tạo Workbook và các Worksheet
                const workbook = XLSX.utils.book_new();

                // Tạo sheet Lịch sử kho
                const historyWorksheet = XLSX.utils.json_to_sheet(historyDataToExport);
                historyWorksheet["!cols"] = Object.keys(historyDataToExport[0]).map(key => ({
                    wch: Math.max(...historyDataToExport.map(item => (item[key] || "").toString().length), key.length) + 2
                }));
                XLSX.utils.book_append_sheet(workbook, historyWorksheet, "LichSuKho");

                // Nếu có dữ liệu chú thích, tạo sheet chú thích
                if (notesData.length > 0) {
                    const notesWorksheet = XLSX.utils.json_to_sheet(notesData);
                    notesWorksheet["!cols"] = Object.keys(notesData[0] || {}).map(key => ({
                        wch: Math.max(...notesData.map(item => (item[key] || "").toString().length), key.length) + 2
                    }));
                    XLSX.utils.book_append_sheet(workbook, notesWorksheet, "ChuThich_ID_ThayDoi");
                }

                // 4. Xuất file Excel
                const filename = `LichSuKho_Tu_${params.start_date || 'batdau'}_Den_${params.end_date || 'homnay'}.xlsx`;
                XLSX.writeFile(workbook, filename);

                // --- KẾT THÚC CẬP NHẬT ---
            },
            error: () => showMessageModal('Lỗi khi tạo file Excel từ dữ liệu lịch sử.', 'error'),
            complete: () => btn.prop('disabled', false).html('<i class="fas fa-file-excel mr-2"></i>Xuất Lịch Sử')
        });
    }

// ... (giữ nguyên các hàm khác) ...
    
    function generateReport() {
        const startDate = reportStartDateInput.val();
        const endDate = reportEndDateInput.val();

        if (!startDate || !endDate) {
            showMessageModal('Vui lòng chọn ngày bắt đầu và ngày kết thúc để xem báo cáo.', 'warning');
            return;
        }
        const btn = generateReportBtn;
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang tải...');
        inventoryReportBody.html('<tr><td colspan="6" class="text-center p-10"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i></td></tr>');
        
        $.ajax({
            url: 'api/get_inventory_report.php',
            data: { 
                start_date: startDate,
                end_date: endDate
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) renderReport(response.data);
                else {
                    showMessageModal('Không thể tải báo cáo: ' + response.message, 'error');
                    inventoryReportBody.html(`<tr><td colspan="6" class="text-center p-6 text-red-500">Lỗi: ${response.message}</td></tr>`);
                    reportDataCache = [];
                }
            },
            error: () => {
                showMessageModal('Lỗi kết nối khi tải báo cáo.', 'error');
                inventoryReportBody.html('<tr><td colspan="6" class="text-center p-6 text-red-500">Lỗi kết nối server.</td></tr>');
                reportDataCache = [];
            },
            complete: () => btn.prop('disabled', false).html('<i class="fas fa-search mr-2"></i>Xem Báo Cáo')
        });
    }

    function exportReportToExcel() {
        if (reportDataCache.length === 0) {
            showMessageModal('Chưa có dữ liệu báo cáo để xuất. Vui lòng "Xem Báo Cáo" trước.', 'warning');
            return;
        }
        const dataToExport = reportDataCache.map(item => ({
            'Mã SP': item.variant_sku, 'Tên SP': item.variant_name, 'Tồn Đầu Kỳ': item.TonDauKy,
            'Tổng Nhập': item.TongNhap, 'Tổng Xuất': item.TongXuat, 'Tồn Cuối Kỳ': item.TonCuoiKy
        }));
        const worksheet = XLSX.utils.json_to_sheet(dataToExport);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "BaoCaoXuatNhapTon");
        worksheet["!cols"] = Object.keys(dataToExport[0]).map(key => ({
            wch: Math.max(...dataToExport.map(item => (item[key] || "").toString().length), key.length) + 2
        }));
        const startDate = reportStartDateInput.val();
        const endDate = reportEndDateInput.val();
        const filename = `BaoCaoXNT_Tu_${startDate}_Den_${endDate}.xlsx`;
        XLSX.writeFile(workbook, filename);
    }

    // === EVENT LISTENERS ===
    $('.tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-pane').addClass('hidden');
        $('#tab-content-' + tab).removeClass('hidden');
        if (tab === 'history' && historyListBody.is(':empty')) loadHistoryData();
    });

    searchInput.on('keyup', debounce(applyInventoryFiltersAndRender, 300));
    groupFilter.on('change', applyInventoryFiltersAndRender);
    thicknessFilter.on('change', applyInventoryFiltersAndRender);
    widthFilter.on('change', applyInventoryFiltersAndRender); // ADDED: Event listener for width filter
    typeFilter.on('change', applyInventoryFiltersAndRender);
    
    historyFilterBtn.on('click', () => {
        historyCurrentPage = 1;
        loadHistoryData();
    });

    exportInventoryExcelBtn.on('click', exportInventoryToExcel);
    exportHistoryExcelBtn.on('click', exportHistoryToExcel);
    generateReportBtn.on('click', generateReport);
    exportReportExcelBtn.on('click', exportReportToExcel);

    inventoryListBody.on('click', '.adjust-stock-btn', function() {
        showAdjustModal($(this).closest('tr').data('product-data'));
    });

    inventoryListBody.on('click', '.edit-min-stock-btn', function() {
        showEditMinStockModal($(this).closest('tr').data('product-data'));
    });

    $('#cancel-adjust-btn').on('click', hideAdjustModal);
    $('#cancel-edit-min-stock-btn').on('click', hideEditMinStockModal);

    adjustForm.on('submit', function(e) {
        e.preventDefault();
        const button = $('#confirm-adjust-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...');
        const data = {
            variant_id: $('#adjust-product-id').val(), new_stock: $('#adjust-new-stock').val(),
            transaction_type: $('#adjust-type').val(), notes: $('#adjust-notes').val()
        };
        $.ajax({
            url: 'api/adjust_stock.php', method: 'POST', contentType: 'application/json',
            data: JSON.stringify(data), dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showMessageModal(response.message, 'success');
                    hideAdjustModal();
                    loadInventoryData(true);
                    if ($('#tab-content-history').is(':visible')) {
                        historyCurrentPage = 1;
                        loadHistoryData();
                    }
                } else showMessageModal(response.message, 'error');
            },
            error: () => showMessageModal('Lỗi server khi điều chỉnh kho.', 'error'),
            complete: () => button.prop('disabled', false).text('Xác nhận')
        });
    });

    editMinStockForm.on('submit', function(e) {
        e.preventDefault();
        const button = $('#confirm-edit-min-stock-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...');
        const data = {
            variant_id: $('#edit-min-stock-product-id').val(),
            new_min_stock: $('#edit-min-stock-new').val()
        };
        $.ajax({
            url: 'api/update_minimum_stock.php', method: 'POST', contentType: 'application/json',
            data: JSON.stringify(data), dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showMessageModal(response.message, 'success');
                    hideEditMinStockModal();
                    loadInventoryData(true);
                } else showMessageModal(response.message, 'error');
            },
            error: () => showMessageModal('Lỗi server khi cập nhật định mức.', 'error'),
            complete: () => button.prop('disabled', false).text('Lưu thay đổi')
        });
    });

    // === INITIALIZATION ===
    function init() {
        loadInventoryData();
        loadTransactionTypes();
        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];
        historyEndDate.val(todayStr);
        reportEndDateInput.val(todayStr);

        today.setDate(1);
        const firstDayOfMonthStr = today.toISOString().split('T')[0];
        historyStartDate.val(firstDayOfMonthStr);
        reportStartDateInput.val(firstDayOfMonthStr);
    }
    
    init();
}
