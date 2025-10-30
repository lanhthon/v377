/**
 * =================================================================================
 * MODULE QUẢN LÝ NHẬP KHO BÁN THÀNH PHẨM (BTP) - VERSION 4.0
 * =================================================================================
 * - [UPDATE] Tích hợp bộ lọc, phân trang và cột ghi chú chung.
 * - [UPDATE] Cấu trúc lại hàm để dễ đọc và bảo trì.
 * - [UPDATE] Sau khi lưu phiếu nhập kho BTP thành công, hệ thống sẽ tự động
 * chuyển hướng người dùng trở lại trang chi tiết phiếu chuẩn bị hàng gốc.
 * =================================================================================
 */

// Hàm render bảng dữ liệu
function renderPNKBTPTable(data) {
    const listBody = $('#danhsach-pnk-btp-body');
    listBody.empty();
    if (data && data.length > 0) {
        data.forEach(pnk => {
            const ngayNhap = pnk.NgayNhap ? new Date(pnk.NgayNhap).toLocaleDateString('vi-VN') : 'N/A';
            const row = `
                <tr class="hover:bg-gray-50">
                    <td class="p-3 font-semibold text-blue-600">${pnk.SoPhieuNhapKhoBTP}</td>
                    <td class="p-3">${ngayNhap}</td>
                    <td class="p-3">${pnk.LyDoNhap || ''}</td>
                    <td class="p-3">${pnk.SoLenhSX || 'N/A'}</td>
                    <td class="p-3 text-sm text-gray-600">${pnk.GhiChu || ''}</td>
                    <td class="p-3">${pnk.TenNguoiTao || 'N/A'}</td>
                    <td class="p-3 text-center">
                        <button data-pnk-btp-id="${pnk.PNK_BTP_ID}" class="view-pnk-btp-btn text-indigo-600 hover:text-indigo-900">
                            <i class="fas fa-eye mr-1"></i>Xem
                        </button>
                    </td>
                </tr>`;
            listBody.append(row);
        });
    } else {
        listBody.html('<tr><td colspan="7" class="p-4 text-center">Không tìm thấy phiếu nhập kho BTP nào phù hợp.</td></tr>');
    }
}

// Hàm render các nút phân trang
function renderPNKBTPPagination(pagination) {
    const { currentPage, totalPages } = pagination;
    const paginationControls = $('#pagination-controls');
    paginationControls.empty();

    if (totalPages <= 1) return;

    let paginationHtml = '<div class="flex items-center space-x-1">';

    // Nút Trang đầu
    paginationHtml += `<button class="pagination-btn px-3 py-1 rounded-md text-sm ${currentPage === 1 ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-100'}" data-page="1" ${currentPage === 1 ? 'disabled' : ''}><i class="fas fa-angle-double-left"></i></button>`;
    
    // Nút Lùi 1 trang
    paginationHtml += `<button class="pagination-btn px-3 py-1 rounded-md text-sm ${currentPage === 1 ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-100'}" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}><i class="fas fa-angle-left"></i></button>`;

    // Các trang số
    const maxPagesToShow = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
    
    if (endPage - startPage + 1 < maxPagesToShow) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += `<button class="pagination-btn px-3 py-1 rounded-md text-sm ${i === currentPage ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'}" data-page="${i}">${i}</button>`;
    }
    
    // Nút Tới 1 trang
    paginationHtml += `<button class="pagination-btn px-3 py-1 rounded-md text-sm ${currentPage === totalPages ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-100'}" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}><i class="fas fa-angle-right"></i></button>`;
    
    // Nút Trang cuối
    paginationHtml += `<button class="pagination-btn px-3 py-1 rounded-md text-sm ${currentPage === totalPages ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-100'}" data-page="${totalPages}" ${currentPage === totalPages ? 'disabled' : ''}><i class="fas fa-angle-double-right"></i></button>`;
    
    paginationHtml += '</div>';
    paginationControls.html(paginationHtml);
}


// Hàm chính để lấy dữ liệu
function fetchPNKBTPData(page = 1) {
    const listBody = $('#danhsach-pnk-btp-body');
    listBody.html('<tr><td colspan="7" class="p-4 text-center"><i class="fas fa-spinner fa-spin mr-2"></i>Đang tải dữ liệu...</td></tr>');

    const filters = {
        startDate: $('#filter-start-date').val(),
        endDate: $('#filter-end-date').val(),
        soPhieu: $('#filter-so-phieu').val(),
        soLSX: $('#filter-so-lsx').val(),
        ghiChu: $('#filter-ghi-chu').val(),
        page: page,
        limit: 15
    };
    
    const params = new URLSearchParams(filters).toString();

    $.getJSON(`api/get_list_pnk_btp.php?${params}`, function(response) {
        if (response.success) {
            renderPNKBTPTable(response.data);
            renderPNKBTPPagination(response.pagination);
        } else {
            listBody.html(`<tr><td colspan="7" class="p-4 text-center text-red-500">Lỗi: ${response.message}</td></tr>`);
        }
    }).fail(function() {
        listBody.html('<tr><td colspan="7" class="p-4 text-center text-red-500">Lỗi kết nối đến máy chủ.</td></tr>');
    });
}


