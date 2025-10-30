/**
 * =================================================================================
 * BÁO GIÁ LIST SCRIPT (VERSION 10.4 - ENHANCED FILTERS)
 * Tái cấu trúc để dễ đọc, bảo trì và cải thiện bộ lọc.
 * =================================================================================
 */

// Chạy mã khi tài liệu HTML đã được tải hoàn toàn
$(document).ready(function() {
    // Chỉ khởi tạo script nếu tìm thấy phần tử chính của trang
    if ($('#quote-list-body').length > 0) {
        initializeQuoteListPage();
    }
});

/**
 * Hàm chính khởi tạo toàn bộ chức năng của trang danh sách báo giá.
 */
function initializeQuoteListPage() {

    // --- KHAI BÁO BIẾN VÀ CÁC PHẦN TỬ DOM ---
    const DOM = {
        quoteListBody: $('#quote-list-body'),
        paginationControls: $('#pagination-controls'),
        tooltip: $('#info-tooltip'),
        applyFilterBtn: $('#apply-filter-btn'),
        exportExcelBtn: $('#export-list-excel-btn'),
        filterStartDate: $('#filter-start-date'),
        filterEndDate: $('#filter-end-date'),
        filterSearchTerm: $('#filter-search-term'),
        filterStatus: $('#filter-status'),
        filterCreator: $('#filter-creator')
    };

    const config = {
        itemsPerPage: 20 // Số lượng mục hiển thị trên mỗi trang
    };

    let state = {
        currentUserRole: 'Guest' // Vai trò của người dùng, mặc định là 'Guest'
    };

    // --- CÁC HÀM TIỆN ÍCH ---

    // Định dạng số (10000 -> "10,000")
    function formatNumber(num) {
        if (num === null || num === undefined || isNaN(num)) return '0';
        return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
    }

    // Mã hóa HTML để tránh XSS và các lỗi hiển thị
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return text.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // --- CÁC HÀM GỌI API (TRUY XUẤT DỮ LIỆU) ---

    /**
     * Tải danh sách người tạo báo giá và điền vào dropdown bộ lọc.
     */
    function populateCreatorFilter() {
        $.ajax({
            url: 'api/get_quote_creators.php',
            method: 'GET',
            dataType: 'json'
        }).done(response => {
            if (response.success && Array.isArray(response.creators)) {
                response.creators.forEach(creator => {
                    DOM.filterCreator.append(new Option(creator.HoTen, creator.UserID));
                });
            }
        }).fail(() => {
            console.error('Không thể tải danh sách người tạo báo giá.');
        });
    }

    /**
     * Lấy vai trò của người dùng hiện tại từ server.
     * @returns {Promise} Một Promise sẽ resolve với vai trò người dùng.
     */
    function fetchUserRole() {
        return $.ajax({
            url: 'api/get_user_permissions.php',
            method: 'GET',
            dataType: 'json'
        }).done(response => {
            if (response.success && response.user) {
                state.currentUserRole = response.user.role;
            }
        }).fail(() => {
            state.currentUserRole = 'Guest'; // Giữ vai trò mặc định nếu lỗi
            console.error('Không thể lấy quyền người dùng.');
        });
    }

    /**
     * Lấy danh sách báo giá từ server dựa trên bộ lọc và trang hiện tại.
     * @param {number} [page=1] - Số trang cần lấy.
     */
    function fetchQuotes(page = 1) {
        const filters = {
            startDate: DOM.filterStartDate.val(),
            endDate: DOM.filterEndDate.val(),
            status: DOM.filterStatus.val(),
            creatorId: DOM.filterCreator.val(),
            searchTerm: DOM.filterSearchTerm.val(),
            page: page,
            limit: config.itemsPerPage
        };

        $.ajax({
            url: 'api/get_quotes.php',
            method: 'GET',
            data: filters,
            dataType: 'json'
        }).done(response => {
            if (response.success) {
                renderPage(response.quotes, response.pagination);
            } else {
                showMessageModal(response.message || 'Có lỗi xảy ra khi tải dữ liệu.', 'error');
            }
        }).fail((jqXHR, textStatus, errorThrown) => {
            console.error("Lỗi khi fetch báo giá:", textStatus, errorThrown, jqXHR.responseText);
            showMessageModal("Không thể tải danh sách báo giá. Vui lòng thử lại.", 'error');
            DOM.quoteListBody.html(`<tr><td colspan="7" class="text-center py-4 text-red-500">Lỗi tải dữ liệu.</td></tr>`);
            DOM.paginationControls.empty();
        });
    }


    // --- CÁC HÀM HIỂN THỊ (RENDERING) ---

    /**
     * Tạo HTML cho các nút hành động (Xem, Sửa, Xóa).
     * @param {object} quote - Đối tượng báo giá.
     * @returns {string} - Chuỗi HTML.
     */
    function createActionButtons(quote) {
        const viewButton = `<button class="view-quote-btn text-gray-600 hover:text-gray-900 mr-2" data-id="${quote.BaoGiaID}" title="Xem"><i class="fas fa-eye"></i></button>`;
        const editButton = `<button class="edit-quote-btn text-blue-600 hover:text-blue-900 mr-2" data-id="${quote.BaoGiaID}" title="Chỉnh sửa"><i class="fas fa-edit"></i></button>`;
        const deleteButton = `<button class="delete-quote-btn text-red-600 hover:text-red-900" data-id="${quote.BaoGiaID}" title="Xóa"><i class="fas fa-trash"></i></button>`;
        
        const isFinalized = ['Chốt', 'Tạch', 'Đã tạo đơn hàng'].includes(quote.TrangThai);

        if (isFinalized) {
            return viewButton;
        }

        let buttons = editButton + viewButton;
        if (state.currentUserRole === 'Admin' || state.currentUserRole === 'Quản lý') {
            buttons += deleteButton;
        }
        return buttons;
    }

    /**
     * Tạo HTML cho một dòng (row) trong bảng báo giá.
     * @param {object} quote - Đối tượng báo giá.
     * @returns {string} - Chuỗi HTML của thẻ <tr>.
     */
    function createQuoteRow(quote) {
        const quoteNumberParts = quote.SoBaoGia ? quote.SoBaoGia.split('/') : [];
        const derivedCustomerCode = quoteNumberParts.length > 0 ? quoteNumberParts[quoteNumberParts.length - 1] : 'N/A';
        const customerCode = quote.MaCongTy || derivedCustomerCode;
        
        const customerInfoContent = `
            <div class='font-bold text-base text-gray-200'>${escapeHtml(quote.TenCongTy) || 'Chưa có thông tin'}</div>
            <div class='text-xs mt-1'><b>MST:</b> ${escapeHtml(quote.MaSoThue) || 'N/A'}</div>
            <div class='text-xs'><b>Địa chỉ:</b> ${escapeHtml(quote.DiaChiCongTy) || 'N/A'}</div>
            <div class='text-xs'><b>SĐT:</b> ${escapeHtml(quote.SoDienThoaiChinh) || 'N/A'}</div>`;

        const statusClasses = {
            'Chốt': 'bg-green-200 text-green-800',
            'Đã tạo đơn hàng': 'bg-green-200 text-green-800',
            'Đấu thầu': 'bg-yellow-200 text-yellow-800',
            'Đàm phán': 'bg-blue-200 text-blue-800',
            'Mới tạo': 'bg-gray-200 text-gray-800',
            'Tạch': 'bg-red-200 text-red-800'
        };
        const statusClass = statusClasses[quote.TrangThai] || 'bg-gray-200 text-gray-800';

        return `
            <tr class="border-b border-gray-200 quote-row" data-id="${quote.BaoGiaID}">
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-800">${escapeHtml(quote.SoBaoGia)}</div>
                    <span class="mt-1 inline-block px-2 py-1 text-xs font-semibold rounded-full bg-sky-100 text-sky-800">${escapeHtml(quote.NguoiTao) || 'N/A'}</span>
                </td>
                <td class="px-4 py-3 has-tooltip" data-tooltip-content="${escapeHtml(customerInfoContent)}">${escapeHtml(customerCode)}</td>
                <td class="px-4 py-3">${escapeHtml(quote.TenDuAn) || 'N/A'}</td>
                <td class="px-4 py-3">${escapeHtml(quote.NgayBaoGia)}</td>
                <td class="px-4 py-3 text-right">${formatNumber(quote.TongTienSauThue)} VNĐ</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold ${statusClass}">${escapeHtml(quote.TrangThai)}</span>
                </td>
                <td class="px-4 py-3 text-center">${createActionButtons(quote)}</td>
            </tr>`;
    }

    /**
     * Hiển thị dữ liệu báo giá và phân trang lên giao diện.
     * @param {Array} quotes - Mảng các đối tượng báo giá.
     * @param {object} pagination - Thông tin phân trang.
     */
    function renderPage(quotes, pagination) {
        DOM.quoteListBody.empty();
        if (!quotes || quotes.length === 0) {
            DOM.quoteListBody.html(`<tr><td colspan="7" class="text-center py-4 text-gray-500">Không có báo giá nào được tìm thấy.</td></tr>`);
            DOM.paginationControls.empty();
            return;
        }

        const rowsHtml = quotes.map(createQuoteRow).join('');
        DOM.quoteListBody.html(rowsHtml);
        renderPagination(pagination.currentPage, pagination.totalPages);
    }

    /**
     * Tạo và hiển thị các nút phân trang.
     * @param {number} currentPage - Trang hiện tại.
     * @param {number} totalPages - Tổng số trang.
     */
    function renderPagination(currentPage, totalPages) {
        if (totalPages <= 1) {
            DOM.paginationControls.empty();
            return;
        }

        let html = '';
        const createButton = (page, text, disabled = false) =>
            `<button class="pagination-btn px-3 py-1 border rounded-md ${disabled ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-blue-600 hover:bg-gray-100'}" data-page="${page}" ${disabled ? 'disabled' : ''}>${text}</button>`;
        const createActiveButton = (page) =>
            `<button class="pagination-btn px-3 py-1 border rounded-md bg-blue-600 text-white" data-page="${page}">${page}</button>`;

        html += createButton(currentPage - 1, 'Trước', currentPage === 1);

        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) {
            html += createButton(1, '1');
            if (startPage > 2) html += `<span class="px-3 py-1">...</span>`;
        }

        for (let i = startPage; i <= endPage; i++) {
            html += (i === currentPage) ? createActiveButton(i) : createButton(i, i);
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) html += `<span class="px-3 py-1">...</span>`;
            html += createButton(totalPages, totalPages);
        }

        html += createButton(currentPage + 1, 'Sau', currentPage === totalPages);
        DOM.paginationControls.html(html);
    }

    // --- CÁC HÀM XỬ LÝ SỰ KIỆN ---

    function handleFilter() {
        fetchQuotes(1);
    }
    
    function handlePaginationClick(event) {
        const page = parseInt($(event.currentTarget).data('page'));
        if (!isNaN(page)) {
            fetchQuotes(page);
        }
    }

    function handleRowClick(event) {
        if ($(event.target).closest('button, a, .has-tooltip').length > 0) return;
        const quoteId = $(event.currentTarget).data('id');
        window.location.href = `index.php?page=quote_create&id=${quoteId}&view=true`;
    }

    function handleEditClick(event) {
        const quoteId = $(event.currentTarget).data('id');
        window.location.href = `index.php?page=quote_create&id=${quoteId}`;
    }

    function handleViewClick(event) {
        const quoteId = $(event.currentTarget).data('id');
        window.location.href = `index.php?page=quote_create&id=${quoteId}&view=true`;
    }

    function handleDeleteClick(event) {
        const quoteId = $(event.currentTarget).data('id');
        showConfirmationModal('Bạn có chắc chắn muốn xóa báo giá này không?', () => {
            $.ajax({
                url: 'api/delete_quote.php',
                method: 'POST',
                data: JSON.stringify({ id: quoteId }),
                contentType: 'application/json',
            }).done(response => {
                showMessageModal(response.message, response.success ? 'success' : 'error');
                if (response.success) fetchQuotes(1);
            }).fail(() => {
                showMessageModal("Không thể xóa báo giá. Vui lòng thử lại.", 'error');
            });
        });
    }

    function handleExportExcel() {
        const filters = {
            startDate: DOM.filterStartDate.val(),
            endDate: DOM.filterEndDate.val(),
            status: DOM.filterStatus.val(),
            creatorId: DOM.filterCreator.val(),
            searchTerm: DOM.filterSearchTerm.val()
        };
        const params = new URLSearchParams(filters).toString();
        window.open(`api/export_quotes_list_excel.php?${params}`, '_blank');
    }

    // --- LOGIC CHO TOOLTIP ---
    let tooltipTimeout;

    function showTooltip(event) {
        const target = $(event.currentTarget);
        const content = target.data('tooltip-content');
        
        tooltipTimeout = setTimeout(() => {
            if (content && content.trim().length > 0) {
                DOM.tooltip.html(content).removeClass('hidden');
                updateTooltipPosition(event);
            }
        }, 150);
    }
    
    function hideTooltip() {
        clearTimeout(tooltipTimeout);
        DOM.tooltip.addClass('hidden');
    }

    function updateTooltipPosition(event) {
        if (DOM.tooltip.hasClass('hidden')) return;

        const tooltipWidth = DOM.tooltip.outerWidth();
        const windowWidth = $(window).width();
        let left = event.pageX + 15;
        if (left + tooltipWidth > windowWidth - 15) {
            left = event.pageX - tooltipWidth - 15;
        }
        DOM.tooltip.css({ top: event.pageY + 15, left: left });
    }

    // --- GÁN CÁC SỰ KIỆN ---
    function bindEvents() {
        // 1. Lắng nghe sự kiện click vào nút "Lọc"
        DOM.applyFilterBtn.on('click', handleFilter);

        // 2. Tự động lọc khi thay đổi các dropdown
        DOM.filterStatus.on('change', handleFilter);
        DOM.filterCreator.on('change', handleFilter);

        // 3. Lọc khi nhấn Enter trong ô tìm kiếm
        DOM.filterSearchTerm.on('keyup', function(event) {
            // Kiểm tra xem phím được nhấn có phải là 'Enter' không
            if (event.key === 'Enter') {
                handleFilter();
            }
        });

        // 4. Lắng nghe sự kiện click vào nút xuất Excel
        DOM.exportExcelBtn.on('click', handleExportExcel);

        // 5. Lắng nghe sự kiện click vào nút phân trang
        DOM.paginationControls.on('click', '.pagination-btn', handlePaginationClick);
        
        // Sử dụng event delegation cho các phần tử được tạo động
        DOM.quoteListBody.on('click', '.quote-row', handleRowClick);
        DOM.quoteListBody.on('click', '.edit-quote-btn', handleEditClick);
        DOM.quoteListBody.on('click', '.view-quote-btn', handleViewClick);
        DOM.quoteListBody.on('click', '.delete-quote-btn', handleDeleteClick);
        
        DOM.quoteListBody.on('mouseenter', '.has-tooltip', showTooltip);
        DOM.quoteListBody.on('mouseleave', '.has-tooltip', hideTooltip);
        DOM.quoteListBody.on('mousemove', '.has-tooltip', updateTooltipPosition);
    }

    // --- HÀM KHỞI TẠO CHÍNH ---
    async function init() {
        bindEvents();
        populateCreatorFilter();
        await fetchUserRole();
        fetchQuotes(1);
    }

    // Bắt đầu thực thi
    init();
}