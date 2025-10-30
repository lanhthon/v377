/**
 * =================================================================================
 * MODULE QUẢN LÝ SẢN XUẤT (DANH SÁCH + MODAL CHI TIẾT)
 * Phiên bản nâng cấp: Tích hợp bộ lọc cố định, tab quá hạn, phân trang nâng cao.
 * Cảnh báo: Cần thêm thư viện SheetJS vào trang HTML chính để hoạt động.
 * <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
 * =================================================================================
 */

function initializeProductionOrderListPage() {
    // === DOM Elements ===
    const listBody = $('#production-orders-list-body');
    const paginationControls = $('#pagination-controls');
    const statusFilter = $('#status-filter');
    const typeFilter = $('#type-filter');
    const searchFilter = $('#search-filter');
    const startDateFilter = $('#start-date-filter');
    const endDateFilter = $('#end-date-filter');
    const filterBtn = $('#filter-btn');
    const resetBtn = $('#reset-filter-btn');
    const exportBtn = $('#export-excel-btn-lsx');
    
    const filterTabs = $('.filter-tab-lsx');
    const overdueCountBadge = $('#overdue-count-lsx');
    const limitPerPageSelect = $('#limit-per-page-lsx');
    const paginationInfo = $('#pagination-info-lsx');

    if (!listBody.length) return;

    // === State ===
    let currentPage = 1;
    let currentLimit = parseInt(limitPerPageSelect.val(), 10) || 200;
    let currentFilterType = 'all';

    // === Helper Functions ===
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'N/A'; // Check for invalid date
            return date.toLocaleDateString('vi-VN', {
                day: '2-digit', month: '2-digit', year: 'numeric'
            });
        } catch (e) {
            return 'N/A';
        }
    }
    
    function createOverdueBadge(dueDateString, status) {
        if (status === 'Hoàn thành') {
            return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Đã hoàn thành</span>`;
        }
        if (status === 'Đã hủy' || status === 'Hủy') {
            return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Đã hủy</span>`;
        }
        if (!dueDateString) {
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Chưa có DK</span>';
        }

        try {
            const dueDate = new Date(dueDateString);
            dueDate.setHours(0, 0, 0, 0);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const diffTime = dueDate - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays < 0) {
                return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Quá hạn ${Math.abs(diffDays)} ngày</span>`;
            } else if (diffDays === 0) {
                return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Hoàn thành hôm nay</span>`;
            } else {
                return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Còn ${diffDays} ngày</span>`;
            }
        } catch (e) {
            return 'N/A';
        }
    }
    
    // === Core Functions ===
    function loadProductionOrders(isInitialLoad = false) {
        listBody.html('<tr><td colspan="10" class="text-center p-6"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i></td></tr>');
        paginationControls.empty();
        paginationInfo.text('');

        const params = new URLSearchParams({ 
            page: currentPage,
            limit: currentLimit,
            filter_type: currentFilterType,
            search: searchFilter.val(),
            status: statusFilter.val(),
            type: typeFilter.val(),
            startDate: startDateFilter.val(),
            endDate: endDateFilter.val()
        });
        
        const apiUrl = `api/get_production_order_list.php?${params.toString()}`;
        
        $.getJSON(apiUrl, function(response) {
            if (response.success) {
                renderTable(response.data);
                renderPagination(response.pagination);
                renderPaginationInfo(response.pagination);

                if (isInitialLoad && response.statuses) {
                    populateStatusFilter(response.statuses);
                }

                if (response.overdueCount > 0) {
                    overdueCountBadge.text(response.overdueCount).removeClass('hidden');
                } else {
                    overdueCountBadge.addClass('hidden');
                }
            } else {
                listBody.html(`<tr><td colspan="10" class="text-center p-6 text-red-500">Lỗi: ${response.message || 'Không rõ'}</td></tr>`);
            }
        }).fail(function() {
            listBody.html(`<tr><td colspan="10" class="text-center p-6 text-red-500">Không thể kết nối đến máy chủ.</td></tr>`);
        });
    }

    // === Render Functions ===
    function renderTable(data) {
        listBody.empty();
        if (data.length > 0) {
            data.forEach(po => {
                let statusBadge = '';
                switch (po.TrangThai) {
                    case 'Hoàn thành': statusBadge = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoàn thành</span>'; break;
                    case 'Đã hủy': case 'Hủy': statusBadge = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Đã hủy</span>'; break;
                    case 'Đã duyệt (đang sx)': statusBadge = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Đang SX</span>'; break;
                    default: statusBadge = `<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">${po.TrangThai || 'Chờ duyệt'}</span>`; break;
                }
                
                const overdueBadge = createOverdueBadge(po.NgayHoanThanhUocTinh, po.TrangThai);
                
                const row = `
                    <tr class="hover:bg-gray-50">
                        <td class="p-3 font-semibold text-blue-600">${po.SoLenhSX}</td>
                        <td class="p-3">${po.LoaiLSX}</td>
                        <td class="p-3">${po.SoYCSX || '<span class="font-semibold text-green-600">Lưu kho</span>'}</td>
                        <td class="p-3">${formatDate(po.NgayTao)}</td>
                        <td class="p-3">${formatDate(po.NgayHoanThanhUocTinh)}</td>
                        <td class="p-3">${formatDate(po.NgayHoanThanhThucTe)}</td>
                        <td class="p-3">${po.NguoiYeuCau}</td>
                        <td class="p-3 text-center">${overdueBadge}</td>
                        <td class="p-3 text-center">${statusBadge}</td>
                        <td class="p-3 text-center">
                            <button class="view-po-details-btn text-indigo-600 hover:text-indigo-900 text-sm" data-id="${po.LenhSX_ID}"><i class="fas fa-eye mr-1"></i>Xem</button>
                        </td>
                    </tr>`;
                listBody.append(row);
            });
        } else {
            listBody.html('<tr><td colspan="10" class="text-center p-6 text-gray-500">Không tìm thấy Lệnh sản xuất nào phù hợp.</td></tr>');
        }
    }

    function renderPaginationInfo(pagination) {
        if (!pagination || !pagination.totalRecords || pagination.totalRecords === 0) {
            paginationInfo.text('Không có mục nào');
            return;
        }
        const startItem = (pagination.page - 1) * pagination.limit + 1;
        const endItem = Math.min(startItem + pagination.limit - 1, pagination.totalRecords);
        paginationInfo.text(`Hiển thị ${startItem} đến ${endItem} của ${pagination.totalRecords} mục`);
    }

    function renderPagination(pagination) {
        if (!pagination || pagination.totalPages <= 1) {
            paginationControls.empty();
            return;
        }

        const { page: currentPage, totalPages } = pagination;
        let paginationHtml = `<div class="inline-flex -space-x-px rounded-md shadow-sm">`;
        
        paginationHtml += `<button data-page="${currentPage - 1}" class="page-link relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-500 hover:bg-gray-50 ring-1 ring-inset ring-gray-300 ${currentPage <= 1 ? 'cursor-not-allowed opacity-50' : ''}" ${currentPage <= 1 ? 'disabled' : ''}><i class="fas fa-chevron-left text-sm"></i></button>`;

        const pages = [];
        const SPREAD = 2;
        if (totalPages <= (SPREAD * 2) + 3) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            pages.push(1);
            if (currentPage > SPREAD + 2) pages.push('...');
            for (let i = Math.max(2, currentPage - SPREAD); i <= Math.min(totalPages - 1, currentPage + SPREAD); i++) {
                pages.push(i);
            }
            if (currentPage < totalPages - SPREAD - 1) pages.push('...');
            pages.push(totalPages);
        }

        pages.forEach(p => {
            if (p === '...') {
                paginationHtml += `<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300">...</span>`;
            } else if (p === currentPage) {
                paginationHtml += `<button aria-current="page" class="relative z-10 inline-flex items-center bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">${p}</button>`;
            } else {
                paginationHtml += `<button data-page="${p}" class="page-link relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">${p}</button>`;
            }
        });
        
        paginationHtml += `<button data-page="${currentPage + 1}" class="page-link relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-500 hover:bg-gray-50 ring-1 ring-inset ring-gray-300 ${currentPage >= totalPages ? 'cursor-not-allowed opacity-50' : ''}" ${currentPage >= totalPages ? 'disabled' : ''}><i class="fas fa-chevron-right text-sm"></i></button>`;
        paginationHtml += `</div>`;
        paginationControls.html(paginationHtml);
    }
    
    function populateStatusFilter(statuses) {
        statusFilter.find('option:not(:first)').remove();
        statuses.forEach(status => {
            statusFilter.append(`<option value="${status}">${status}</option>`);
        });
    }

    // =======================================================
    // === CHỨC NĂNG XUẤT EXCEL PHÍA CLIENT (JAVASCRIPT) ===
    // =======================================================
    function exportProductionListToExcel() {
        if (typeof XLSX === 'undefined') {
            alert('Thư viện xuất Excel (SheetJS) chưa được tải. Vui lòng liên hệ quản trị viên.');
            return;
        }

        const button = exportBtn;
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang xuất...');

        // Lấy các tham số lọc hiện tại để yêu cầu toàn bộ dữ liệu
        const params = new URLSearchParams({
            export: 'true',
            filter_type: currentFilterType,
            search: searchFilter.val(),
            status: statusFilter.val(),
            type: typeFilter.val(),
            startDate: startDateFilter.val(),
            endDate: endDateFilter.val()
        });

        const apiUrl = `api/get_production_order_list.php?${params.toString()}`;

        $.getJSON(apiUrl)
            .done(function(response) {
                if (response.success && response.data && response.data.length > 0) {
                    // Chuyển đổi dữ liệu JSON nhận được thành định dạng phù hợp cho Excel
                    const dataToExport = response.data.map(po => {
                        let overdueText = 'N/A';
                        try {
                            if (po.TrangThai === 'Hoàn thành') overdueText = 'Đã hoàn thành';
                            else if (po.TrangThai === 'Đã hủy' || po.TrangThai === 'Hủy') overdueText = 'Đã hủy';
                            else if (!po.NgayHoanThanhUocTinh) overdueText = 'Chưa có DK';
                            else {
                                const dueDate = new Date(po.NgayHoanThanhUocTinh);
                                const today = new Date();
                                dueDate.setHours(0, 0, 0, 0);
                                today.setHours(0, 0, 0, 0);
                                const diffDays = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
                                if (diffDays < 0) overdueText = `Quá hạn ${Math.abs(diffDays)} ngày`;
                                else if (diffDays === 0) overdueText = 'Hoàn thành hôm nay';
                                else overdueText = `Còn ${diffDays} ngày`;
                            }
                        } catch (e) { /* Bỏ qua lỗi nếu ngày không hợp lệ */ }

                        return {
                            'Số Lệnh SX': po.SoLenhSX,
                            'Loại LSX': po.LoaiLSX,
                            'Số YCSX / Mục đích': po.SoYCSX || 'Lưu kho',
                            'Ngày tạo': formatDate(po.NgayTao),
                            'Ngày Hoàn Thành (DK)': formatDate(po.NgayHoanThanhUocTinh),
                            'Ngày SX Xong': formatDate(po.NgayHoanThanhThucTe),
                            'Người yêu cầu': po.NguoiYeuCau,
                            'Cảnh Báo': overdueText,
                            'Trạng thái': po.TrangThai,
                        };
                    });

                    // Tạo workbook và worksheet từ dữ liệu đã xử lý
                    const worksheet = XLSX.utils.json_to_sheet(dataToExport);
                    const workbook = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(workbook, worksheet, "DanhSachLSX");

                    // Tự động điều chỉnh độ rộng cột
                    const cols = Object.keys(dataToExport[0]);
                    const colWidths = cols.map(key => ({ wch: Math.max(key.length, ...dataToExport.map(row => (row[key] || "").toString().length)) + 2 }));
                    worksheet['!cols'] = colWidths;
                    
                    // Tạo và tải file Excel
                    XLSX.writeFile(workbook, "DanhSach_LenhSanXuat.xlsx");

                } else {
                    alert('Không có dữ liệu để xuất hoặc có lỗi xảy ra.');
                }
            })
            .fail(function() {
                alert('Lỗi kết nối khi cố gắng xuất file Excel. Vui lòng thử lại.');
            })
            .always(function() {
                button.prop('disabled', false).html('<i class="fas fa-file-excel mr-2"></i>Xuất Excel');
            });
    }

    // === Event Listeners ===
    filterBtn.on('click', () => { currentPage = 1; loadProductionOrders(); });
    searchFilter.on('keypress', e => { if (e.which === 13) { currentPage = 1; loadProductionOrders(); } });
    resetBtn.on('click', () => {
        $('input, select', '#sticky-filter-bar-lsx').val('');
        currentPage = 1;
        loadProductionOrders();
    });
    
    filterTabs.on('click', function(e) {
        e.preventDefault();
        const filter = $(this).data('filter');
        if (filter === currentFilterType) return;
        
        currentFilterType = filter;
        currentPage = 1;
        filterTabs.removeClass('border-indigo-500 text-indigo-600').addClass('border-transparent text-gray-500');
        $(this).addClass('border-indigo-500 text-indigo-600').removeClass('border-transparent text-gray-500');
        loadProductionOrders();
    });

    limitPerPageSelect.on('change', function() {
        currentLimit = parseInt($(this).val(), 10);
        currentPage = 1;
        loadProductionOrders();
    });

    paginationControls.on('click', '.page-link', function() {
        const page = $(this).data('page');
        if (page && page != currentPage) {
            currentPage = page;
            loadProductionOrders();
        }
    });

    // Gán hàm xuất Excel mới cho sự kiện click của nút
    exportBtn.on('click', exportProductionListToExcel);

    // === Initial Load ===
    loadProductionOrders(true);
}


