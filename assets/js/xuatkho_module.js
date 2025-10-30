// File: public/js/xuatkho_module.js
// Version: 3.8 - Thay thế cột Tên Khách Hàng bằng Tên Dự Án

/*******************************************************************************
 * 1. CÁC HÀM KHỞI TẠO TRANG (PAGE INITIALIZATION FUNCTIONS)
 *******************************************************************************/

/**
 * Khởi tạo trang Danh sách chờ xuất kho
 */
function initializeXuatKhoListPage() {
    const listBody = $('#xuatkho-list-body');
    listBody.html('<tr><td colspan="6" class="p-4 text-center">Đang tải dữ liệu...</td></tr>');

    $.ajax({
        url: 'api/get_orders_for_issue.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data.length > 0) {
                listBody.empty();
                response.data.forEach(slip => {
                    const row = `
                        <tr>
                            <td class="p-3 font-semibold">${slip.SoCBH || 'N/A'}</td>
                            <td class="p-3">${slip.SoYCSX || ''}</td>
                            <td class="p-3">${slip.TenCongTy || ''}</td>
                            <td class="p-3">${slip.NgayTao ? new Date(slip.NgayTao).toLocaleDateString('vi-VN') : ''}</td>
                            <td class="p-3">${slip.NgayGiao ? new Date(slip.NgayGiao).toLocaleDateString('vi-VN') : ''}</td>
                            <td class="p-3 text-center">
                                <button class="create-pxk-btn bg-blue-500 text-white px-3 py-1 rounded-md text-xs hover:bg-blue-600" data-cbh-id="${slip.CBH_ID}">
                                    <i class="fas fa-file-import mr-1"></i> Tạo Phiếu Xuất
                                </button>
                            </td>
                        </tr>
                    `;
                    listBody.append(row);
                });
            } else {
                listBody.html('<tr><td colspan="6" class="p-4 text-center">Không có phiếu chuẩn bị hàng nào đang chờ xuất kho.</td></tr>');
            }
        },
        error: function(xhr) {
            console.error('Lỗi khi tải danh sách chờ xuất kho:', xhr.responseText);
            listBody.html('<tr><td colspan="6" class="p-4 text-center text-red-500">Lỗi khi tải dữ liệu.</td></tr>');
        }
    });
}


/**
 * Khởi tạo trang Danh sách phiếu đã xuất kho
 */
function initializeXuatKhoIssuedListPage() {
    fetchAndRenderIssuedSlips(1, {});

    $('#filter-btn').off('click').on('click', function() {
        const filters = {
            search: $('#search-input').val(),
            status: $('#status-filter').val(),
            startDate: $('#start-date-filter').val(),
            endDate: $('#end-date-filter').val()
        };
        fetchAndRenderIssuedSlips(1, filters);
    });

    $('#clear-filter-btn').off('click').on('click', function() {
        $('#search-input').val('');
        $('#status-filter').val('');
        $('#start-date-filter').val('');
        $('#end-date-filter').val('');
        fetchAndRenderIssuedSlips(1, {});
    });
}

/**
 * Khởi tạo trang Tạo/Xem Phiếu Xuất Kho (Đã cập nhật)
 */
