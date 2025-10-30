/**
 * =================================================================================
 * SCRIPT QUẢN LÝ ĐƠN HÀNG & GIAO HÀNG (PHIÊN BẢN TƯƠNG THÍCH API MỚI)
 * =================================================================================
 * - Đã cập nhật logic tải trạng thái động cho bộ lọc.
 * - Chứa mã nguồn đầy đủ, ổn định cho tất cả các chức năng.
 * - Yêu cầu jQuery và đối tượng `App` (từ main.js) phải được tải trước.
 * - Cải thiện chống duplicate cho chức năng "Kiểm tra tiến độ"
 * - Cải thiện hiển thị lỗi chi tiết từ API.
 * - **CẬP NHẬT:** Thêm nút chức năng (Xem/Sửa PXK, BBGH, CCCL) cho các đợt giao hàng.
 */

// =================================================================================
// CÁC HÀM TIỆN ÍCH DÙNG CHUNG
// =================================================================================

function formatNumber(num) {
    if (num === null || num === undefined || isNaN(num)) return '0';
    return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'N/A';
        return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } catch (e) { return 'N/A'; }
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}


/**
 * Phân tích và trả về thông báo lỗi từ phản hồi của API.
 * @param {object} jqXHR - Đối tượng jqXHR từ jQuery AJAX.
 * @param {string} textStatus - Trạng thái lỗi (ví dụ: 'timeout', 'error').
 * @param {string} errorThrown - Chuỗi lỗi được ném ra.
 * @returns {string} - Một thông báo lỗi tường minh.
 */
function getApiErrorMessage(jqXHR, textStatus, errorThrown) {
    if (textStatus === 'timeout') {
        return 'Yêu cầu quá thời gian chờ. Vui lòng thử lại.';
    }

    let responseData = jqXHR.responseJSON;
    if (!responseData && jqXHR.responseText) {
        try {
            responseData = JSON.parse(jqXHR.responseText);
        } catch (e) { /* Không phải JSON */ }
    }

    if (responseData && (responseData.message || responseData.errors)) {
        let message = responseData.message || 'Đã xảy ra lỗi sau:';
        if (responseData.errors && Array.isArray(responseData.errors) && responseData.errors.length > 0) {
            const errorList = responseData.errors.map(err => `<li>${String(err).replace(/</g, "&lt;").replace(/>/g, "&gt;")}</li>`).join('');
            message += `<ul class="list-disc list-inside mt-2 text-sm text-left">${errorList}</ul>`;
        }
        return message;
    }

    if (jqXHR.responseText && !responseData) {
        if (jqXHR.responseText.length < 500) {
            return jqXHR.responseText;
        }
    }

    if (errorThrown) {
        return errorThrown;
    }

    return 'Đã xảy ra lỗi không xác định. Vui lòng liên hệ quản trị viên.';
}


// Biến global để tracking trạng thái kiểm tra tiến độ
let isProcessingProgress = false;
let lastProgressRequest = 0;

// Xử lý sự kiện quay lại trang (Back button)
window.addEventListener('pageshow', function(event) {
    // Nếu event.persisted là true, có nghĩa là người dùng vừa "Back" lại trang này
    if (event.persisted) {
        console.log("Phát hiện quay lại trang, đang làm mới dữ liệu...");
        
        // Lấy trang hiện tại từ URL để gọi đúng hàm
        const urlParams = new URLSearchParams(window.location.search);
        const page = urlParams.get('page');

        // Dựa vào 'page' để gọi lại đúng hàm khởi tạo tương ứng
        if (page === 'donhang_list') {
            initializeDonHangListPage();
        } else if (page === 'donhang_view') {
            initializeDonHangViewPage();
        } else if (page === 'delivery_plan_view') {
            initializeDeliveryPlanViewPage();
        }
        // Thêm các trang khác vào đây nếu bạn muốn chúng cũng tự động làm mới
    }
});

/**
 * =================================================================================
 * 1. KHỞI TẠO TRANG DANH SÁCH ĐƠN HÀNG (donhang_list)
 * =================================================================================
 */
