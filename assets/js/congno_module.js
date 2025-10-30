function initializeCongNoPage() {
    // Initial data load
    fetchAndRenderCongNo(1, getCongNoFilters());

    // --- EVENT LISTENERS ---
    // Filter button
    $('#congno-filter-btn').off('click').on('click', () => fetchAndRenderCongNo(1, getCongNoFilters()));
    
    // Clear filter button
    $('#congno-clear-filter-btn').off('click').on('click', () => {
        clearCongNoFilters();
        fetchAndRenderCongNo(1, getCongNoFilters());
    });

    // Tab switching
    $('#congno-tabs').on('click', '.congno-tab', function(e) {
        e.preventDefault();
        $('#congno-tabs .congno-tab').removeClass('active-tab');
        $(this).addClass('active-tab');
        fetchAndRenderCongNo(1, getCongNoFilters());
    });

    // Detailed Excel Export Button
    $('#export-congno-detail-btn').off('click').on('click', function() {
        const filters = getCongNoFilters();
        const params = new URLSearchParams(filters);
        // Redirect to the new detailed export script
        window.location.href = `api/export_congno_excel_detail.php?${params.toString()}`;
    });
}

// Get all current filter values from the UI
const getCongNoFilters = () => ({
    search: $('#congno-search-input').val(),
    status: $('#congno-status-filter').val(),
    startDate: $('#congno-start-date-filter').val(),
    endDate: $('#congno-end-date-filter').val(),
    filter_type: $('#congno-tabs .active-tab').data('filter') || 'all'
});

// Reset all filter fields
const clearCongNoFilters = () => {
    $('#congno-search-input, #congno-status-filter, #congno-start-date-filter, #congno-end-date-filter').val('');
};

// Main function to fetch data and render the table
function fetchAndRenderCongNo(page, filters = {}) {
    const listBody = $('#congno-list-body');
    const paginationContainer = $('#congno-pagination-container');
    
    // Show loading spinner
    listBody.html(`<tr><td colspan="14" class="p-12 text-center"><i class="fas fa-spinner fa-spin text-4xl text-blue-500"></i><p class="mt-2 text-gray-500">Đang tải dữ liệu...</p></td></tr>`);
    paginationContainer.empty();

    const params = new URLSearchParams({ page, ...filters });

    $.ajax({
        url: `api/get_congno_data.php?${params.toString()}`,
        dataType: 'json',
        success: response => {
            // Update summary cards
            if (response.summary) {
                $('#summary-total-debt').text(App.formatNumber(response.summary.totalDebt || 0) + ' đ');
                $('#summary-overdue-debt').text(App.formatNumber(response.summary.overdueDebt || 0) + ' đ');
                $('#summary-overdue-count').text(response.summary.overdueCount || 0);
                $('#overdue-tab-count').text(response.summary.overdueCount || 0);
            }

            listBody.empty();
            if (response.success && response.data.length > 0) {
                // Populate table rows
                response.data.forEach((item) => {
                    const hoSoLienQuanHtml = `<div class="flex justify-center items-center gap-3">
                        ${createIconLink('donhang_view', item.YCSX_ID, 'fa-file-alt', 'Xem đơn hàng')}
                        ${item.BBGH_ID ? createIconLink('bbgh_view', item.BBGH_ID, 'fa-clipboard-check', 'Xem BBGH') : ''}
                        ${createIconLink('xuat_hoa_don', null, 'fa-receipt', 'Xuất Hóa Đơn', `ycsx_id=${item.YCSX_ID}`, true)}
                    </div>`;

                    const row = $(`
                        <tr data-ycsx-id="${item.YCSX_ID}" data-tong-gia-tri="${item.TongGiaTri}" data-ngay-giao-hang="${item.NgayGiaoHang}">
                            <td class="p-3 font-medium text-gray-700">${item.SoYCSX}</td>
                            <td class="p-3"><div class="font-semibold">${item.MaKhachHang}</div><div class="text-xs text-gray-500">${item.TenCongTy}</div></td>
                            <td class="p-3"><input type="text" class="form-input-table don-vi-tra" value="${item.DonViTra || ''}"></td>
                            <td class="p-3">${item.NgayGiaoHang ? new Date(item.NgayGiaoHang).toLocaleDateString('vi-VN') : ''}</td>
                            <td class="p-3 text-center font-medium so-ngay-cho-no-display"></td>
                            <td class="p-3"><input type="date" class="form-input-table thoi-han-thanh-toan" value="${item.ThoiHanThanhToan || ''}"></td>
                            <td class="p-3"><input type="date" class="form-input-table ngay-xuat-hoa-don" value="${item.NgayXuatHoaDon || ''}"></td>
                            <td class="p-3 text-right font-semibold">${App.formatNumber(item.TongGiaTri)}</td>
                            <td class="p-3"><input type="text" class="form-input-table text-right so-tien-tam-ung" value="${App.formatNumber(item.SoTienTamUng)}"></td>
                            <td class="p-3 text-right font-bold text-blue-600 gia-tri-con-lai">${App.formatNumber(item.GiaTriConLai)}</td>
                            <td class="p-3 text-center">${getStatusBadge(item.TrangThaiThanhToan)}</td>
                            <td class="p-3 text-center">${getQuaHanInfo(item.ThoiHanThanhToan, item.TrangThaiThanhToan)}</td>
                            <td class="p-3 text-center">${hoSoLienQuanHtml}</td>
                            <td class="p-3 text-center">
                                <button class="save-congno-btn bg-blue-600 text-white p-2 rounded-lg text-xs hover:bg-blue-700 transition" title="Lưu thay đổi"><i class="fas fa-save"></i></button>
                            </td>
                        </tr>`);
                    listBody.append(row);
                    updateSoNgayDisplay(row);
                });
                // Render pagination controls
                renderCongNoPagination(response.pagination, filters);
            } else {
                 listBody.html(`<tr><td colspan="14" class="p-12 text-center"><i class="fas fa-inbox text-5xl text-gray-300"></i><p class="mt-3 text-gray-500">Không có dữ liệu công nợ nào phù hợp.</p></td></tr>`);
            }
        },
        error: () => listBody.html(`<tr><td colspan="14" class="p-12 text-center"><i class="fas fa-exclamation-triangle text-5xl text-red-400"></i><p class="mt-3 text-red-600">Đã xảy ra lỗi khi tải dữ liệu.</p></td></tr>`)
    });
}