function initializeXuatKhoCreatePage() {
    const urlParams = new URLSearchParams(window.location.search);
    const cbhId = urlParams.get('id');
    const pxkId = urlParams.get('pxk_id');
    const itemsBody = $('#xuatkho-items-body');
    const exportContainer = $('#export-buttons-container-pxk');
    const saveBtn = $('#save-xuatkho-btn');
    const isViewMode = !!pxkId;

    /**
     * Hàm render dữ liệu phiếu vào form
     * @param {object} header - Dữ liệu phần header của phiếu
     * @param {object} itemGroups - Dữ liệu các nhóm sản phẩm
     */
    const renderSlipData = (header, itemGroups) => {
        const ngayXuatDate = header.NgayXuat ? new Date(header.NgayXuat) : new Date();
        const formattedDate = ngayXuatDate.toISOString().split('T')[0];
        $('#pxk-ngayxuat').val(formattedDate);
        
        $('#info-sophieu').text(`Số: ${header.SoPhieuXuat || '...'}`);
        
        $('#pxk-tencongty').val(header.TenCongTyHienThi || '');
        $('#pxk-diachi').val(header.DiaChiCongTyHienThi || '');
        $('#pxk-nguoinhan').val(header.NguoiNhanHienThi || '');
        $('#pxk-diachigiao').val(header.DiaChiGiaoHangHienThi || '');
        $('#pxk-lydoxuat').val(header.LyDoXuatKhoHienThi || '');
        
        const nguoiLap = header.NguoiLapPhieuHienThi || (!isViewMode ? window.App.currentUser.fullName : '');
        $('#input-nguoilap').val(nguoiLap);
        $('#input-thukho').val(header.ThuKho || '');
        $('#input-nguoigiao').val(header.NguoiGiaoHang || '');
        $('#input-nguoinhanhang').val(header.NguoiNhanHang || '');

        itemsBody.empty();

        if (itemGroups && Object.keys(itemGroups).length > 0) {
            
            const renderGroup = (groupName, groupData) => {
                let ghiChuText = groupData.ghiChu || '';

                itemsBody.append(`<tr class="font-bold bg-gray-100"><td class="p-2 border border-black" colspan="5">${groupName}</td><td class="p-2 border border-black text-center">${ghiChuText}</td></tr>`);
                let subSttCounter = 1;

                const getSortPriority = (maHang) => {
                    if (typeof maHang !== 'string') return 3;
                    if (maHang.includes('PUR')) return 1;
                    if (maHang.includes('ULA')) return 2;
                    return 3;
                };

                groupData.items.sort((a, b) => {
                    const priorityA = getSortPriority(a.MaHang);
                    const priorityB = getSortPriority(b.MaHang);
                    if (priorityA !== priorityB) return priorityA - priorityB;
                    return (a.MaHang || '').localeCompare(b.MaHang || '');
                });

                groupData.items.forEach((item) => {
                    const rowHtml = `
                        <tr class="product-row border-b" data-detail-id="${item.ChiTietPXK_ID || ''}" data-variant-id="${item.variant_id || ''}">
                            <td class="p-2 border border-black text-center">${subSttCounter++}</td>
                            <td class="p-2 border border-black pl-4 ten-san-pham">${item.TenSanPham || ''}</td>
                            <td class="p-2 border border-black text-center ma-hang">${item.MaHang || ''}</td>
                            <td class="p-2 border border-black text-center">
                                <input type="number" class="w-full border-none p-1 rounded text-center so-luong-thuc-xuat bg-transparent" value="${item.SoLuongThucXuat || 0}" min="0">
                            </td>
                            <td class="p-2 border border-black">
                                <input type="text" class="w-full border-none p-1 rounded tai-so bg-transparent" value="${item.TaiSo || ''}">
                            </td>
                            <td class="p-2 border border-black">
                                <input type="text" class="w-full border-none p-1 rounded ghi-chu bg-transparent" value="${item.GhiChu || ''}">
                            </td>
                        </tr>
                    `;
                    itemsBody.append(rowHtml);
                });
            };

            const groupsWithPriority = Object.keys(itemGroups).map(groupName => {
                const groupData = itemGroups[groupName];
                let priority = 3;
                if (groupData.items.some(item => item.MaHang && item.MaHang.includes('PUR'))) priority = 1;
                else if (groupData.items.some(item => item.MaHang && item.MaHang.includes('ULA'))) priority = 2;
                return { groupName, priority, data: groupData };
            });

            groupsWithPriority.sort((a, b) => {
                if (a.priority !== b.priority) return a.priority - b.priority;
                return a.groupName.localeCompare(b.groupName);
            });

            groupsWithPriority.forEach(group => renderGroup(group.groupName, group.data));
        } else {
            itemsBody.html('<tr><td colspan="6" class="p-4 text-center">Không có sản phẩm.</td></tr>');
            if (!isViewMode) saveBtn.prop('disabled', true).addClass('opacity-50');
        }
    };

    const url = isViewMode ? `api/get_issued_slip_details.php?pxk_id=${pxkId}` : `api/get_xuatkho_details_from_cbh.php?cbh_id=${cbhId}`;

    if (!pxkId && !cbhId) {
        App.showMessageModal('Không tìm thấy ID hợp lệ để tải dữ liệu.', 'error');
        return;
    }

    $('#page-title').text(isViewMode ? 'Xem & Sửa Phiếu Xuất Kho' : 'Tạo Phiếu Xuất Kho');
    exportContainer.empty();
    if (isViewMode) {
        saveBtn.hide(); 
        exportContainer.html(`
            <button id="update-pxk-btn" data-pxk-id="${pxkId}" class="bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700">
                <i class="fas fa-save mr-2"></i>Lưu thay đổi
            </button>
            <button id="export-pxk-excel-btn-3" data-pxk-id="${pxkId}" class="bg-green-700 text-white px-4 py-2 rounded-md hover:bg-green-800">
                <i class="fas fa-file-excel mr-2"></i>Xuất Excel
            </button>
            <button id="export-pxk-pdf-btn" data-pxk-id="${pxkId}" class="bg-red-700 text-white px-4 py-2 rounded-md hover:bg-red-800">
                <i class="fas fa-file-pdf mr-2"></i>Xuất PDF
            </button>
        `);
    } else {
        saveBtn.show();
    }
    
    $('#back-to-list-btn').off('click').on('click', () => history.back());

    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderSlipData(response.header, response.items);
            } else {
                App.showMessageModal(`Lỗi khi tải dữ liệu: ${response.message}`, 'error');
            }
        },
        error: function() {
            App.showMessageModal('Lỗi hệ thống khi tải chi tiết phiếu xuất kho.', 'error');
        }
    });
}