/**
 * Khởi tạo trang Lịch sử Nhập kho BTP.
 */
function initializeDanhSachPNKBTPPage() {
    // Tải dữ liệu lần đầu
    fetchPNKBTPData(1);

    // Xóa các event listener cũ để tránh bị gọi nhiều lần
    $(document).off('click', '#filter-btn');
    $(document).off('click', '#reset-filter-btn');
    $(document).off('click', '.pagination-btn');

    // Gắn event listener cho nút Lọc
    $(document).on('click', '#filter-btn', function() {
        fetchPNKBTPData(1); // Luôn bắt đầu từ trang 1 khi lọc
    });

    // Gắn event listener cho nút Làm Mới
    $(document).on('click', '#reset-filter-btn', function() {
        $('#filter-container input').val('');
        fetchPNKBTPData(1);
    });

    // Gắn event listener cho các nút phân trang
    $(document).on('click', '.pagination-btn', function() {
        const page = $(this).data('page');
        if (page) {
            fetchPNKBTPData(page);
        }
    });
}


/**
 * Khởi tạo trang Danh sách Lệnh Sản Xuất đang chờ nhập kho BTP.
 */
function initializeNhapKhoBTPListPage() {
    const listBody = $('#nhapkho-btp-list-body');
    listBody.html('<tr><td colspan="5" class="p-4 text-center">Đang tải dữ liệu...</td></tr>');

    $.getJSON('api/get_lsx_ready_for_btp_receipt.php', function(response) {
        if (response.success && response.data.length > 0) {
            listBody.empty();
            response.data.forEach(lsx => {
                const ngayTao = lsx.NgayTao ? new Date(lsx.NgayTao).toLocaleDateString('vi-VN') : 'N/A';
                const ngayHT = lsx.NgayHoanThanhThucTe ? new Date(lsx.NgayHoanThanhThucTe).toLocaleDateString('vi-VN') : 'Chưa có';
                const row = `
                    <tr class="hover:bg-gray-50">
                        <td class="p-3 font-semibold text-green-600">${lsx.SoLenhSX}</td>
                        <td class="p-3">${lsx.SoYCSX}</td>
                        <td class="p-3">${ngayTao}</td>
                        <td class="p-3">${ngayHT}</td>
                        <td class="p-3 text-center">
                             <button data-cbh-id="${lsx.CBH_ID}" class="create-btp-receipt-btn bg-blue-500 text-white px-3 py-1 rounded-md text-xs hover:bg-blue-600">
                                 <i class="fas fa-plus-circle mr-1"></i> Tạo Phiếu Nhập
                             </button>
                        </td>
                    </tr>`;
                listBody.append(row);
            });
        } else {
            listBody.html('<tr><td colspan="5" class="p-4 text-center">Không có Lệnh sản xuất BTP nào đang chờ nhập kho.</td></tr>');
        }
    });
}

/**
 * Khởi tạo trang Tạo/Xem Phiếu Nhập Kho BTP.
 */