/**
 * HÀM HỖ TRỢ CHO MODAL (Đã cập nhật)
 */
function createDetailsModalHtml(orderData) {
    const { info, items } = orderData;
    const formatDate = (dateStr) => dateStr ? new Date(dateStr).toLocaleDateString('vi-VN') : 'N/A';
    const calculateDays = (start, end) => {
        if (!start || !end) return '';
        const diffDays = Math.ceil(Math.abs(new Date(end) - new Date(start)) / (1000 * 60 * 60 * 24));
        return `(Tổng: ${diffDays} ngày)`;
    };
    const formatNumber = (num) => (window.App && App.formatNumber) ? App.formatNumber(num) : (num || 0);

    let itemsTableHtml = `<table class="min-w-full text-sm mt-4 table-auto border-collapse">
        <thead style="background-color: #92D050;">
            <tr>
                <th class="p-2 text-left font-semibold text-black border">Stt.</th>
                <th class="p-2 text-left font-semibold text-black border">Mã hàng</th>
                <th class="p-2 text-right font-semibold text-black border">Khối lượng SX</th>
                <th class="p-2 text-right font-semibold text-black border">Đã SX</th>
                <th class="p-2 text-right font-semibold text-black border">Còn lại</th>
                <th class="p-2 text-center font-semibold text-black border">Đơn vị</th>
                <th class="p-2 text-left font-semibold text-black border">Trạng thái</th>
                <th class="p-2 text-left font-semibold text-black border">Ghi chú</th>
            </tr>
        </thead>
        <tbody>`;
        
    if (items && items.length > 0) {
        items.forEach((item, index) => {
            const isUla = info.LoaiLSX === 'ULA';
            
            const quantityRequired = item.SoLuongBoCanSX > 0 ? item.SoLuongBoCanSX : item.SoLuongCayCanSX;
            const quantityProduced = item.SoLuongDaSanXuat || 0;
            const quantityRemaining = quantityRequired - quantityProduced;
            
            const unit = item.DonViTinh || (isUla ? 'Bộ' : 'Cây');
            const status = item.TrangThai || 'Mới'; // Sử dụng TrangThai từ chitiet_lenh_san_xuat
            const remainingColor = quantityRemaining > 0 ? 'text-red-600' : 'text-green-600';

            itemsTableHtml += `
                <tr class="border-t">
                    <td class="p-2 border text-center">${index + 1}</td>
                    <td class="p-2 border">${item.MaHang}</td>
                    <td class="p-2 border text-right font-semibold">${formatNumber(quantityRequired)}</td>
                    <td class="p-2 border text-right font-semibold">${formatNumber(quantityProduced)}</td>
                    <td class="p-2 border text-right font-bold ${remainingColor}">${formatNumber(quantityRemaining)}</td>
                    <td class="p-2 border text-center">${unit}</td>
                    <td class="p-2 border">${status}</td>
                    <td class="p-2 border">${item.GhiChu || ''}</td>
                </tr>`;
        });
    } else {
        itemsTableHtml += `<tr><td colspan="8" class="p-4 text-center border">Không có chi tiết sản phẩm.</td></tr>`;
    }
    itemsTableHtml += `</tbody></table>`;
    
    // Phần HTML còn lại của modal không thay đổi
    return `<div class="flex justify-between items-start mb-4"><div><img src="logo.png" alt="Logo" class="h-12"></div><div class="text-right"><h2 class="text-2xl font-bold text-gray-800">LỆNH SẢN XUẤT - LSX</h2><p class="font-semibold text-lg text-red-600">Số: ${info.SoLenhSX}</p></div></div><div class="border-t border-b py-4 my-4 text-sm"><div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2"><div><p><span class="text-gray-500 w-32 inline-block">Người nhận:</span><strong class="font-semibold">${info.NguoiNhanSX || 'Mr. Thiết'}</strong></p><p><span class="text-gray-500 w-32 inline-block">Đơn vị:</span><strong class="font-semibold">${info.BoPhanSX || 'Đội trưởng SX'}</strong></p><p><span class="text-gray-500 w-32 inline-block">Đơn hàng gốc:</span><strong class="font-semibold">${info.SoYCSX || 'N/A (Lưu kho)'}</strong></p><p><span class="text-gray-500 w-32 inline-block">Người yêu cầu:</span><strong class="font-semibold">${info.NguoiYeuCau || ''}</strong></p></div><div class="text-left md:text-right space-y-2"><p class="inline-block md:block"><span class="p-2 rounded bg-yellow-200"><span class="text-gray-600">Ngày yêu cầu:</span> <strong class="font-bold text-black">${formatDate(info.NgayTao)}</strong></span></p><br><p class="inline-block md:block"><span class="p-2 rounded bg-yellow-200"><span class="text-gray-600">Ngày hoàn thành:</span> <strong class="font-bold text-black">${formatDate(info.NgayHoanThanhUocTinh)}</strong><span class="text-xs ml-2">${calculateDays(info.NgayTao, info.NgayHoanThanhUocTinh)}</span></span></p></div></div></div>${itemsTableHtml}`;
}