/*******************************************************************************
 * 2. CÁC HÀM HỖ TRỢ (HELPER FUNCTIONS)
 *******************************************************************************/
/**
 * Lấy dữ liệu header từ form
 * @returns {object} Dữ liệu header
 */
function getHeaderDataFromForm() {
    return {
        NgayXuat: $('#pxk-ngayxuat').val(),
        TenCongTy: $('#pxk-tencongty').val(),
        DiaChiCongTy: $('#pxk-diachi').val(),
        NguoiNhan: $('#pxk-nguoinhan').val(),
        DiaChiGiaoHang: $('#pxk-diachigiao').val(),
        LyDoXuatKho: $('#pxk-lydoxuat').val(),
        NguoiLapPhieu: $('#input-nguoilap').val(),
        ThuKho: $('#input-thukho').val(),
        NguoiGiaoHang: $('#input-nguoigiao').val(),
        NguoiNhanHang: $('#input-nguoinhanhang').val()
    };
}


/**
 * Lấy dữ liệu phiếu đã xuất kho từ API và hiển thị ra bảng
 */
function fetchAndRenderIssuedSlips(page, filters = {}) {
    const listBody = $('#issued-slips-list-body');
    const paginationContainer = $('#pagination-container');
    listBody.html('<tr><td colspan="9" class="p-4 text-center">Đang tải dữ liệu...</td></tr>');
    paginationContainer.empty();

    const params = new URLSearchParams({ page, ...filters });

    $.ajax({
        url: `api/get_issued_slips.php?${params.toString()}`,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data.length > 0) {
                listBody.empty();
                response.data.forEach(slip => {
                    let statusBadge;
                    let actionButtons = '';

                    switch (slip.TrangThai) {
                        case 'Đã giao hàng':
                            statusBadge = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Đã giao hàng</span>';
                            actionButtons = '<span class="text-xs text-gray-500">Hoàn tất</span>';
                            break;
                        case 'Đã hủy':
                            statusBadge = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Đã hủy</span>';
                            actionButtons = '<span class="text-xs text-gray-500">Đã hủy</span>';
                            break;
                        default:
                            statusBadge = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Đã xuất kho</span>';
                            actionButtons = `
                                <button class="update-status-btn bg-green-600 text-white px-3 py-1 rounded-md text-xs hover:bg-green-700" data-pxk-id="${slip.PhieuXuatKhoID}" data-status="Đã giao hàng">
                                    <i class="fas fa-check-circle mr-1"></i> Giao thành công
                                </button>
                                <button class="update-status-btn bg-red-600 text-white px-3 py-1 rounded-md text-xs hover:bg-red-700 ml-2" data-pxk-id="${slip.PhieuXuatKhoID}" data-status="Đã hủy">
                                    <i class="fas fa-times-circle mr-1"></i> Hủy đơn
                                </button>
                            `;
                            break;
                    }
                    
                    const row = `
                        <tr>
                            <td class="p-3 font-semibold">${slip.SoPhieuXuat || ''}</td>
                            <td class="p-3">${slip.SoYCSX || 'N/A'}</td>
                            <td class="p-3">${slip.MaKhachHang || ''}</td>
                            <td class="p-3">${slip.TenDuAn || ''}</td>
                            <td class="p-3">${slip.NguoiNhan || ''}</td>
                            <td class="p-3">${slip.NgayXuat ? new Date(slip.NgayXuat).toLocaleDateString('vi-VN') : ''}</td>
                            <td class="p-3 text-center">${statusBadge}</td>
                            <td class="p-3 text-center space-x-2">
                                <button class="view-pxk-btn bg-gray-500 text-white px-3 py-1 rounded-md text-xs hover:bg-gray-600" data-pxk-id="${slip.PhieuXuatKhoID}"><i class="fas fa-eye mr-1"></i> Xem/Sửa</button>
                                <button class="manage-bbgh-btn bg-blue-500 text-white px-3 py-1 rounded-md text-xs hover:bg-blue-600" data-pxk-id="${slip.PhieuXuatKhoID}"><i class="fas fa-dolly-flatbed mr-1"></i> BBGH</button>
                                <button class="manage-cccl-btn bg-purple-500 text-white px-3 py-1 rounded-md text-xs hover:bg-purple-600" data-pxk-id="${slip.PhieuXuatKhoID}"><i class="fas fa-certificate mr-1"></i> CCCL</button>
                            </td>
                             <td class="p-3 text-center">${actionButtons}</td>
                        </tr>
                    `;
                    listBody.append(row);
                });

                if (response.pagination) {
                    renderPagination(response.pagination.current_page, response.pagination.total_pages, filters);
                }
            } else {
                listBody.html('<tr><td colspan="9" class="p-4 text-center">Không tìm thấy phiếu xuất kho nào phù hợp.</td></tr>');
            }
        },
        error: function(xhr) {
            console.error('Lỗi khi tải danh sách đã xuất:', xhr.responseText);
            listBody.html('<tr><td colspan="9" class="p-4 text-center text-red-500">Lỗi khi tải dữ liệu.</td></tr>');
        }
    });
}