// --- HELPER FUNCTIONS ---
function createIconLink(page, id, iconClass, title, params = '', newTab = false) {
    let href = `?page=${page}${id ? `&id=${id}` : ''}${params ? `&${params}` : ''}`;
    return `<a href="${href}" ${newTab ? 'target="_blank"' : ''} class="text-gray-500 hover:text-blue-600" title="${title}"><i class="fas ${iconClass}"></i></a>`;
}

function getStatusBadge(status) {
    const map = {'Chưa thanh toán': 'status-unpaid', 'Thanh toán 1 phần': 'status-partial', 'Đã thanh toán': 'status-paid'};
    return `<span class="status-badge ${map[status] || ''}">${status}</span>`;
}

function getQuaHanInfo(thoiHanTT, trangThaiTT) {
    if (trangThaiTT === 'Đã thanh toán' || !thoiHanTT) return '<span class="text-gray-400">-</span>';
    const diff = Math.ceil((new Date() - new Date(thoiHanTT)) / (1000 * 3600 * 24));
    if (diff <= 0) return `<span class="text-green-600">Còn ${-diff}d</span>`;
    return `<span class="overdue-badge ${diff > 30 ? 'overdue-critical' : 'overdue-warning'}">Quá ${diff}d</span>`;
}

// Function to build and render pagination controls
function renderCongNoPagination(pagination, filters) {
    const { current_page, total_pages, total_records, items_per_page } = pagination;
    const container = $('#congno-pagination-container');
    if (total_pages <= 1) { container.empty(); return; }
    
    const start_item = (current_page - 1) * items_per_page + 1;
    const end_item = Math.min(start_item + items_per_page - 1, total_records);
    let infoHtml = `<div class="text-sm text-gray-600">Hiển thị ${start_item} - ${end_item} trên tổng số ${total_records} mục</div>`;
    
    let linksHtml = '<div class="inline-flex items-center -space-x-px rounded-md text-sm mt-2 sm:mt-0">';
    const createLink = (page, content, disabled = false, active = false) => 
        `<a href="#" data-page="${page}" class="pagination-link relative inline-flex items-center px-3 py-2 ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}">${content}</a>`;

    linksHtml += createLink(current_page - 1, '<i class="fas fa-chevron-left"></i>', current_page === 1);

    for (let i = 1; i <= total_pages; i++) {
        if (i === 1 || i === total_pages || (i >= current_page - 2 && i <= current_page + 2)) {
            linksHtml += createLink(i, i, false, i === current_page);
        } else if (i === current_page - 3 || i === current_page + 3) {
            linksHtml += `<span class="pagination-link relative inline-flex items-center px-3 py-2">...</span>`;
        }
    }

    linksHtml += createLink(current_page + 1, '<i class="fas fa-chevron-right"></i>', current_page === total_pages);
    linksHtml += '</div>';
    
    container.html(infoHtml + linksHtml);

    $('.pagination-link').not('.disabled, .active').on('click', function(e) {
        e.preventDefault();
        fetchAndRenderCongNo(parseInt($(this).data('page')), filters);
    });
}