// --- CÁC SỰ KIỆN TOÀN CỤC (GLOBAL EVENT HANDLERS) ---
$(document).on('click', '.view-po-details-btn', function() {
    const poId = $(this).data('id');
    $('body').append('<div id="modal-loading" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50"><i class="fas fa-spinner fa-spin text-white text-4xl"></i></div>');
    $.getJSON(`api/get_production_order_details.php?id=${poId}`)
        .done(function(response) {
            if (response.success) {
                const info = response.data.info;
                const modalShell = `<div id="details-modal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-4"><div class="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col"><div class="p-6 overflow-y-auto">${createDetailsModalHtml(response.data)}</div><div class="flex justify-end p-4 border-t bg-gray-50 rounded-b-lg space-x-3"><button id="close-modal-btn" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md text-sm hover:bg-gray-400">Đóng</button><button class="export-btn-excel-modal bg-green-600 text-white px-4 py-2 rounded-md text-sm" data-id="${info.LenhSX_ID}"><i class="fas fa-file-excel mr-2"></i>Xuất Excel</button><button class="export-btn-pdf-modal bg-red-600 text-white px-4 py-2 rounded-md text-sm" data-id="${info.LenhSX_ID}"><i class="fas fa-file-pdf mr-2"></i>Xuất PDF</button></div></div></div>`;
                $('body').append(modalShell);
            } else {
                window.App.showMessageModal('Lỗi: ' + response.message, 'error');
            }
        })
        .fail(() => window.App.showMessageModal('Không thể tải chi tiết lệnh sản xuất.', 'error'))
        .always(() => $('#modal-loading').remove());
});

$(document).on('click', '#close-modal-btn, #details-modal', function(e) {
    if (e.target.id === 'details-modal' || e.target.id === 'close-modal-btn' || $(e.target).closest('#close-modal-btn').length) {
        $('#details-modal').remove();
    }
});
$(document).on('click', '.export-btn-excel-modal', function() {
    const id = $(this).data('id');
    window.location.href = `api/export_production_order_excel.php?id=${id}`;
});
$(document).on('click', '.export-btn-pdf-modal', function() {
    const id = $(this).data('id');
    window.open(`api/export_production_order_pdf.php?id=${id}`, '_blank');
});