/**
 * Hiển thị các nút điều khiển phân trang.
 */
function renderPagination(currentPage, totalPages, filters) {
    const paginationContainer = $('#pagination-container');
    paginationContainer.empty();
    if (totalPages <= 1) return;

    let paginationHtml = '<nav><ul class="inline-flex items-center -space-x-px">';
    paginationHtml += `<li><a href="#" class="pagination-link py-2 px-3 ml-0 leading-tight text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100 ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${currentPage - 1}">Trước</a></li>`;
    for (let i = 1; i <= totalPages; i++) {
        paginationHtml += `<li><a href="#" class="pagination-link py-2 px-3 leading-tight ${i === currentPage ? 'text-blue-600 bg-blue-50 border-blue-300' : 'text-gray-500 bg-white border-gray-300'} hover:bg-gray-100" data-page="${i}">${i}</a></li>`;
    }
    paginationHtml += `<li><a href="#" class="pagination-link py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-100 ${currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${currentPage + 1}">Sau</a></li>`;
    paginationHtml += '</ul></nav>';
    paginationContainer.html(paginationHtml);
}


/**
 * Hiển thị một modal xác nhận tùy chỉnh.
 */
/**
 * Hiển thị một modal xác nhận tùy chỉnh.
 */
function showXuatKhoConfirmationModal(title, message, onConfirm) {
    $('#xuatkho-confirmation-modal').remove();
    const modalHtml = `
        <div id="xuatkho-confirmation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 h-full w-full flex items-center justify-center z-50">
            <div class="relative mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
                <h3 class="text-lg font-medium text-gray-900 text-center">${title}</h3>
                <div class="mt-4 px-4 py-3 bg-gray-50 rounded-lg text-sm text-gray-700 max-h-96 overflow-y-auto">${message}</div>
                <div class="mt-4 items-center px-4 py-3 text-center">
                    <button id="confirm-action-btn" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-md shadow-sm hover:bg-blue-700"><i class="fas fa-check-circle mr-2"></i>Xác Nhận</button>
                    <button id="cancel-action-btn" class="ml-3 px-4 py-2 bg-gray-300 text-gray-800 font-medium rounded-md shadow-sm hover:bg-gray-400">Hủy Bỏ</button>
                </div>
            </div>
        </div>`;
    $('body').append(modalHtml);
    $('#confirm-action-btn').on('click', function() {
        onConfirm?.();
        $('#xuatkho-confirmation-modal').remove();
    });
    $('#cancel-action-btn').on('click', function() {
        $('#xuatkho-confirmation-modal').remove();
        $('#save-xuatkho-btn').prop('disabled', false).html('<i class="fas fa-check-circle mr-2"></i>Hoàn Tất Xuất Kho');
    });
}

