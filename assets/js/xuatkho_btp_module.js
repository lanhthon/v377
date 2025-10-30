/**
 * Module: Quản lý Xuất kho Bán thành phẩm (BTP)
 * Version: 2.0
 * Update: Tích hợp bộ lọc, phân trang và xuất Excel cho danh sách.
 */

// Hàm render bảng dữ liệu phiếu xuất kho
function renderIssuedSlipsBTPTable(data) {
    const listBody = $('#xuatkho-btp-list-body');
    listBody.empty();
    if (data && data.length > 0) {
        data.forEach(slip => {
            const ngayXuat = slip.NgayXuat ? new Date(slip.NgayXuat).toLocaleDateString('vi-VN') : 'N/A';
            const row = `
                <tr class="hover:bg-gray-50 border-b">
                    <td class="p-3 font-semibold text-blue-600">${slip.SoPhieuXuat || 'N/A'}</td>
                    <td class="p-3">${slip.SoYCSX || 'N/A'}</td>
                    <td class="p-3">${ngayXuat}</td>
                    <td class="p-3">${slip.NguoiTao || 'N/A'}</td>
                    <td class="p-3 text-sm text-gray-600">${slip.GhiChu || ''}</td>
                    <td class="p-3 text-center">
                        <button class="view-pxk-btp-btn text-indigo-600 hover:text-indigo-900" data-pxk-id="${slip.PhieuXuatKhoID}">
                            <i class="fas fa-eye mr-1"></i> Xem
                        </button>
                    </td>
                </tr>`;
            listBody.append(row);
        });
    } else {
        listBody.html('<tr><td colspan="6" class="p-4 text-center text-gray-500">Không tìm thấy phiếu xuất kho nào phù hợp.</td></tr>');
    }
}

// Hàm render các nút phân trang
function renderIssuedSlipsBTPPagination(pagination) {
    const { currentPage, totalPages } = pagination;
    const paginationControls = $('#pagination-controls-pxk');
    paginationControls.empty();
    if (totalPages <= 1) return;

    let paginationHtml = '<div class="flex items-center space-x-1">';
    for (let i = 1; i <= totalPages; i++) {
        paginationHtml += `<button class="pagination-btn-pxk px-3 py-1 rounded-md text-sm ${i === currentPage ? 'bg-indigo-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'}" data-page="${i}">${i}</button>`;
    }
    paginationHtml += '</div>';
    paginationControls.html(paginationHtml);
}

// Hàm chính để lấy dữ liệu từ API
function fetchIssuedSlipsBTPData(page = 1) {
    const listBody = $('#xuatkho-btp-list-body');
    listBody.html('<tr><td colspan="6" class="p-4 text-center"><i class="fas fa-spinner fa-spin mr-2"></i>Đang tải dữ liệu...</td></tr>');

    const filters = {
        soPhieu: $('#filter-so-phieu-pxk').val(),
        soYCSX: $('#filter-so-ycsx-pxk').val(),
        startDate: $('#filter-start-date-pxk').val(),
        endDate: $('#filter-end-date-pxk').val(),
        ghiChu: $('#filter-ghi-chu-pxk').val(),
        page: page,
        limit: 15
    };
    
    const params = new URLSearchParams(filters).toString();

    $.getJSON(`api/get_issued_slips_btp.php?${params}`, function(response) {
        if (response.success) {
            renderIssuedSlipsBTPTable(response.data);
            renderIssuedSlipsBTPPagination(response.pagination);
        } else {
            listBody.html(`<tr><td colspan="6" class="p-4 text-center text-red-500">Lỗi: ${response.message}</td></tr>`);
        }
    }).fail(function() {
        listBody.html('<tr><td colspan="6" class="p-4 text-center text-red-500">Lỗi kết nối đến máy chủ.</td></tr>');
    });
}

/**
 * Khởi tạo và hiển thị danh sách các phiếu xuất kho BTP.
 */
function initializeXuatKhoBTPListPage() {
    fetchIssuedSlipsBTPData(1);

    // Sử dụng delegation để gắn sự kiện, chỉ cần chạy 1 lần
    $(document)
        .off('click', '#filter-btn-pxk')
        .on('click', '#filter-btn-pxk', () => fetchIssuedSlipsBTPData(1))
        
        .off('click', '#reset-filter-btn-pxk')
        .on('click', '#reset-filter-btn-pxk', function() {
            $('#filter-container-pxk input').val('');
            fetchIssuedSlipsBTPData(1);
        })
        
        .off('click', '.pagination-btn-pxk')
        .on('click', '.pagination-btn-pxk', function() {
            const page = $(this).data('page');
            if (page) fetchIssuedSlipsBTPData(page);
        })
        
        .off('click', '#export-list-pxk-excel-btn')
        .on('click', '#export-list-pxk-excel-btn', function() {
            const filters = {
                soPhieu: $('#filter-so-phieu-pxk').val(),
                soYCSX: $('#filter-so-ycsx-pxk').val(),
                startDate: $('#filter-start-date-pxk').val(),
                endDate: $('#filter-end-date-pxk').val(),
                ghiChu: $('#filter-ghi-chu-pxk').val(),
            };
            const params = new URLSearchParams(filters).toString();
            window.location.href = `api/export_list_pxk_btp_excel.php?${params}`;
        });
}