// --- DYNAMIC UPDATE FUNCTIONS ---
// Update the 'So Ngay No' display based on dates
function updateSoNgayDisplay(row) {
    const ngayGiaoHang = row.data('ngay-giao-hang');
    const hanThanhToan = row.find('.thoi-han-thanh-toan').val();
    if (ngayGiaoHang && hanThanhToan) {
        const diff = Math.round((new Date(hanThanhToan) - new Date(ngayGiaoHang)) / (1000 * 3600 * 24));
        row.find('.so-ngay-cho-no-display').text(diff >= 0 ? diff : '-');
    } else {
        row.find('.so-ngay-cho-no-display').text('-');
    }
}

// Update the 'Con Lai' value when 'Da TT' input changes
function updateGiaTriConLai(row) {
    const tongGiaTri = parseFloat(row.data('tong-gia-tri'));
    const soTienTamUng = App.parseNumber(row.find('.so-tien-tam-ung').val());
    const conLai = tongGiaTri - soTienTamUng;
    row.find('.gia-tri-con-lai').text(App.formatNumber(conLai));
}

// --- ROW-LEVEL EVENT LISTENERS (using event delegation) ---
// When 'Han TT' date is changed, update 'So Ngay No'
$(document).on('change', '.thoi-han-thanh-toan', function() { updateSoNgayDisplay($(this).closest('tr')); });

// When 'Da TT' amount is changed, update 'Con Lai'
$(document).on('input', '.so-tien-tam-ung', function() { updateGiaTriConLai($(this).closest('tr')); });

// Format number on blur
$(document).on('blur', '.so-tien-tam-ung', function() { $(this).val(App.formatNumber(App.parseNumber($(this).val()))); });

// Save button click
$(document).on('click', '.save-congno-btn', function() {
    const btn = $(this);
    const row = btn.closest('tr');
    const data = {
        YCSX_ID: row.data('ycsx-id'),
        SoTienTamUng: App.parseNumber(row.find('.so-tien-tam-ung').val()),
        ThoiHanThanhToan: row.find('.thoi-han-thanh-toan').val(),
        NgayXuatHoaDon: row.find('.ngay-xuat-hoa-don').val(),
        DonViTra: row.find('.don-vi-tra').val(),
    };

    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    $.ajax({
        url: 'api/update_congno_details.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: (res) => {
             if (res.success) {
                App.showMessageModal('Cập nhật thành công!', 'success');
                // Refresh data on the current page to reflect changes
                const currentPage = parseInt($('#congno-pagination-container .pagination-link.active').data('page')) || 1;
                fetchAndRenderCongNo(currentPage, getCongNoFilters());
             } else App.showMessageModal(`Lỗi: ${res.message}`, 'error');
        },
        error: () => App.showMessageModal('Lỗi hệ thống!', 'error'),
        complete: () => btn.prop('disabled', false).html('<i class="fas fa-save"></i>')
    });
});