/**
 * Lưu dữ liệu Phiếu Xuất Kho hiện tại.
 * @returns {Promise<boolean>} Promise giải quyết là true nếu thành công.
 */
function savePxk() {
    return new Promise((resolve, reject) => {
        const btn = $('#update-pxk-btn');
        const pxkId = new URLSearchParams(window.location.search).get('pxk_id');

        if (!pxkId) {
            // Trường hợp này không nên xảy ra nếu logic đúng
            reject(new Error('Không tìm thấy ID Phiếu xuất kho.'));
            return;
        }

        const itemsUpdates = [];
        $('#xuatkho-items-body tr.product-row').each(function() {
            const row = $(this);
            itemsUpdates.push({
                detail_id: row.data('detail-id'),
                soLuongThucXuat: parseInt(row.find('.so-luong-thuc-xuat').val()) || 0,
                taiSo: row.find('.tai-so').val(),
                ghiChu: row.find('.ghi-chu').val()
            });
        });

        const payload = {
            pxk_id: pxkId,
            header: getHeaderDataFromForm(),
            items: itemsUpdates
        };

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...');

        $.ajax({
            url: 'api/update_phieuxuatkho.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    resolve(true);
                } else {
                    App.showMessageModal(`Lỗi khi cập nhật PXK: ${response.message}`, 'error');
                    reject(new Error(response.message));
                }
            },
            error: function(xhr) {
                App.showMessageModal('Lỗi hệ thống, không thể kết nối đến server.', 'error');
                reject(new Error(xhr.responseText));
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Lưu thay đổi');
            }
        });
    });
}


/*******************************************************************************
 * 3. LẮNG NGHE SỰ KIỆN (EVENT DELEGATION)
 *******************************************************************************/

$(document).on('click', '.create-pxk-btn', function() {
    const cbhId = $(this).data('cbh-id');
    const url = `?page=xuatkho_create&id=${cbhId}`;
    history.pushState({ page: 'xuatkho_create', id: cbhId }, '', url);
    window.App.handleRouting();
});