function initializeNhapKhoBTPCreatePage() {
    const urlParams = new URLSearchParams(window.location.search);
    const cbhId = parseInt(urlParams.get('cbh_id')) || 0;
    const pnkBtpId = parseInt(urlParams.get('pnk_btp_id')) || 0; 
    
    const saveBtn = $('#save-nhapkho-btp-btn');
    const exportPdfBtn = $('#export-pnk-btp-pdf-btn');
    const exportExcelBtn = $('#export-pnk-btp-excel-btn');
    const itemsBody = $('#nhapkho-btp-items-body');

    $('#back-to-list-btn').off('click').on('click', () => history.back());
    
    if (pnkBtpId) {
        // === CHẾ ĐỘ XEM CHI TIẾT ===
        $('#page-title').text('Xem Lại Phiếu Nhập Kho BTP');
        saveBtn.hide(); 
        exportPdfBtn.show().data('pnk-btp-id', pnkBtpId);
        exportExcelBtn.show().data('pnk-btp-id', pnkBtpId);
        
        $.getJSON(`api/get_pnk_btp_details.php?pnk_btp_id=${pnkBtpId}`, function(response) {
            if (response.success && response.data) {
                const header = response.data.header;
                const items = response.data.items;

                $('#info-sophieu').text(header.SoPhieuNhapKhoBTP);
                const ngayNhap = new Date(header.NgayNhap);
                $('#info-ngaynhap').text(`Ngày ${ngayNhap.getDate()} tháng ${ngayNhap.getMonth() + 1} năm ${ngayNhap.getFullYear()}`);
                $('#info-lenhsx').text(header.SoLenhSX || 'N/A');
                $('#info-nguoilap').text(header.TenNguoiTao);
                $('#info-lydo').text(header.LyDoNhap);

                // Parse GhiChu to extract NguoiGiao and the actual user note.
                let nguoiGiao = 'Không có';
                let ghiChu = header.GhiChu || '';
                const nguoiGiaoPrefix = "Người giao: ";

                if (ghiChu.startsWith(nguoiGiaoPrefix)) {
                    const parts = ghiChu.substring(nguoiGiaoPrefix.length).split('. ');
                    nguoiGiao = parts.shift();
                    ghiChu = parts.join('. ');
                }

                $('#info-nguoigiao').text(nguoiGiao);
                $('#info-ghichu').text(ghiChu || 'Không có');

                // Hiển thị tổng tiền nếu có
                if (header.TongTien && parseFloat(header.TongTien) > 0) {
                    $('#container-tongtien').show();
                    $('#info-tongtien').text(window.App.formatNumber(header.TongTien) + ' VNĐ');
                } else {
                    $('#container-tongtien').hide();
                }

                itemsBody.empty();
                items.forEach((item, index) => {
                    const rowHtml = `<tr class="bg-gray-50">
                        <td class="p-2 border text-center">${index + 1}</td>
                        <td class="p-2 border">${item.MaBTP}</td>
                        <td class="p-2 border">${item.TenBTP}</td>
                        <td class="p-2 border text-center">${item.DonViTinh || 'Cây'}</td>
                        <td class="p-2 border text-center">${window.App.formatNumber(item.SoLuongTheoLenhSX) || 'N/A'}</td>
                        <td class="p-2 border text-center font-semibold">${window.App.formatNumber(item.SoLuong)}</td>
                        <td class="p-2 border">${item.GhiChu || ''}</td>
                    </tr>`;
                    itemsBody.append(rowHtml);
                });
            } else {
                window.App.showMessageModal(response.message || 'Không thể tải dữ liệu chi tiết.', 'error');
            }
        });

    } else if (cbhId) {
        // === CHẾ ĐỘ TẠO MỚI ===
        $('#page-title').text('Tạo Phiếu Nhập Kho Bán Thành Phẩm');
        saveBtn.show();
        exportPdfBtn.hide();
        exportExcelBtn.hide();
        $('#container-tongtien').hide(); // Ẩn mục tổng tiền khi tạo mới

        $.getJSON(`api/get_btp_details_for_receipt.php?cbh_id=${cbhId}`, function(response) {
            if (response.success) {
                $('#info-ngaynhap').text(`Ngày ${new Date().getDate()} tháng ${new Date().getMonth() + 1} năm ${new Date().getFullYear()}`);
                $('#info-lenhsx').text(response.header.SoLenhSX);
                $('#info-nguoilap').text(window.App.currentUser.fullName || 'Không xác định');
                
                itemsBody.empty();
                let hasItemsToReceive = false;
                if (response.items && response.items.length > 0) {
                    response.items.forEach((item, index) => {
                        if (item.SoLuongCanNhap > 0) {
                            hasItemsToReceive = true;
                            const rowHtml = `
                                <tr class="product-row" data-btp-id="${item.BTP_ID}" data-chitiet-lsx-id="${item.ChiTiet_LSX_ID}">
                                    <td class="p-2 border text-center">${index + 1}</td>
                                    <td class="p-2 border">${item.MaBTP}</td>
                                    <td class="p-2 border">${item.TenBTP}</td>
                                    <td class="p-2 border text-center">${item.DonViTinh || 'Cây'}</td>
                                    <td class="p-2 border text-center text-gray-700">${window.App.formatNumber(item.SoLuongCanNhap)}</td>
                                    <td class="p-2 border"><input type="number" class="w-full p-1 text-center so-luong-thuc-nhap" value="${item.SoLuongCanNhap}" min="0"></td>
                                    <td class="p-2 border"><input type="text" class="w-full p-1 ghi-chu"></td>
                                </tr>`;
                            itemsBody.append(rowHtml);
                        }
                    });
                }
                
                if (!hasItemsToReceive) {
                    itemsBody.html(`<tr><td colspan="7" class="p-4 text-center">Tất cả Bán thành phẩm đã được nhập kho.</td></tr>`);
                    saveBtn.prop('disabled', true);
                }
            } else {
                window.App.showMessageModal(response.message, 'error');
            }
        });
    } else {
        window.App.showMessageModal('ID không hợp lệ.', 'error');
        return;
    }
    
    // Logic nút Lưu
    saveBtn.off('click').on('click', function() {
        if (pnkBtpId) return; // Không cho lưu ở chế độ xem

        const itemsData = [];
        $('#nhapkho-btp-items-body tr.product-row').each(function() {
            const row = $(this);
            const soLuongThucNhap = parseInt(row.find('.so-luong-thuc-nhap').val()) || 0;
            if (soLuongThucNhap > 0) {
                itemsData.push({
                    btp_id: row.data('btp-id'),
                    chitiet_lsx_id: row.data('chitiet-lsx-id'),
                    soLuongThucNhap: soLuongThucNhap,
                    ghiChu: row.find('.ghi-chu').val()
                });
            }
        });

        if (itemsData.length === 0) {
            window.App.showMessageModal('Vui lòng nhập số lượng thực nhập lớn hơn 0 cho ít nhất một sản phẩm.', 'warning');
            return;
        }

        window.App.showConfirmationModal('Xác Nhận Nhập Kho BTP', `Bạn chắc chắn muốn nhập kho các bán thành phẩm này?`, function() {
            saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...');
            $.ajax({
                url: 'api/save_phieunhapkho_btp.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ cbh_id: cbhId, items: itemsData }),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        window.App.showMessageModal('Nhập kho BTP thành công! Đang quay lại phiếu chuẩn bị hàng...', 'success');
                        
                        setTimeout(() => {
                            if (typeof $ !== 'undefined' && $('#message-modal').length) {
                                 $('#message-modal').remove();
                            }
                            
                            const url = `?page=chuanbi_hang_edit&id=${cbhId}`;
                            history.pushState({ page: 'chuanbi_hang_edit', id: cbhId }, '', url);
                            window.App.handleRouting();
                        }, 1500); 
                    } else {
                        window.App.showMessageModal(res.message, 'error');
                        saveBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Hoàn Tất Nhập Kho');
                    }
                },
                error: function() {
                    window.App.showMessageModal('Có lỗi xảy ra, không thể lưu phiếu nhập kho BTP.', 'error');
                    saveBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Hoàn Tất Nhập Kho');
                }
            });
        });
    });
}