/**
 * Khởi tạo và hiển thị trang xem chi tiết một phiếu xuất kho BTP.
 */
function initializeXuatKhoBTPViewPage() {
    const params = new URLSearchParams(window.location.search);
    const pxkId = params.get('pxk_id');
    const mainContainer = $('#main-content-container');
    const actionButtonsContainer = $('#action-buttons-container');

    if (!pxkId) {
        mainContainer.html('<p class="text-red-500 p-4">Lỗi: ID Phiếu xuất kho không hợp lệ.</p>');
        return;
    }
    
    actionButtonsContainer.empty();
    
    $.getJSON(`api/get_issued_slip_btp_details.php?pxk_id=${pxkId}`, function(response) {
        if (response.success && response.data) {
            const { header, items } = response.data;
            
            actionButtonsContainer.html(`
                <button id="export-pxk-excel-btn" data-pxk-id="${pxkId}" class="bg-green-600 ...">Xuất Excel Chi Tiết</button>
                <button id="export-pxk-pdf-btn2" data-pxk-id="${pxkId}" class="bg-red-600 ...">Xuất PDF</button>
            `);

            const ngayXuat = new Date(header.NgayXuat);
            $('#info-ngayxuat').text(`${ngayXuat.getDate()} tháng ${ngayXuat.getMonth() + 1} năm ${ngayXuat.getFullYear()}`);
            $('#info-sophieu').text(header.SoPhieuXuat);
            $('#info-nguoilap').text(header.NguoiLap || 'N/A');

            if (header.SoYCSX) {
                $('#info-ycsx').text(header.SoYCSX);
                $('#info-nguoinhan').text('Bộ phận cắt');
                $('#info-lydo').text('Xuất BTP để cắt thành phẩm');
            } else {
                $('#info-ycsx').text('N/A');
                $('#info-nguoinhan').text(header.NguoiNhan || 'Không rõ');
                $('#info-lydo').text(header.GhiChu || 'Không có');
            }

            const itemsBody = $('#xuatkho-btp-items-body');
            itemsBody.empty();
            let totalQuantity = 0;

            if (items.length > 0) {
                items.forEach((item, index) => {
                    const row = `
                        <tr class="border-b">
                            <td class="p-2 border text-center">${index + 1}</td>
                            <td class="p-2 border">${item.MaHang || ''}</td>
                            <td class="p-2 border">${item.TenSanPham || ''}</td>
                            <td class="p-2 border text-center">${item.DonViTinh || 'Cây'}</td>
                            <td class="p-2 border text-center font-semibold">${App.formatNumber(item.SoLuongThucXuat)}</td>
                        </tr>`;
                    itemsBody.append(row);
                    totalQuantity += parseFloat(item.SoLuongThucXuat) || 0;
                });
                itemsBody.append(`<tr class="bg-gray-100 font-bold"><td colspan="4" class="p-2 border text-right">Tổng cộng:</td><td class="p-2 border text-center">${App.formatNumber(totalQuantity)}</td></tr>`);
            } else {
                itemsBody.html('<tr><td colspan="5" class="p-4 text-center">Phiếu này không có sản phẩm.</td></tr>');
            }
        } else {
            mainContainer.html(`<div class="bg-red-100 ...">${response.message || 'Lỗi tải dữ liệu.'}</div>`);
        }
    }).fail(function() {
        mainContainer.html(`<div class="bg-red-100 ...">Lỗi Server!</div>`);
    });

    $('#back-to-btp-list-btn').on('click', () => history.back());
}

/**
 * Thiết lập các trình xử lý sự kiện (Event Delegation) cho toàn bộ ứng dụng.
 */
$(document).ready(function() {
    
    $(document).on('click', '.view-pxk-btp-btn', function() {
        const pxkId = $(this).data('pxk-id');
        if (pxkId) {
            history.pushState({ page: 'xuatkho_btp_view', pxk_id: pxkId }, '', `?page=xuatkho_btp_view&pxk_id=${pxkId}`);
            if (window.App && typeof window.App.handleRouting === 'function') {
                window.App.handleRouting();
            } else {
                window.location.href = `?page=xuatkho_btp_view&pxk_id=${pxkId}`;
            }
        }
    });

    // Xuất Excel chi tiết (đã có)
    $(document).on('click', '#export-pxk-excel-btn', function() {
        const pxkId = $(this).data('pxk-id');
        if (pxkId) {
            window.location.href = `api/export_pxk_btp_excel.php?pxk_id=${pxkId}`;
        }
    });

    // Xuất PDF (đã có)
    $(document).on('click', '#export-pxk-pdf-btn2', function() {
        const pxkId = $(this).data('pxk-id');
        if (pxkId) {
            window.open(`api/export_pxk_btp_pdf.php?pxk_id=${pxkId}`, '_blank');
        }
    });

});