$(document).on('click', '.view-pxk-btn', function() {
    const pxkId = $(this).data('pxk-id');
    const url = `?page=xuatkho_create&pxk_id=${pxkId}`;
    history.pushState({ page: 'xuatkho_create', pxk_id: pxkId }, '', url);
    window.App.handleRouting();
});

$(document).on('click', '.pagination-link', function(e) {
    e.preventDefault();
    const page = parseInt($(this).data('page'));
    const currentPageLink = $('#pagination-container .pagination-link[data-page]').filter(function() { return $(this).css('color').toString().includes('rgb(37, 99, 235)'); });
    const currentPage = currentPageLink.length > 0 ? parseInt(currentPageLink.data('page')) : 1;
    const totalPages = $('#pagination-container .pagination-link').length - 2;
    if (page && page > 0 && page <= totalPages && page !== currentPage) {
        const filters = {
            search: $('#search-input').val(),
            status: $('#status-filter').val(),
            startDate: $('#start-date-filter').val(),
            endDate: $('#end-date-filter').val()
        };
        fetchAndRenderIssuedSlips(page, filters);
    }
});

$(document).on('click', '.manage-bbgh-btn', function() {
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    $.post('api/create_or_get_bbgh.php', { pxk_id: btn.data('pxk-id') }, function(response) {
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

$(document).on('click', '.manage-cccl-btn', function() {
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    $.post('api/create_or_get_cccl.php', { pxk_id: btn.data('pxk-id') }, function(response) {
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

$(document).on('click', '.update-status-btn', function() {
    const btn = $(this);
    const pxkId = btn.data('pxk-id');
    const newStatus = btn.data('status');
    const confirmationMessage = `Bạn có chắc chắn muốn cập nhật trạng thái đơn hàng này thành "${newStatus}" không?`;

    showXuatKhoConfirmationModal('Xác nhận cập nhật', confirmationMessage, function() {
        btn.closest('td').html('<i class="fas fa-spinner fa-spin"></i>');

        $.post('api/update_delivery_status.php', {
            pxk_id: pxkId,
            status: newStatus
        }, function(response) {
            if (response.success) {
                App.showMessageModal(response.message, 'success');
                $('#filter-btn').click(); 
            } else {
                App.showMessageModal(response.message || 'Có lỗi xảy ra.', 'error');
                $('#filter-btn').click();
            }
        }, 'json').fail(function() {
            App.showMessageModal('Lỗi kết nối đến server.', 'error');
            $('#filter-btn').click();
        });
    });
});


// Sự kiện cho nút "Hoàn tất xuất kho" (tạo mới)
$(document).on('click', '#save-xuatkho-btn', function() {
    const btn = $(this);
    const cbhId = new URLSearchParams(window.location.search).get('id');
    if (!cbhId) return;

    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...');

    const itemsData = [];
    let confirmationMessage = '<p class="mb-2 font-semibold">Hệ thống sẽ trừ tồn kho các sản phẩm sau:</p><ul class="list-disc pl-5 text-left">';
    let hasItemsToIssue = false;

    $('#xuatkho-items-body tr.product-row').each(function() {
        const row = $(this);
        const soLuongThucXuat = parseInt(row.find('.so-luong-thuc-xuat').val()) || 0;
        const item = {
            variant_id: row.data('variant-id'),
            maHang: row.find('.ma-hang').text(),
            tenSanPham: row.find('.ten-san-pham').text().trim(),
            soLuongThucXuat: soLuongThucXuat,
            taiSo: row.find('.tai-so').val(),
            ghiChu: row.find('.ghi-chu').val(),
        };
        itemsData.push(item);
        if (soLuongThucXuat > 0) {
            hasItemsToIssue = true;
            confirmationMessage += `<li><strong>${item.tenSanPham}</strong> (${item.maHang}): <span class="font-bold text-red-600">${item.soLuongThucXuat}</span></li>`;
        }
    });
    confirmationMessage += '</ul><p class="mt-3">Bạn có chắc chắn muốn tiếp tục?</p>';

    if (!hasItemsToIssue) {
        App.showMessageModal('Không có sản phẩm nào được xuất kho (số lượng thực xuất > 0).', 'error');
        btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-2"></i>Hoàn Tất Xuất Kho');
        return;
    }

    showXuatKhoConfirmationModal('Xác Nhận Xuất Kho', confirmationMessage, function() {
        const payload = {
            cbh_id: cbhId,
            header: getHeaderDataFromForm(), // Lấy dữ liệu header từ form
            items: itemsData.filter(item => item.soLuongThucXuat > 0),
        };

        btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...');

        $.ajax({
            url: 'api/save_phieuxuatkho.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    App.showMessageModal(response.message, 'success');
                    history.pushState({ page: 'xuatkho_issued_list' }, '', `?page=xuatkho_issued_list`);
                    window.App.handleRouting();
                } else {
                    App.showMessageModal(`Lỗi: ${response.message}`, 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-2"></i>Hoàn Tất Xuất Kho');
                }
            },
            error: function() {
                App.showMessageModal('Lỗi hệ thống không thể lưu phiếu.', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-2"></i>Hoàn Tất Xuất Kho');
            }
        });
    });
});


$(document).on('click', '#print-pxk-btn', function() {
    window.print();
});

// Sự kiện cho nút "Lưu thay đổi" trên phiếu xuất kho đã có
$(document).on('click', '#update-pxk-btn', function() {
    savePxk()
        .then(() => App.showMessageModal('Cập nhật phiếu xuất kho thành công!', 'success'))
        .catch(error => console.error("Lưu PXK thất bại:", error.message));
});


$(document).on('click', '#export-pxk-excel-btn-3', function() {
    const exportAction = () => {
        const pxkId = new URLSearchParams(window.location.search).get('pxk_id');
        if (pxkId) {
            window.location.href = `api/export_pxk_excel.php?pxk_id=${pxkId}`;
        }
    };

    App.showMessageModal('Đang lưu dữ liệu và chuẩn bị xuất file...', 'info');
    savePxk().then(() => {
        App.showMessageModal('Lưu thành công. Bắt đầu tải file Excel.', 'success', 2000);
        exportAction();
    }).catch(error => {
        console.error("Không thể xuất Excel do lưu PXK thất bại:", error.message);
    });
});

$(document).on('click', '#export-pxk-pdf-btn', function() {
    const exportAction = () => {
        const pxkId = new URLSearchParams(window.location.search).get('pxk_id');
        if (pxkId) {
            window.open(`api/export_pxk_pdf.php?pxk_id=${pxkId}`, '_blank');
        }
    };
    
    App.showMessageModal('Đang lưu dữ liệu và chuẩn bị xuất file...', 'info');
    savePxk().then(() => {
        App.showMessageModal('Lưu thành công. Mở file PDF trong tab mới.', 'success', 2000);
        exportAction();
    }).catch(error => {
        console.error("Không thể xuất PDF do lưu PXK thất bại:", error.message);
    });
});

// *** ĐOẠN MÃ MỚI ĐƯỢC THÊM VÀO ***
// Sự kiện cho nút "Xuất Excel" trên trang danh sách
$(document).on('click', '#export-list-excel-btn', function() {
    const filters = {
        search: $('#search-input').val(),
        status: $('#status-filter').val(),
        startDate: $('#start-date-filter').val(),
        endDate: $('#end-date-filter').val()
    };

    // Loại bỏ các giá trị rỗng để URL gọn gàng hơn
    const validFilters = Object.fromEntries(Object.entries(filters).filter(([_, v]) => v != null && v !== ''));

    const params = new URLSearchParams(validFilters);
    
    // Điều hướng đến API để tải file. Trình duyệt sẽ tự động xử lý việc tải xuống.
    window.location.href = `api/export_issued_list_excel.php?${params.toString()}`;
});