// --- EVENT DELEGATION ---

// Sự kiện click cho nút "Xuất PDF"
$(document).on('click', '#export-pnk-btp-pdf-btn', function() {
    const pnkBtpId = $(this).data('pnk-btp-id');
    if (pnkBtpId) {
        window.open(`api/export_pnk_btp_pdf.php?pnk_btp_id=${pnkBtpId}`, '_blank');
    }
});

// Sự kiện click cho nút "Xuất Excel"
$(document).on('click', '#export-pnk-btp-excel-btn', function() {
    const pnkBtpId = $(this).data('pnk-btp-id');
    if (pnkBtpId) {
        window.location.href = `api/export_pnk_btp_excel.php?pnk_btp_id=${pnkBtpId}`;
    }
});

// Sự kiện click nút "Tạo Phiếu Nhập" từ trang danh sách chờ
$(document).on('click', '.create-btp-receipt-btn', function() {
    const cbhId = $(this).data('cbh-id');
    const url = `?page=nhapkho_btp_create&cbh_id=${cbhId}`;
    history.pushState({ page: 'nhapkho_btp_create', cbh_id: cbhId }, '', url);
    window.App.handleRouting();
});

// Sự kiện click nút "Xem" từ trang lịch sử
$(document).on('click', '.view-pnk-btp-btn', function() {
    const pnkBtpId = $(this).data('pnk-btp-id');
    const url = `?page=nhapkho_btp_create&pnk_btp_id=${pnkBtpId}`;
    history.pushState({ page: 'nhapkho_btp_create', pnk_btp_id: pnkBtpId }, '', url);
    window.App.handleRouting();
});
$(document).on('click', '#create-pnk-btp-btn', function() {
    const url = `?page=nhapkho_btp_list`;
    history.pushState({ page: 'nhapkho_btp_list' }, '', url);
    window.App.handleRouting();
});