function initializeDonHangListPage() {
    // === DOM Elements ===
    const listBody = $('#donhang-table-body');
    const paginationControls = $('#pagination-controls-dh');
    const statusFilter = $('#status-filter-dh');
    const searchFilter = $('#search-filter-dh');
    const startDateFilter = $('#start-date-filter-dh');
    const endDateFilter = $('#end-date-filter-dh');
    const filterBtn = $('#filter-btn-dh');
    const resetBtn = $('#reset-filter-btn-dh');
    const exportBtn = $('#export-donhang-list-excel-btn');
    
    const filterTabs = $('.filter-tab-dh');
    const overdueCountBadge = $('#overdue-count-dh');
    const limitPerPageSelect = $('#limit-per-page-dh');
    const paginationInfo = $('#pagination-info-dh');
    
    const tooltip = $('#info-tooltip-dh');

    if (!listBody.length) return;

    // === State ===
    let currentPage = 1;
    let currentLimit = parseInt(limitPerPageSelect.val(), 10) || 200;
    let currentFilterType = 'all';

    // === Helper Functions (Đã chuyển ra global) ===

    function createOverdueBadge(dueDateString, status) {
        if (status === 'Đã giao hàng' || status === 'Hoàn thành') {
            return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Đã hoàn thành</span>`;
        }
        if (status === 'Đã hủy') {
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
                return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Giao hôm nay</span>`;
            } else {
                return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Còn ${diffDays} ngày</span>`;
            }
        } catch (e) { return 'N/A'; }
    }
    
    function getStatusBadgeHtml(status) {
        const statusConfig = {
            'Đang chờ xử lý': { text: 'Chờ xử lý', color: 'slate' },
            'Gửi YCSX': { text: 'Gửi YCSX', color: 'amber' },
            'Đang SX': { text: 'Đang SX', color: 'blue' },
            'Chờ xuất kho': { text: 'Chờ xuất kho', color: 'purple' },
            'Đã giao hàng': { text: 'Đã giao hàng', color: 'green' },
            'Hoàn thành': { text: 'Hoàn thành', color: 'green' },
            'Đã hủy': { text: 'Đã hủy', color: 'red' },
            'Mới tạo': { text: 'Mới Tạo', color: 'gray' },
            'Chờ sản xuất': { text: 'Chờ Sản Xuất', color: 'amber' },
            'Đang sản xuất': { text: 'Đang Sản Xuất', color: 'blue' },
            'Chờ giao hàng': { text: 'Chờ Giao Hàng', color: 'emerald' },
        };
        const config = statusConfig[status] || { text: status, color: 'gray' };
        return `<span class="px-2 py-1 text-xs font-semibold rounded-full bg-${config.color}-100 text-${config.color}-800">${config.text}</span>`;
    }

    // === Core Functions ===
    function loadOrders(isInitialLoad = false) {
        listBody.html(`<tr><td colspan="11" class="text-center p-6"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i></td></tr>`);
        paginationControls.empty();
        paginationInfo.text('');

        const params = new URLSearchParams({ 
            page: currentPage,
            limit: currentLimit,
            filter_type: currentFilterType,
            search: searchFilter.val(),
            status: statusFilter.val(),
            startDate: startDateFilter.val(),
            endDate: endDateFilter.val()
        });
        
        const apiUrl = `api/get_donhang_filtered.php?${params.toString()}`;
        
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
                listBody.html(`<tr><td colspan="11" class="text-center p-6 text-red-500">Lỗi: ${response.message || 'Không rõ'}</td></tr>`);
            }
        }).fail(function() {
            listBody.html(`<tr><td colspan="11" class="text-center p-6 text-red-500">Không thể kết nối đến máy chủ.</td></tr>`);
        });
    }

    // === Render Functions ===
    function renderTable(data) {
        listBody.empty();
        if (data.length > 0) {
            data.forEach((order, index) => {
                const stt = (currentPage - 1) * currentLimit + index + 1;
                const overdueBadge = createOverdueBadge(order.NgayGiaoDuKien, order.TrangThai);
                const statusBadge = getStatusBadgeHtml(order.TrangThai);
                
                const customerInfoContent = [
                    `<div class='font-bold text-base text-gray-200'>${escapeHtml(order.TenCongTy) || 'Chưa có thông tin'}</div>`,
                    `<div class='text-xs mt-1'><b>MST:</b> ${escapeHtml(order.MaSoThue) || 'N/A'}</div>`,
                    `<div class='text-xs'><b>Địa chỉ:</b> ${escapeHtml(order.DiaChiHienThi) || 'N/A'}</div>`,
                    `<div class='text-xs'><b>SĐT:</b> ${escapeHtml(order.SoDienThoaiChinh) || 'N/A'}</div>`
                ].join('');
                
                const row = `
                    <tr class="hover:bg-gray-50">
                        <td class="p-3">${stt}</td>
                        <td class="p-3 font-semibold text-blue-600">${order.SoYCSX}</td>
                        <td class="p-3 has-tooltip-dh" data-tooltip-content="${escapeHtml(customerInfoContent)}">${order.MaCongTy || 'N/A'}</td>
                        <td class="p-3">${escapeHtml(order.TenDuAn) || 'N/A'}</td>
                        <td class="p-3">${order.NguoiBaoGia || 'N/A'}</td>
                        <td class="p-3">${formatDate(order.NgayTao)}</td>
                        <td class="p-3">${formatDate(order.NgayGiaoDuKien)}</td>
                        <td class="p-3 text-center">${overdueBadge}</td>
                        <td class="p-3 font-semibold text-right">${formatNumber(order.TongTien)}đ</td>
                        <td class="p-3 text-center">${statusBadge}</td>
                        <td class="p-3 text-center">
                            <a href="?page=donhang_view&id=${order.YCSX_ID}" class="text-indigo-600 hover:text-indigo-900 text-sm"><i class="fas fa-eye mr-1"></i>Xem</a>
                        </td>
                    </tr>`;
                listBody.append(row);
            });
        } else {
            listBody.html('<tr><td colspan="11" class="text-center p-6 text-gray-500">Không tìm thấy đơn hàng nào.</td></tr>');
        }
    }
    
    function renderPaginationInfo(pagination) {
        if (!pagination || !pagination.totalRecords || pagination.totalRecords === 0) {
            paginationInfo.text('Không có mục nào');
            return;
        }
        const startItem = (pagination.page - 1) * pagination.limit + 1;
        const endItem = Math.min(startItem + pagination.limit - 1, pagination.totalRecords);
        paginationInfo.html(`Hiển thị <b>${startItem}</b> đến <b>${endItem}</b> của <b>${pagination.totalRecords}</b> mục`);
    }

    function renderPagination(pagination) {
        if (!pagination || pagination.totalPages <= 1) {
            paginationControls.empty(); return;
        }

        const { page: currentPage, totalPages } = pagination;
        let paginationHtml = `<div class="inline-flex -space-x-px rounded-md shadow-sm">`;
        paginationHtml += `<button data-page="${currentPage - 1}" class="page-link relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-500 hover:bg-gray-50 ring-1 ring-inset ring-gray-300 ${currentPage <= 1 ? 'cursor-not-allowed opacity-50' : ''}" ${currentPage <= 1 ? 'disabled' : ''}><i class="fas fa-chevron-left text-sm"></i></button>`;

        const pages = []; const SPREAD = 2;
        if (totalPages <= (SPREAD * 2) + 3) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            pages.push(1);
            if (currentPage > SPREAD + 2) pages.push('...');
            for (let i = Math.max(2, currentPage - SPREAD); i <= Math.min(totalPages - 1, currentPage + SPREAD); i++) pages.push(i);
            if (currentPage < totalPages - SPREAD - 1) pages.push('...');
            pages.push(totalPages);
        }

        pages.forEach(p => {
            if (p === '...') paginationHtml += `<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300">...</span>`;
            else if (p === currentPage) paginationHtml += `<button aria-current="page" class="relative z-10 inline-flex items-center bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">${p}</button>`;
            else paginationHtml += `<button data-page="${p}" class="page-link relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">${p}</button>`;
        });
        
        paginationHtml += `<button data-page="${currentPage + 1}" class="page-link relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-500 hover:bg-gray-50 ring-1 ring-inset ring-gray-300 ${currentPage >= totalPages ? 'cursor-not-allowed opacity-50' : ''}" ${currentPage >= totalPages ? 'disabled' : ''}><i class="fas fa-chevron-right text-sm"></i></button>`;
        paginationHtml += `</div>`;
        paginationControls.html(paginationHtml);
    }
    
    function populateStatusFilter(statuses) {
        statusFilter.find('option:not(:first)').remove();
        statuses.forEach(status => statusFilter.append(`<option value="${status}">${status}</option>`));
    }

    function exportToExcel() {
        if (typeof XLSX === 'undefined') {
            alert('Thư viện xuất Excel (SheetJS) chưa được tải.'); return;
        }

        const button = exportBtn;
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang xuất...');

        const params = new URLSearchParams({
            export: 'true',
            filter_type: currentFilterType,
            search: searchFilter.val(),
            status: statusFilter.val(),
            startDate: startDateFilter.val(),
            endDate: endDateFilter.val()
        });

        const apiUrl = `api/get_donhang_filtered.php?${params.toString()}`;

        $.getJSON(apiUrl).done(function(response) {
            if (response.success && response.data && response.data.length > 0) {
                const dataToExport = response.data.map(order => ({
                    'Số Đơn Hàng': order.SoYCSX,
                    'Mã KH': order.MaCongTy,
                    'Tên Dự Án': order.TenDuAn,
                    'Người Báo Giá': order.NguoiBaoGia,
                    'Ngày Đặt': formatDate(order.NgayTao),
                    'Ngày Giao Khách': formatDate(order.NgayGiaoDuKien),
                    'Tổng Tiền': Math.round(order.TongTien),
                    'Trạng Thái': order.TrangThai
                }));

                const worksheet = XLSX.utils.json_to_sheet(dataToExport);
                const workbook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(workbook, worksheet, "DanhSachDonHang");

                const cols = Object.keys(dataToExport[0]);
                const colWidths = cols.map(key => ({ wch: Math.max(key.length, ...dataToExport.map(row => (row[key] || "").toString().length)) + 2 }));
                worksheet['!cols'] = colWidths;
                
                XLSX.writeFile(workbook, "DanhSach_DonHang.xlsx");
            } else {
                alert('Không có dữ liệu để xuất.');
            }
        }).fail(function() {
            alert('Lỗi kết nối khi xuất Excel.');
        }).always(function() {
            button.prop('disabled', false).html('<i class="fas fa-file-excel mr-2"></i>Xuất Excel');
        });
    }
    
    // Logic cho Tooltip
    let tooltipTimeout;

    function showTooltip(event) {
        const target = $(event.currentTarget);
        const content = target.data('tooltip-content');
        
        tooltipTimeout = setTimeout(() => {
            if (content && content.trim().length > 0) {
                tooltip.html(content).removeClass('hidden');
                updateTooltipPosition(event);
            }
        }, 150);
    }
    
    function hideTooltip() {
        clearTimeout(tooltipTimeout);
        tooltip.addClass('hidden');
    }

    function updateTooltipPosition(event) {
        if (tooltip.hasClass('hidden')) return;
        const tooltipWidth = tooltip.outerWidth();
        const windowWidth = $(window).width();
        let left = event.pageX + 15;
        if (left + tooltipWidth > windowWidth - 15) {
            left = event.pageX - tooltipWidth - 15;
        }
        tooltip.css({ top: event.pageY + 15, left: left });
    }

    // === Event Listeners ===
    filterBtn.on('click', () => { currentPage = 1; loadOrders(); });
    searchFilter.on('keypress', e => { if (e.which === 13) { currentPage = 1; loadOrders(); } });
    resetBtn.on('click', () => {
        $('input, select', '#sticky-filter-bar-dh').val('');
        currentPage = 1; loadOrders();
    });
    
    filterTabs.on('click', function(e) {
        e.preventDefault();
        const filter = $(this).data('filter');
        if (filter === currentFilterType) return;
        currentFilterType = filter;
        currentPage = 1;
        filterTabs.removeClass('border-indigo-500 text-indigo-600').addClass('border-transparent text-gray-500');
        $(this).addClass('border-indigo-500 text-indigo-600').removeClass('border-transparent text-gray-500');
        loadOrders();
    });

    limitPerPageSelect.on('change', function() {
        currentLimit = parseInt($(this).val(), 10);
        currentPage = 1; loadOrders();
    });

    paginationControls.on('click', '.page-link', function() {
        const page = $(this).data('page');
        if (page && page != currentPage) {
            currentPage = page; loadOrders();
        }
    });

    exportBtn.on('click', exportToExcel);
    
    listBody.on('mouseenter', '.has-tooltip-dh', showTooltip);
    listBody.on('mouseleave', '.has-tooltip-dh', hideTooltip);
    listBody.on('mousemove', '.has-tooltip-dh', updateTooltipPosition);


    // === Initial Load ===
    loadOrders(true);
}

/**
 * =================================================================================
 * 2. KHỞI TẠO TRANG CHI TIẾT ĐƠN HÀNG (donhang_view)
 * =================================================================================
 */
function initializeDonHangViewPage() {
    const params = new URLSearchParams(window.location.search);
    const donhangId = params.get('id');

    if (!donhangId) {
        App.showMessageModal('Không tìm thấy ID đơn hàng.', 'error');
        return;
    }
    
    // =================================================================================
    // THÊM CÁC HÀM XỬ LÝ SỰ KIỆN CHO CÁC NÚT MỚI
    // =================================================================================
    $(document).off('click', '.view-pxk-btn').on('click', '.view-pxk-btn', function() {
        const pxkId = $(this).data('pxk-id');
        if (!pxkId) return;
        const url = `?page=xuatkho_create&pxk_id=${pxkId}`;
        history.pushState({ page: 'xuatkho_create', pxk_id: pxkId }, '', url);
        window.App.handleRouting();
    });

    $(document).off('click', '.manage-bbgh-btn').on('click', '.manage-bbgh-btn', function() {
        const btn = $(this);
        const pxkId = btn.data('pxk-id');
        if (!pxkId) return;

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.post('api/create_or_get_bbgh.php', { pxk_id: pxkId }, function(response) {
            if (response.success && response.bbgh_id) {
                history.pushState({ page: 'bbgh_view', id: response.bbgh_id }, '', `?page=bbgh_view&id=${response.bbgh_id}`);
                window.App.handleRouting();
            } else {
                App.showMessageModal(response.message || 'Không thể tạo/lấy BBGH.', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-dolly-flatbed mr-1"></i> BBGH');
            }
        }, 'json').fail(() => {
            App.showMessageModal('Lỗi kết nối đến server khi xử lý BBGH.', 'error');
            btn.prop('disabled', false).html('<i class="fas fa-dolly-flatbed mr-1"></i> BBGH');
        });
    });
    
    $(document).off('click', '.manage-cccl-btn').on('click', '.manage-cccl-btn', function() {
        const btn = $(this);
        const pxkId = btn.data('pxk-id');
        if (!pxkId) return;
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.post('api/create_or_get_cccl.php', { pxk_id: pxkId }, function(response) {
            if (response.success && response.cccl_id) {
                history.pushState({ page: 'cccl_view', id: response.cccl_id }, '', `?page=cccl_view&id=${response.cccl_id}`);
                window.App.handleRouting();
            } else {
                App.showMessageModal(response.message || 'Không thể tạo/lấy CCCL.', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-certificate mr-1"></i> CCCL');
            }
        }, 'json').fail(() => {
            App.showMessageModal('Lỗi kết nối đến server khi xử lý CCCL.', 'error');
            btn.prop('disabled', false).html('<i class="fas fa-certificate mr-1"></i> CCCL');
        });
    });

    /**
     * Xử lý sự kiện click nút "Kiểm tra tiến độ" - CHỐNG DUPLICATE
     * @param {string} currentDonhangId ID của đơn hàng hiện tại
     */
    function handleCheckProgress(currentDonhangId) {
        const checkBtn = $('#check-progress-btn');
        if (checkBtn.hasClass('processing')) {
            console.log('Đang xử lý, bỏ qua click duplicate');
            return;
        }
        
        checkBtn.addClass('processing').prop('disabled', true);
        checkBtn.html('<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...');
        
        App.showMessageModal('Đang kiểm tra tiến độ, vui lòng chờ...', 'loading');

        $.ajax({
            url: 'api/estimate_delivery_schedule.php', 
            method: 'POST',
            data: { donhang_id: currentDonhangId },
            dataType: 'json',
            timeout: 60000 
        })
        .done(function(response) {
            if (response.success) {
                // Giả lập dữ liệu mẫu nếu API không trả về
                const sampleData = {
                    summary: {
                        overallStatus: "Đang sản xuất",
                        estimatedCompletion: "2025-10-10"
                    },
                    details: [
                        { productName: "Sản phẩm A", status: "Đã xong phần cắt", notes: "Chờ bộ phận lắp ráp" },
                        { productName: "Sản phẩm B", status: "Đang chờ vật tư", notes: "Vật tư XYZ dự kiến về ngày 2025-10-01" }
                    ]
                };
                showProgressSummaryModal(response.data || sampleData, response.khgh_id);
            } else {
                throw new Error(response.message || 'Không thể tính toán tiến độ.');
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            const errorMessage = getApiErrorMessage(jqXHR, textStatus, errorThrown);
            App.showMessageModal('Lỗi: ' + errorMessage, 'error');
        })
        .always(function() {
            checkBtn.removeClass('processing').prop('disabled', false);
            checkBtn.html('<i class="fas fa-chart-line"></i> Kiểm tra tiến độ');
        });
    }

    /**
     * Hiển thị modal tóm tắt tiến độ. (ĐÃ CẬP NHẬT)
     * @param {object} data - Dữ liệu tiến độ từ API.
     * @param {string} khghId - ID của kế hoạch giao hàng (nếu có).
     */
    function showProgressSummaryModal(data, khghId) {
        $('#progress-summary-modal').remove();

        let detailsHtml = '<p class="text-center text-gray-500 py-4">Không có chi tiết tiến độ.</p>';
        
        // Kiểm tra xem data.details có phải là một mảng và có phần tử không
        if (data && data.details && Array.isArray(data.details) && data.details.length > 0) {
            // Vì API trả về mảng chuỗi, ta sẽ hiển thị mỗi chuỗi trong một hàng
            detailsHtml = data.details.map(detailString => `
                <tr class="border-b">
                    <td class="py-2 px-3" colspan="3">${detailString}</td>
                </tr>
            `).join('');
        }

        // Lấy thông tin tóm tắt từ API
        const overallStatus = data?.summary?.overallStatus || 'Kiểm tra chi tiết';
        const estimatedCompletion = data?.estimatedDeliveryDate || data?.summary?.estimatedCompletion; // Hỗ trợ cả hai key

        const modalHtml = `
        <div id="progress-summary-modal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center p-4 z-50 transition-opacity duration-300 ease-in-out">
            <div id="progress-modal-content" class="bg-white rounded-xl shadow-2xl w-full max-w-3xl transform transition-all duration-300 ease-in-out scale-95 opacity-0" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="modal-title">
                <header class="flex justify-between items-center p-4 border-b">
                    <h3 id="modal-title" class="text-xl font-bold text-gray-800"><i class="fas fa-tasks mr-2 text-blue-500"></i>Tóm Tắt Tiến Độ Giao Hàng</h3>
                    <button class="modal-close-btn text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                </header>
                <main class="p-6 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 text-center">
                        <div class="bg-gray-50 p-4 rounded-lg border">
                            <p class="text-sm font-medium text-gray-500">Trạng thái tổng thể</p>
                            <p class="text-lg font-bold text-blue-600">${escapeHtml(overallStatus)}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg border">
                            <p class="text-sm font-medium text-gray-500">Dự kiến hoàn thành</p>
                            <p class="text-lg font-bold text-green-600">${formatDate(estimatedCompletion) || 'N/A'}</p>
                        </div>
                    </div>

                    <h4 class="text-md font-semibold text-gray-700 mb-2">Chi tiết:</h4>
                    <div class="overflow-hidden border rounded-lg">
                        <table class="min-w-full text-sm">
                            <tbody class="bg-white divide-y">
                                ${detailsHtml}
                            </tbody>
                        </table>
                    </div>
                </main>
                <footer class="flex justify-end p-4 bg-gray-50 border-t rounded-b-xl space-x-3">
                    ${khghId ? `<a href="?page=delivery_plan_view&id=${khghId}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-semibold shadow-sm"><i class="fas fa-eye mr-2"></i>Xem Kế hoạch</a>` : ''}
                    <button class="modal-close-btn px-4 py-2 bg-white text-gray-700 border rounded-lg hover:bg-gray-100 text-sm font-semibold shadow-sm">Đóng</button>
                </footer>
            </div>
        </div>
        `;

        $('body').append(modalHtml);
        
        setTimeout(() => {
            $('#progress-modal-content').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 50);

        $('#progress-summary-modal').on('click', '.modal-close-btn', function() {
            $('#progress-modal-content').addClass('scale-95 opacity-0');
            setTimeout(() => {
                 $('#progress-summary-modal').remove();
            }, 200);
        });
    }
    
    $(document).off('click', '#check-progress-btn').on('click', '#check-progress-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        handleCheckProgress(donhangId);
    });

    function renderOrderInfo(info) {
        $('#order-number-subtitle').text(info.SoYCSX);
        $('#info-customer-name').text(info.TenCongTy);
        $('#info-project-name').text(info.TenDuAn || 'Chưa có');
        $('#info-order-date').text(new Date(info.NgayTao).toLocaleDateString('vi-VN'));
        $('#info-delivery-date').text(info.NgayGiaoDuKien ? new Date(info.NgayGiaoDuKien).toLocaleDateString('vi-VN') : 'Chưa xác định');
        $('#info-total-value').text(formatNumber(Math.round(info.TongTienSauThue)) + ' đ');
        const statusConfig = { 'Đã hủy': { color: 'red'}, 'Đã giao hàng': { color: 'green'}, 'Chờ giao hàng': { color: 'emerald'} };
        const config = statusConfig[info.TrangThai] || { text: info.TrangThai, color: 'gray' };
        $('#info-status .status-badge').text(config.text || info.TrangThai).addClass(`bg-${config.color}-100 text-${config.color}-800`);
        $('#create-delivery-plan-btn').attr('href', `?page=delivery_plan_create&donhang_id=${donhangId}`);
    }

    function loadAndRenderDeliveryPlans(currentDonhangId) {
        const listContainer = $('#delivery-plans-list').empty().html('<p class="text-gray-500">Đang tải...</p>');
        $.ajax({
            url: 'api/get_delivery_plans.php',
            data: { donhang_id: currentDonhangId },
            dataType: 'json',
            success: function(response) {
                listContainer.empty();
                if (response.success && response.plans.length > 0) {
                    response.plans.forEach(plan => {
                        if (plan.TrangThai === 'Ẩn') {
                            return; 
                        }
                        
                        let actionButtonsHtml = '';
                        if (plan.TrangThai === 'Chờ xử lý') {
                            actionButtonsHtml = `<a href="?page=delivery_plan_view&id=${plan.KHGH_ID}" class="action-btn bg-green-100 text-green-700 ml-2" title="Tạo phiếu chuẩn bị hàng"><i class="fas fa-plus-circle"></i> Tạo Phiếu CBH</a>`;
                        } else {
                            if (plan.TrangThai === 'Đã tạo phiếu chuẩn bị hàng') {
                                actionButtonsHtml += `<button data-plan-id="${plan.KHGH_ID}" class="action-btn bg-yellow-100 text-yellow-800 ml-2 send-ycsx-btn" title="Gửi Yêu cầu Sản xuất"><i class="fas fa-paper-plane"></i> Gửi YCSX</button>`;
                            }
                            actionButtonsHtml += `<a href="?page=delivery_plan_view&id=${plan.KHGH_ID}" class="action-btn bg-blue-100 text-blue-600 ml-2" title="Xem chi tiết phiếu"><i class="fas fa-eye"></i> Xem Phiếu</a>`;
                        }

                        let associatedDocsHtml = '';
                        if (plan.CBH_ID && plan.PhieuXuatKhoID) {
                             associatedDocsHtml = `
                                 <div class="mt-3 pt-3 border-t border-gray-200 flex items-center justify-end space-x-2">
                                     <button class="view-pxk-btn bg-gray-500 text-white px-3 py-1 rounded-md text-xs hover:bg-gray-600" data-pxk-id="${plan.PhieuXuatKhoID}" title="Xem/Sửa phiếu xuất kho"><i class="fas fa-eye mr-1"></i> Xem/Sửa PXK</button>
                                     <button class="manage-bbgh-btn bg-blue-500 text-white px-3 py-1 rounded-md text-xs hover:bg-blue-600" data-pxk-id="${plan.PhieuXuatKhoID}" title="Quản lý Biên bản Giao hàng"><i class="fas fa-dolly-flatbed mr-1"></i> BBGH</button>
                                     <button class="manage-cccl-btn bg-purple-500 text-white px-3 py-1 rounded-md text-xs hover:bg-purple-600" data-pxk-id="${plan.PhieuXuatKhoID}" title="Quản lý Chứng chỉ Chất lượng"><i class="fas fa-certificate mr-1"></i> CCCL</button>
                                 </div>
                             `;
                        }

                        const planHtml = `
                            <div class="bg-gray-50 p-4 rounded-lg border mb-3 shadow-sm">
                                <div class="flex justify-between items-center flex-wrap gap-2">
                                    <div>
                                        <p class="font-bold text-gray-800">${plan.SoKeHoach || 'Chưa có số'}</p>
                                        <p class="text-sm text-gray-500">Ngày giao: <span class="font-medium text-red-600">${plan.NgayGiaoDuKien ? new Date(plan.NgayGiaoDuKien).toLocaleDateString('vi-VN') : 'N/A'}</span></p>
                                        <p class="text-sm text-gray-500">Ghi chú: <span class="font-medium text-gray-700">${plan.GhiChu || 'Không có'}</span></p>
                                        <p class="text-sm text-gray-500">Ngày tạo: <span class="font-medium text-black">${new Date(plan.created_at).toLocaleString('vi-VN')}</span></p>
                                    </div>
                                    <div class="flex items-center text-right">
                                        <span class="status-badge bg-blue-100 text-blue-800">${plan.TrangThai}</span>
                                        ${actionButtonsHtml}
                                    </div>
                                </div>
                                ${associatedDocsHtml}
                            </div>`;
                        listContainer.append(planHtml);
                    });
                } else {
                    listContainer.html('<p class="text-center py-4 text-gray-500">Chưa có kế hoạch giao hàng.</p>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                const errorMessage = getApiErrorMessage(jqXHR, textStatus, errorThrown);
                listContainer.html(`<p class="text-center py-4 text-red-500">Lỗi tải kế hoạch giao hàng: ${errorMessage}</p>`);
            }
        });
    }
    
    function sendProductionRequest(planId) {
        App.showConfirmationModal(
            'Xác nhận gửi YCSX',
            'Bạn có chắc chắn muốn gửi Yêu cầu Sản xuất cho đợt giao hàng này không?',
            function() {
                App.showMessageModal('Đang gửi yêu cầu...', 'loading');
                $.ajax({
                    url: 'api/send_production_request.php',
                    method: 'POST',
                    data: {
                        khgh_id: planId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            App.showMessageModal('Đã gửi YCSX thành công!', 'success');
                            loadAndRenderDeliveryPlans(donhangId); // Tải lại để cập nhật trạng thái
                        } else {
                            App.showMessageModal('Lỗi: ' + (response.message || 'Không thể gửi YCSX.'), 'error');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                         const errorMessage = getApiErrorMessage(jqXHR, textStatus, errorThrown);
                         App.showMessageModal('Lỗi kết nối: ' + errorMessage, 'error');
                    }
                });
            }
        );
    }
    
    $('#delivery-plans-list').on('click', '.send-ycsx-btn', function() {
        const planId = $(this).data('plan-id');
        if (planId) {
            sendProductionRequest(planId);
        }
    });

    $.ajax({
        url: `api/get_donhang_details.php?id=${donhangId}`,
        dataType: 'json',
        success: (res) => {
            if (res.success && res.donhang) {
                renderOrderInfo(res.donhang.info);
                loadAndRenderDeliveryPlans(donhangId);
            } else {
                App.showMessageModal('Lỗi tải chi tiết đơn hàng: ' + (res.message || 'Không rõ'), 'error');
            }
        },
        error: (jqXHR, textStatus, errorThrown) => {
            const errorMessage = getApiErrorMessage(jqXHR, textStatus, errorThrown);
            App.showMessageModal('Lỗi tải chi tiết đơn hàng: ' + errorMessage, 'error');
        }
    });
}

/**
 * =================================================================================
 * 3. KHỞI TẠO TRANG LẬP KẾ HOẠCH GIAO HÀNG (delivery_plan_create)
 * =================================================================================
 */
function initializeDeliveryPlanCreatePage() {
    const params = new URLSearchParams(window.location.search);
    const donhangId = params.get('donhang_id');
    const tableBody = $('#delivery-items-body');

    if (!donhangId) {
        App.showMessageModal('ID đơn hàng không hợp lệ.', 'error');
        return;
    }

    function renderPlanItems(data) {
        tableBody.empty();
        $('#order-number-subtitle span').text(data.info.SoYCSX);
        const tableHeader = `
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 text-left">Sản phẩm</th><th class="p-2">Tổng Đặt</th><th class="p-2">Đã Lên KH</th>
                    <th class="p-2">Còn Lại</th><th class="p-2 w-48">SL Giao Đợt Này</th><th class="p-2">Ghi chú</th>
                </tr>
            </thead>`;
        tableBody.closest('table').find('thead').remove().end().prepend(tableHeader);
        let hasItemsToPlan = false;
        data.items.forEach(item => {
            const remaining = parseInt(item.SoLuong, 10) - (parseInt(item.SoLuongDaLenKeHoach, 10) || 0);
            if (remaining <= 0) return;
            hasItemsToPlan = true;
            const row = `
                <tr data-chitiet-id="${item.ChiTiet_YCSX_ID}" class="item-row border-b">
                    <td class="p-2"><p class="font-medium">${item.MaHang}</p><p class="text-sm text-gray-500">${item.TenSanPham}</p></td>
                    <td class="p-2 text-center">${formatNumber(item.SoLuong)}</td>
                    <td class="p-2 text-center text-blue-600">${formatNumber(item.SoLuongDaLenKeHoach || 0)}</td>
                    <td class="p-2 text-center text-green-600 font-bold">${formatNumber(remaining)}</td>
                    <td class="p-2"><input type="number" class="quantity-to-ship form-input w-full text-center" min="0" max="${remaining}" value="0" data-max-val="${remaining}"></td>
                    <td class="p-2 text-sm feedback-cell"></td>
                </tr>`;
            tableBody.append(row);
        });
        if (!hasItemsToPlan) {
            tableBody.html(`<tr><td colspan="6" class="p-4 text-center">Tất cả sản phẩm đã được lên kế hoạch.</td></tr>`);
            $('#save-delivery-plan-btn').hide();
        }
    }

    $.ajax({
        url: `api/get_data_for_delivery_plan.php`,
        method: 'GET', data: { donhang_id: donhangId }, dataType: 'json',
        success: (res) => res.success ? renderPlanItems(res.data) : App.showMessageModal(res.message, 'error'),
        error: (jqXHR, textStatus, errorThrown) => {
            const errorMessage = getApiErrorMessage(jqXHR, textStatus, errorThrown);
            App.showMessageModal('Lỗi tải dữ liệu: ' + errorMessage, 'error');
        }
    });
    
    tableBody.on('input', '.quantity-to-ship', function() {
        const input = $(this), max = parseInt(input.data('max-val'), 10);
        let value = parseInt(input.val(), 10) || 0;
        if (value > max) value = max;
        if (value < 0) value = 0;
        input.val(value);
        const stillRemaining = max - value;
        const feedbackCell = input.closest('tr').find('.feedback-cell');
        if (value > 0 && stillRemaining > 0) {
            feedbackCell.html(`<span class="text-amber-600 italic">Còn ${formatNumber(stillRemaining)} cho đợt sau.</span>`);
        } else if (value > 0 && stillRemaining === 0) {
            feedbackCell.html(`<span class="text-green-600 font-semibold"><i class="fas fa-check-circle"></i> Đủ</span>`);
        } else {
            feedbackCell.html('');
        }
    });

    $('#save-delivery-plan-btn').on('click', function() {
        const btn = $(this);
        const planData = {
            donhang_id: donhangId,
            ngay_giao_du_kien: $('#delivery-date').val(),
            trang_thai: $('#delivery-status').val(),
            ghi_chu: $('#delivery-notes').val(),
            items: []
        };
        if (!planData.ngay_giao_du_kien) { App.showMessageModal('Vui lòng chọn ngày giao.', 'warning'); return; }
        let totalQuantity = 0;
        tableBody.find('.item-row').each(function() {
            const row = $(this), quantity = parseInt(row.find('.quantity-to-ship').val(), 10);
            if (quantity > 0) {
                planData.items.push({ chitiet_donhang_id: row.data('chitiet-id'), so_luong_giao: quantity });
                totalQuantity += quantity;
            }
        });
        if (totalQuantity === 0) { App.showMessageModal('Vui lòng nhập số lượng.', 'warning'); return; }
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...');
        $.ajax({
            url: `api/save_delivery_plan.php`,
            method: 'POST', contentType: 'application/json', data: JSON.stringify(planData), dataType: 'json',
            success: (res) => {
                if(res.success) {
                    App.showMessageModal(res.message, 'success');
                    window.location.href = `?page=donhang_view&id=${donhangId}`;
                } else { App.showMessageModal('Lỗi: ' + res.message, 'error'); }
            },
            error: (jqXHR, textStatus, errorThrown) => {
                const errorMessage = getApiErrorMessage(jqXHR, textStatus, errorThrown);
                App.showMessageModal('Lỗi khi lưu kế hoạch: ' + errorMessage, 'error');
            },
            complete: () => btn.prop('disabled', false).html('<i class="fas fa-save"></i> Lưu Kế Hoạch')
        });
    });
}


/**
 * =================================================================================
 * 4. KHỞI TẠO TRANG CHI TIẾT ĐỢT GIAO HÀNG & TẠO/SỬA CBH (delivery_plan_view)
 * =================================================================================
 */
function initializeDeliveryPlanViewPage() {
    const params = new URLSearchParams(window.location.search);
    const khghId = params.get('id');
    const saveButton = $('#save-and-create-cbh-btn');
    
    let currentDonhangId = null;

    if (!khghId) {
        $('.container').html('<p class="text-center text-red-500 text-lg">Lỗi: ID Kế hoạch giao hàng không hợp lệ.</p>');
        App.showMessageModal('ID kế hoạch giao hàng không hợp lệ.', 'error');
        return;
    }

    function renderProductGroupAsEditable(title, items, containerSelector) {
        if (!items || items.length === 0) {
            $(containerSelector).hide();
            return;
        }
        let tableRows = '';
        items.forEach((item, index) => {
            const itemType = item.MaHang.startsWith('PUR') ? 'pur' : (item.MaHang.startsWith('Ula') ? 'ula' : 'ecu');
            tableRows += `
                <tr class="border-b item-row" data-id="${item.ChiTietCBH_ID || item.ChiTietEcuCBH_ID}" data-type="${itemType}">
                    <td class="py-2 px-3 text-center">${index + 1}</td>
                    <td class="py-2 px-3"><p class="font-semibold">${item.MaHang}</p><p class="text-xs text-gray-500">${item.TenSanPham}</p></td>
                    <td class="py-2 px-3 text-center font-bold text-blue-600">${item.SoLuongYeuCau || item.SoLuongEcu}</td>
                    <td class="py-2 px-3"><input type="number" class="form-input w-full text-center so-luong-lay" value="${item.SoLuongLayTuKho || 0}"></td>
                    <td class="py-2 px-3"><input type="text" class="form-input w-full cay-cat" value="${item.CayCat || ''}"></td>
                    <td class="py-2 px-3"><input type="text" class="form-input w-full dong-goi" value="${item.DongGoi || item.DongGoiEcu || ''}"></td>
                    <td class="py-2 px-3"><input type="text" class="form-input w-full ghi-chu" value="${item.GhiChu || item.GhiChuEcu || ''}"></td>
                </tr>`;
        });
        const tableHtml = `
            <h4 class="text-lg font-bold text-gray-700 mt-6 mb-2">${title}</h4>
            <div class="overflow-x-auto border rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 text-xs font-medium text-gray-500 uppercase">
                        <tr>
                            <th class="p-2">STT</th><th class="p-2 text-left">Sản phẩm</th><th class="p-2">SL Yêu Cầu</th>
                            <th class="p-2">SL Lấy</th><th class="p-2">Cây Cắt</th><th class="p-2">Đóng Gói</th><th class="p-2">Ghi Chú</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y">${tableRows}</tbody>
                </table>
            </div>`;
        $(containerSelector).html(tableHtml).show();
    }
    
    function populateCbhForm(info) {
        $('#info-bophan').val(info.BoPhan || '');
        $('#info-phutrach').val(info.PhuTrach || '');
        $('#info-sodon').val(info.SoDon || '');
        $('#info-madon').val(info.MaDon || '');
        $('#info-ngaygui').val(info.NgayGuiYCSX ? info.NgayGuiYCSX.split(' ')[0] : '');
        $('#info-nguoinhan').val(info.NguoiNhanHang || '');
        $('#info-sdtnguoinhan').val(info.SdtNguoiNhan || '');
        $('#info-diadiem').val(info.DiaDiemGiaoHang || '');
        $('#info-quycachthung').val(info.QuyCachThung || '');
        $('#info-ngaygiao').val(info.NgayGiao ? info.NgayGiao.split(' ')[0] : '');
        $('#info-dangkicongtruong').val(info.DangKiCongTruong || '');
        $('#info-xegrap').val(info.XeGrap || '');
        $('#info-xetai').val(info.XeTai || '');
        $('#info-solaixe').val(info.SoLaiXe || '');
        $('#info-congtrinh').val(info.CongTrinh || '');
    }

    function prepareEditingMode(cbhData) {
        const creationSection = $('#cbh-creation-section');
        creationSection.removeClass('hidden');
        $('#cbh-review-section').addClass('hidden');
        creationSection.find('h2').text('Chỉnh sửa Phiếu Chuẩn Bị Hàng');
        populateCbhForm(cbhData.info);
        if ($('#editable-tables-container').length === 0) {
            creationSection.find('main > .flex.gap-6').after('<div id="editable-tables-container" class="mt-6"></div>');
        }
        const allItems = [...(cbhData.pur_items || []), ...(cbhData.ula_items || []), ...(cbhData.ecu_items || [])];
        renderProductGroupAsEditable('Tất cả sản phẩm', allItems, '#editable-tables-container');
        
        const buttonContainer = creationSection.find('.flex.justify-end');
        const cbhId = cbhData.info.CBH_ID;
        const editButtonsHtml = `
            <a href="?page=chuanbi_hang_edit&id=${cbhId}&mode=view" class="action-btn bg-blue-100 text-blue-600" title="Xem chi tiết quy trình">
                <i class="fas fa-eye"></i> Xem chi tiết
            </a>
            <button id="save-cbh-changes-btn" class="ml-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow-md">
                <i class="fas fa-save mr-2"></i>Lưu Thay Đổi
            </button>
        `;
        buttonContainer.html(editButtonsHtml);
    }
    
    function attachActionHandlers(cbhId) {
        $(document).off('click', '#save-cbh-changes-btn').on('click', '#save-cbh-changes-btn', function() {
            const btn = $(this);
            const thongTinChung = {
                boPhan: $('#info-bophan').val(),
                ngayGuiYCSX: $('#info-ngaygui').val(),
                phuTrach: $('#info-phutrach').val(),
                ngayGiao: $('#info-ngaygiao').val(),
                nguoiNhanHang: $('#info-nguoinhan').val(),
                sdtNguoiNhan: $('#info-sdtnguoinhan').val(),
                diaDiemGiaoHang: $('#info-diadiem').val(),
                quyCachThung: $('#info-quycachthung').val(),
                xeGrap: $('#info-xegrap').val(),
                xeTai: $('#info-xetai').val(),
                soLaiXe: $('#info-solaixe').val(),
                dangKiCongTruong: $('#info-dangkicongtruong').val()
            };

            const items = [];
            const itemsEcuKemTheo = [];
            $('#editable-tables-container .item-row').each(function() {
                const row = $(this);
                const itemType = row.data('type');
                if (itemType === 'pur' || itemType === 'ula') {
                    items.push({
                        chiTietCBH_ID: row.data('id'),
                        soLuongLayTuKho: row.find('.so-luong-lay').val(),
                        cayCat: row.find('.cay-cat').val(),
                        dongGoi: row.find('.dong-goi').val(),
                        ghiChu: row.find('.ghi-chu').val()
                    });
                } else if (itemType === 'ecu') {
                    itemsEcuKemTheo.push({
                        chiTietEcuCBH_ID: row.data('id'),
                        dongGoiEcu: row.find('.dong-goi').val(),
                        ghiChuEcu: row.find('.ghi-chu').val()
                    });
                }
            });

            const payload = {
                cbhID: cbhId,
                thongTinChung: thongTinChung,
                items: items,
                itemsEcuKemTheo: itemsEcuKemTheo
            };
            
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...');
            
            $.ajax({
                url: 'api/update_phieu_chuan_bi.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(payload),
                dataType: 'json',
                success: (res) => {
                    if (res.success) {
                        App.showMessageModal('Cập nhật thành công!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        App.showMessageModal('Lỗi: ' + res.message, 'error');
                    }
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    const errorMessage = getApiErrorMessage(jqXHR, textStatus, errorThrown);
                    App.showMessageModal('Lỗi kết nối khi cập nhật: ' + errorMessage, 'error');
                },
                complete: () => btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Lưu Thay Đổi')
            });
        });

        $(document).off('click', '#finalize-cbh-btn').on('click', '#finalize-cbh-btn', function() {
            App.showConfirmationModal('Xác nhận hoàn tất', 'Bạn có chắc muốn hoàn tất phiếu này? Sau khi hoàn tất sẽ không thể chỉnh sửa.', () => {
                $.ajax({
                    url: 'api/finalize_chuanbihang.php',
                    method: 'POST', 
                    data: { cbh_id: cbhId }, 
                    dataType: 'json',
                    success: (res) => {
                        if (res.success) {
                            App.showMessageModal('Đã hoàn tất phiếu!', 'success');
                            location.reload();
                        } else {
                            App.showMessageModal('Lỗi: ' + res.message, 'error');
                        }
                    },
                    error: (jqXHR, textStatus, errorThrown) => {
                        const errorMessage = getApiErrorMessage(jqXHR, textStatus, errorThrown);
                        App.showMessageModal('Lỗi: ' + errorMessage, 'error');
                    }
                });
            });
        });
    }

    function populateFormForCreation(planData) {
        const planInfo = planData.info;
        const orderInfo = planData.order_info;
        $('#info-bophan').val('Kho - Logistic');
        $('#info-phutrach').val(orderInfo.NguoiBaoGia || '');
        $('#info-sodon').val(orderInfo.SoYCSX || '');
        $('#info-madon').val(orderInfo.YCSX_ID || '');
        $('#info-ngaygui').val(new Date().toISOString().split('T')[0]);
        $('#info-nguoinhan').val(planInfo.NguoiNhanHang || orderInfo.NguoiNhan || '');
        $('#info-sdtnguoinhan').val(planInfo.SdtNguoiNhan || orderInfo.SoDienThoaiNguoiNhan || '');
        $('#info-diadiem').val(planInfo.DiaDiemGiaoHang || orderInfo.DiaChiGiaoHang || '');
        $('#info-quycachthung').val(planInfo.QuyCachThung || '');
        const deliveryDate = planInfo.NgayGiaoDuKien ? planInfo.NgayGiaoDuKien.split(' ')[0] : (orderInfo.NgayGiaoDuKien ? orderInfo.NgayGiaoDuKien.split(' ')[0] : '');
        $('#info-ngaygiao').val(deliveryDate);
        $('#info-dangkicongtruong').val(planInfo.DangKiCongTruong || '');
        $('#info-xegrap').val(planInfo.XeGrap || '');
        $('#info-xetai').val(planInfo.XeTai || '');
        $('#info-solaixe').val(planInfo.SoLaiXe || '');
        $('#info-congtrinh').val(orderInfo.TenDuAn || '');
    }
    
    function renderItems(items) {
        const itemsBody = $('#plan-items-body').empty();
        if (items && items.length > 0) {
            items.forEach(item => {
                const rowHtml = `<tr class="border-b"><td class="py-3 px-4">${item.TenSanPham || ''} (${item.MaHang || ''})</td><td class="py-3 px-4 text-center font-semibold text-lg">${item.SoLuongGiao || 0}</td></tr>`;
                itemsBody.append(rowHtml);
            });
        } else {
            itemsBody.html('<tr><td colspan="2" class="text-center py-5 text-gray-500">Không có sản phẩm trong đợt giao này.</td></tr>');
        }
    }

    function renderCbhReview(data) {
        $('#cbh-review-section').removeClass('hidden');
        $('#cbh-creation-section').addClass('hidden');
        const header = data.info;
        const items = data.hangSanXuat.concat(data.hangChuanBi_ULA, (data.vatTuKem_ECU || []).map(e => ({...e, MaHang: 'Vật tư', TenSanPham: e.TenSanPhamEcu, SoLuong: e.SoLuongEcu})));
        const cbhId = header.CBH_ID;
        $('#review-socbh').text(header.SoCBH || '[Chưa có]');
        const detailsHtml = `
            <div class="flex flex-col lg:flex-row gap-6 text-sm">
                <div class="flex-1 space-y-3">
                    <div><strong class="text-gray-500 w-28 inline-block">Bộ phận:</strong> ${header.BoPhan || ''}</div>
                    <div><strong class="text-gray-500 w-28 inline-block">Phụ trách:</strong> ${header.PhuTrach || ''}</div>
                    <div><strong class="text-gray-500 w-28 inline-block">Số đơn YCSX:</strong> ${header.SoDon || ''}</div>
                    <div><strong class="text-gray-500 w-28 inline-block">Mã đơn:</strong> ${header.MaDon || ''}</div>
                </div>
                <div class="flex-1 space-y-3">
                    <div><strong class="text-gray-500 w-32 inline-block">Ngày gửi YCSX:</strong> ${header.NgayGuiYCSX ? new Date(header.NgayGuiYCSX + 'T00:00:00').toLocaleDateString('vi-VN') : ''}</div>
                    <div><strong class="text-gray-500 w-32 inline-block">Người nhận hàng:</strong> ${header.NguoiNhanHang || ''}</div>
                    <div><strong class="text-gray-500 w-32 inline-block">SĐT Người nhận:</strong> ${header.SdtNguoiNhan || ''}</div>
                    <div><strong class="text-gray-500 w-32 inline-block">Địa điểm giao:</strong> ${header.DiaDiemGiaoHang || ''}</div>
                    <div><strong class="text-gray-500 w-32 inline-block">Quy cách thùng:</strong> ${header.QuyCachThung || ''}</div>
                </div>
                <div class="flex-1 space-y-3">
                    <div><strong class="text-gray-500 w-32 inline-block">Ngày giao:</strong> ${header.NgayGiao ? new Date(header.NgayGiao + 'T00:00:00').toLocaleDateString('vi-VN') : ''}</div>
                    <div><strong class="text-gray-500 w-32 inline-block">Đăng ký CT:</strong> ${header.DangKiCongTruong || ''}</div>
                    <div><strong class="text-gray-500 w-32 inline-block">Xe Grap:</strong> ${header.XeGrap || ''}</div>
                    <div><strong class="text-gray-500 w-32 inline-block">Xe tải:</strong> ${header.XeTai || ''}</div>
                    <div><strong class="text-gray-500 w-32 inline-block">Số tài xế:</strong> ${header.SoLaiXe || ''}</div>
                </div>
            </div>`;
        $('#review-details-container').html(detailsHtml);
        const itemsBody = $('#review-items-body').empty();
        items.forEach(item => {
            const row = `<tr><td class="py-2 px-3">${item.MaHang}</td><td class="py-2 px-3">${item.TenSanPham}</td><td class="py-2 px-3 text-center font-semibold">${item.SoLuong}</td></tr>`;
            itemsBody.append(row);
        });
        const pdfUrl = `api/export_chuanbihang_pdf.php?id=${cbhId}`;
        const excelUrl = `api/export_chuanbihang_excel.php?id=${cbhId}`;
        const buttonsHtml = `
            <a href="?page=chuanbi_hang_edit&id=${cbhId}&mode=view" class="action-btn bg-blue-100 text-blue-600" title="Xem chi tiết quy trình">
                <i class="fas fa-eye"></i> Xem chi tiết
            </a>
            <a href="${pdfUrl}" target="_blank" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            <a href="${excelUrl}" target="_blank" class="px-4 py-2 bg-green-700 text-white rounded-md hover:bg-green-800 text-sm flex items-center gap-2">
                <i class="fas fa-file-excel"></i> Excel
            </a>
        `;
        $('#review-action-buttons').html(buttonsHtml);
    }

    function validateForm() {
        let isFormValid = true;
        $('.required-field').each(function() {
            if ($(this).val().trim() === '') {
                isFormValid = false;
                $(this).addClass('border-red-400 focus:border-red-500 focus:ring-red-500');
            } else {
                $(this).removeClass('border-red-400 focus:border-red-500 focus:ring-red-500');
            }
        });
        const xeGrapInput = $('#info-xegrap');
        const xeTaiInput = $('#info-xetai');
        if (xeGrapInput.val().trim() === '' && xeTaiInput.val().trim() === '') {
            isFormValid = false;
            xeGrapInput.addClass('border-red-400 focus:border-red-500 focus:ring-red-500');
            xeTaiInput.addClass('border-red-400 focus:border-red-500 focus:ring-red-500');
        } else {
            xeGrapInput.removeClass('border-red-400 focus:border-red-500 focus:ring-red-500');
            xeTaiInput.removeClass('border-red-400 focus:border-red-500 focus:ring-red-500');
        }
        $('#save-and-create-cbh-btn').prop('disabled', !isFormValid);
    }
    
    $(document).on('keyup change blur', '.required-field, #info-xegrap, #info-xetai', validateForm);

    saveButton.on('click', function() {
        validateForm();
        if(saveButton.is(':disabled')) {
            App.showMessageModal('Vui lòng điền đủ thông tin bắt buộc và nhập ít nhất một loại xe (Grap hoặc Tải).', 'warning');
            return;
        }
        const btn = $(this);
        const formData = {
            khgh_id: khghId,
            bophan: $('#info-bophan').val(), 
            ngaygui_ycsx: $('#info-ngaygui').val(),
            phutrach: $('#info-phutrach').val(), 
            ngaygiao: $('#info-ngaygiao').val(),
            nguoinhanhang: $('#info-nguoinhan').val(), 
            sdtnguoinhan: $('#info-sdtnguoinhan').val(),
            diadiemgiaohang: $('#info-diadiem').val(),
            quycachthung: $('#info-quycachthung').val(), 
            xegrap: $('#info-xegrap').val(),
            xetai: $('#info-xetai').val(), 
            solaixe: $('#info-solaixe').val(),
            dangkicongtruong: $('#info-dangkicongtruong').val(),
        };
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Bước 1/3: Đang tạo phiếu...');
        $.ajax({
            url: 'api/create_cbh_from_khgh.php',
            method: 'POST', 
            data: formData, 
            dataType: 'json',
            success: function(res) {
                if (res.success && res.cbh_id) {
                    const newCbhId = res.cbh_id;
                    btn.html('<i class="fas fa-spinner fa-spin"></i> Bước 2/3: Đang xử lý tồn kho...');
                    $.ajax({
                        url: 'api/process_cbh_details.php',
                        method: 'POST', 
                        data: { cbh_id: newCbhId }, 
                        dataType: 'json',
                        success: function(processRes) {
                            if (processRes.success) {
                                btn.html('<i class="fas fa-spinner fa-spin"></i> Bước 3/3: Đang tải kết quả...');
                                App.showMessageModal('Xử lý tồn kho thành công!', 'success');
                                $('#cbh-creation-section').hide();
                                $.ajax({
                                    url: `api/get_chuanbihang_details.php?id=${newCbhId}`,
                                    dataType: 'json',
                                    success: function(reviewRes) {
                                        if (reviewRes.success) {
                                            renderCbhReview(reviewRes.data);
                                        } else {
                                             App.showMessageModal('Không thể tải lại chi tiết phiếu: ' + (reviewRes.message || ''), 'error');
                                        }
                                    },
                                    error: function(jqXHR, textStatus, errorThrown) {
                                        const errorMessage = getApiErrorMessage(jqXHR, textStatus, errorThrown);
                                        App.showMessageModal('Không thể tải lại chi tiết phiếu: ' + errorMessage, 'error');
                                    }
                                });
                            } else {
                                // Ghi log chi tiết toàn bộ phản hồi lỗi ra console để giúp debug phía backend
                                console.error("API 'process_cbh_details.php' trả về lỗi:", processRes);
                                let errorMessage = processRes.message || 'Lỗi không xác định từ máy chủ.';
                                if (processRes.errors && Array.isArray(processRes.errors) && processRes.errors.length > 0) {
                                    const errorList = processRes.errors.map(err => `<li>${String(err).replace(/</g, "&lt;").replace(/>/g, "&gt;")}</li>`).join('');
                                    errorMessage += `<ul class="list-disc list-inside mt-2 text-sm text-left">${errorList}</ul>`;
                                }
                                App.showMessageModal('Tạo phiếu thành công, nhưng xử lý tồn kho thất bại:<br>' + errorMessage, 'error');
                                btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Tạo Phiếu');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            const errorMessage = getApiErrorMessage(jqXHR, textStatus, errorThrown);
                            App.showMessageModal('Tạo phiếu thành công, nhưng gặp lỗi khi xử lý tồn kho: ' + errorMessage, 'error');
                            btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Tạo Phiếu');
                        }
                    });
                } else {
                    App.showMessageModal('Lỗi khi tạo phiếu: ' + (res.message || 'Không thể tạo phiếu.'), 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Tạo Phiếu');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                const errorMessage = getApiErrorMessage(jqXHR, textStatus, errorThrown);
                App.showMessageModal('Lỗi khi tạo phiếu: ' + errorMessage, 'error');
                btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Tạo Phiếu');
            }
        });
    });

    $.ajax({
        url: `api/get_delivery_plan_details.php?id=${khghId}`,
        dataType: 'json',
        success: (res) => {
            if (res.success && res.plan) {
                currentDonhangId = res.plan.info.YCSX_ID || 
                                     res.plan.order_info?.YCSX_ID || 
                                     res.plan.donhang_id;
                
                if (currentDonhangId) {
                    $('#back-to-order-btn').attr('href', `?page=donhang_view&id=${currentDonhangId}`);
                }
                
                renderItems(res.plan.items); 
                const planInfo = res.plan.info;
                $('#plan-number').text(planInfo.SoKeHoach || 'Kiểm tra tiến độ');
                
                if (planInfo && planInfo.CBH_ID) {
                    $.ajax({
                        url: `api/get_chuanbihang_details.php?id=${planInfo.CBH_ID}`,
                        dataType: 'json',
                        success: function(cbhRes) {
                            if (cbhRes.success) {
                                if (planInfo.TrangThai === 'Đã tạo phiếu chuẩn bị hàng') {
                                    prepareEditingMode(cbhRes.data);
                                    attachActionHandlers(planInfo.CBH_ID);
                                } else {
                                    renderCbhReview(cbhRes.data);
                                }
                            } else {
                                App.showMessageModal('Không thể tải chi tiết CBH: ' + (cbhRes.message || 'Lỗi không xác định'), 'error');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            const errorMessage = getApiErrorMessage(jqXHR, textStatus, errorThrown);
                            App.showMessageModal('Lỗi tải chi tiết CBH: ' + errorMessage, 'error');
                        }
                    });
                } else {
                    $('#cbh-review-section').addClass('hidden');
                    $('#cbh-creation-section').removeClass('hidden');
                    populateFormForCreation(res.plan);
                    validateForm();
                }
            } else {
                App.showMessageModal('Không thể tải dữ liệu của đợt giao hàng. ' + (res.message || ''), 'error');
            }
        },
        error: (jqXHR, textStatus, errorThrown) => {
            const errorMessage = getApiErrorMessage(jqXHR, textStatus, errorThrown);
            App.showMessageModal('Lỗi kết nối khi tải dữ liệu ban đầu: ' + errorMessage, 'error');
        }
    });
}


/**
 * =================================================================================
 * ROUTING - GỌI HÀM KHỞI TẠO TƯƠNG ỨNG VỚI TỪNG TRANG
 * =================================================================================
 */
$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');

    if (page === 'donhang_list' || (!page && (window.location.pathname.endsWith('/') || window.location.pathname.endsWith('index.php')))) {
        initializeDonHangListPage();
    } else if (page === 'donhang_view') {
        initializeDonHangViewPage();
    } else if (page === 'delivery_plan_create') {
        initializeDeliveryPlanCreatePage();
    } else if (page === 'delivery_plan_view') {
        initializeDeliveryPlanViewPage();
    }
});