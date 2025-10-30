// File: public/js/giaohang_module.js
// Tệp này chứa toàn bộ logic giao diện cho các module Giao hàng,
// bao gồm Biên bản giao hàng (BBGH) và Chứng chỉ chất lượng (CCCL).

/**
 * =================================================================================
 * PHẦN 1: LOGIC LIÊN QUAN ĐẾN BIÊN BẢN GIAO HÀNG (BBGH)
 * =================================================================================
 */

/**
 * Khởi tạo trang danh sách Biên bản giao hàng (BBGH).
 */
function initializeBbghListPage() {
    const listBody = $('#bbgh-list-body');
    listBody.html('<tr><td colspan="6" class="text-center p-4">Đang tải dữ liệu...</td></tr>');

    $.ajax({
        url: 'api/get_bbgh_list.php',
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.success && response.data.length > 0) {
                listBody.empty();
                response.data.forEach(bbgh => {
                    const row = `
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3 font-semibold text-blue-600">${bbgh.SoBBGH || ''}</td>
                            <td class="p-3">${bbgh.SoYCSX || 'N/A'}</td>
                            <td class="p-3">${bbgh.TenCongTy || ''}</td>
                            <td class="p-3">${bbgh.NgayTao ? new Date(bbgh.NgayTao).toLocaleDateString('vi-VN') : ''}</td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">${bbgh.TrangThai || 'Đã tạo'}</span>
                            </td>
                            <td class="p-3 text-center">
                                <button class="view-bbgh-btn bg-blue-500 text-white px-3 py-1 rounded-md text-xs hover:bg-blue-600" data-bbgh-id="${bbgh.BBGH_ID}">
                                    <i class="fas fa-eye mr-1"></i> Xem
                                </button>
                            </td>
                        </tr>
                    `;
                    listBody.append(row);
                });
            } else {
                listBody.html('<tr><td colspan="6" class="text-center p-4">Chưa có biên bản giao hàng nào được tạo.</td></tr>');
            }
        },
        error: function (xhr) {
            console.error('Lỗi khi tải danh sách BBGH:', xhr.responseText);
            listBody.html('<tr><td colspan="6" class="p-4 text-center text-red-500">Lỗi khi tải dữ liệu.</td></tr>');
        }
    });
}

/**
 * Khởi tạo trang xem chi tiết Biên bản giao hàng (BBGH).
 */
function initializeBbghViewPage() {
    const params = new URLSearchParams(window.location.search);
    const bbghId = params.get('id');

    if (!bbghId) {
        App.showMessageModal('Không tìm thấy ID Biên bản Giao hàng.', 'error');
        return;
    }
    
    markAsSaved();
    
    $('#printable-area').find('input, textarea').val('...');
    $('#printable-area').find('span[id], p[id]').text('...');

    $.ajax({
        url: `api/get_bbgh_details.php?bbgh_id=${bbghId}`,
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.success && response.header) {
                const header = response.header;
                const items = response.items;

                $('#info-sobbgh').text(`Số: ${header.SoBBGH || '...'}`);
                
                const ngayGiao = header.NgayGiaoHienThi ? new Date(header.NgayGiaoHienThi) : new Date();
                $('#bbgh-ngay-giao').val(ngayGiao.toISOString().split('T')[0]);
                
                $('#bbgh-nguoi-lap-phieu').val(header.NguoiGiaoHangHienThi || '');
                $('#bbgh-sdt-nguoi-lap').val(header.SdtNguoiGiaoHangHienThi || '');
                $('#bbgh-footer-nguoigiao').val(header.NguoiGiaoHangHienThi || '');
                $('#bbgh-sanpham').val(header.SanPhamHienThi || 'Gối đỡ PU Foam & Cùm Ula 3i-Fix');

                $('#bbgh-tencongty-khach').val(header.TenCongTy || '');
                $('#bbgh-diachi-khach').val(header.DiaChiKhach || '');
                $('#bbgh-nguoi-nhan').val(header.NguoiNhanHang || '');
                $('#bbgh-sdt-nguoi-nhan').val(header.SoDienThoaiNhanHang || '');
                $('#bbgh-duan').val(header.DuAn || '');
                $('#bbgh-diachi-giaohang').val(header.DiaChiGiaoHang || '');
                $('#bbgh-footer-nguoinhan').val(header.NguoiNhanHang || '');
                
                $('#bbgh-soycsx').text(header.SoYCSX || '...');

                const itemsBody = $('#bbgh-items-body');
                itemsBody.empty();
                
                if (Array.isArray(items) && items.length > 0) {
                     items.sort((a, b) => {
                        const getRank = (maHang) => {
                            if (!maHang) return 3;
                            if (maHang.startsWith('PUR')) return 1;
                            if (maHang.startsWith('ULA')) return 2;
                            return 3;
                        };
                        return getRank(a.MaHang) - getRank(b.MaHang);
                    });

                    items.forEach((item, index) => {
                        const rowHtml = `
                            <tr class="border border-black">
                                <td class="p-2 border border-black text-center">${index + 1}</td>
                                <td class="p-2 border border-black">${item.MaHang || ''}</td>
                                <td class="p-2 border border-black">${item.TenSanPham || ''}</td>
                                <td class="p-2 border border-black text-center">${item.DonViTinh}</td>
                                <td class="p-2 border border-black text-center">${item.SoLuong || 0}</td>
                                <td class="p-2 border border-black text-center"><input type="text" class="editable-field text-center so-thung-input" value="${item.SoThung || ''}" data-id="${item.ChiTietBBGH_ID}"></td>
                                <td class="p-2 border border-black"><input type="text" class="editable-field ghi-chu-input" value="${item.GhiChu || ''}" data-id="${item.ChiTietBBGH_ID}"></td>
                            </tr>`;
                        itemsBody.append(rowHtml);
                    });
                } else {
                    itemsBody.html('<tr><td colspan="7" class="text-center p-4">Không có chi tiết hàng hóa.</td></tr>');
                }
                
                markAsSaved();
            } else {
                App.showMessageModal(response.message || 'Không tìm thấy dữ liệu cho biên bản này.', 'error');
            }
        },
        error: function (xhr) {
            App.showMessageModal('Lỗi hệ thống khi tải chi tiết BBGH.', 'error');
            console.error(xhr.responseText);
        }
    });
}

/**
 * =================================================================================
 * PHẦN 2: LOGIC LIÊN QUAN ĐẾN CHỨNG CHỈ CHẤT LƯỢNG (CCCL)
 * =================================================================================
 */

/**
 * Khởi tạo trang xem chi tiết Chứng chỉ chất lượng (CCCL).
 */
function initializeCcclViewPage() {
    const params = new URLSearchParams(window.location.search);
    const ccclId = params.get('id');

    if (!ccclId) {
        App.showMessageModal('Không tìm thấy ID Chứng chỉ chất lượng.', 'error');
        return;
    }
    
    markAsSavedCccl();
    
    $('#printable-area').find('input, textarea').val('...');
    $('#printable-area').find('span[id]').text('...');

    $.ajax({
        url: `api/get_cccl_details.php?cccl_id=${ccclId}`,
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            $('#cccl-items-body').closest('table').find('thead').css({'background-color': '#92D050', 'color': 'black'});

            if (response.success && response.header) {
                const header = response.header;
                const items = response.items;
                
                $('#info-socccl').text(header.SoCCCL || '...');
                $('#cccl-ngay-cap').val(header.NgayCap ? new Date(header.NgayCap).toISOString().split('T')[0] : '');
                
                $('#cccl-tencongty-khach').val(header.TenCongTyKhach || '');
                $('#cccl-diachi-khach').val(header.DiaChiKhach || '');
                $('#cccl-duan').val(header.TenDuAn || '');
                $('#cccl-diachi-duan').val(header.DiaChiDuAn || '');
                $('#cccl-sanpham').val(header.SanPham || '');
                $('#cccl-soycsx-goc').text(header.SoYCSX || '...');
                $('#cccl-footer-nguoikiemtra').val(header.NguoiKiemTra || '');

                const itemsBody = $('#cccl-items-body');
                itemsBody.empty();
                
                if(Array.isArray(items) && items.length > 0) {
                    items.sort((a, b) => {
                        const getRank = (maHang) => {
                            if (!maHang) return 3;
                            if (maHang.startsWith('PUR')) return 1;
                            if (maHang.startsWith('ULA')) return 2;
                            return 3;
                        };
                        return getRank(a.MaHang) - getRank(b.MaHang);
                    });

                    items.forEach((item, index) => {
                        const rowHtml = `
                            <tr class="border border-black">
                                <td class="p-2 border border-black text-center">${index + 1}</td>
                                <td class="p-2 border border-black">${item.MaHang || ''}</td>
                                <td class="p-2 border border-black">${item.TenSanPham || ''}</td>
                                <td class="p-2 border border-black text-center">${item.DonViTinh}</td>
                                <td class="p-2 border border-black text-center">${item.SoLuong || 0}</td>
                                <td class="p-2 border border-black text-center">
                                    <input type="text" 
                                           class="editable-cccl-field w-full text-center" 
                                           value="${item.TieuChuanDatDuoc || 'Đạt'}" 
                                           data-id="${item.ChiTietCCCL_ID}">
                                </td>
                                <td class="p-2 border border-black">
                                    <input type="text" 
                                           class="editable-cccl-field w-full" 
                                           value="${item.GhiChuChiTiet || ''}" 
                                           data-id="${item.ChiTietCCCL_ID}">
                                </td>
                            </tr>
                        `;
                        itemsBody.append(rowHtml);
                    });
                } else {
                    itemsBody.html('<tr><td colspan="7" class="text-center p-4">Không có chi tiết hàng hóa.</td></tr>');
                }
                markAsSavedCccl();
            } else {
                App.showMessageModal(response.message || 'Không tìm thấy dữ liệu.', 'error');
            }
        },
        error: function (xhr) {
            App.showMessageModal('Lỗi hệ thống khi tải chi tiết CCCL.', 'error');
            console.error(xhr.responseText);
        }
    });
}

/**
 * =================================================================================
 * PHẦN 3: QUẢN LÝ TRẠNG THÁI "CHƯA LƯU"
 * =================================================================================
 */

// --- Quản lý trạng thái cho BBGH ---
let hasUnsavedChanges = false;
function markAsChanged() { hasUnsavedChanges = true; updateSaveButtonState(); }
function markAsSaved() { hasUnsavedChanges = false; updateSaveButtonState(); }
function updateSaveButtonState() {
    const saveBtn = $('#save-bbgh-btn');
    if (hasUnsavedChanges) {
        saveBtn.html('<i class="fas fa-save mr-2"></i>Lưu thay đổi •').removeClass('bg-teal-600').addClass('bg-orange-600');
    } else {
        saveBtn.html('<i class="fas fa-save mr-2"></i>Lưu thay đổi').removeClass('bg-orange-600').addClass('bg-teal-600');
    }
}
function checkUnsavedChanges(actionName) {
    if (hasUnsavedChanges) {
        App.showMessageModal(`Bạn có thay đổi chưa được lưu. Vui lòng nhấn "Lưu thay đổi" trước khi ${actionName}.`, 'warning');
        return false;
    }
    return true;
}

// --- Quản lý trạng thái cho CCCL ---
let hasUnsavedChangesCccl = false;
function markAsChangedCccl() { hasUnsavedChangesCccl = true; updateSaveButtonStateCccl(); }
function markAsSavedCccl() { hasUnsavedChangesCccl = false; updateSaveButtonStateCccl(); }
function updateSaveButtonStateCccl() {
    const saveBtn = $('#save-cccl-btn');
    if (hasUnsavedChangesCccl) {
        saveBtn.html('<i class="fas fa-save mr-2"></i>Lưu thay đổi •').removeClass('bg-teal-600').addClass('bg-orange-600');
    } else {
        saveBtn.html('<i class="fas fa-save mr-2"></i>Lưu thay đổi').removeClass('bg-orange-600').addClass('bg-teal-600');
    }
}
function checkUnsavedChangesCccl(actionName) {
    if (hasUnsavedChangesCccl) {
        App.showMessageModal(`Bạn có thay đổi chưa được lưu. Vui lòng nhấn "Lưu thay đổi" trước khi ${actionName}.`, 'warning');
        return false;
    }
    return true;
}

/**
 * =================================================================================
 * PHẦN 4: LOGIC LƯU DỮ LIỆU (BBGH VÀ CCCL)
 * =================================================================================
 */

/**
 * Lưu dữ liệu Biên bản giao hàng (BBGH) và trả về một Promise.
 * @returns {Promise<boolean>} Promise được giải quyết là true nếu thành công, và bị từ chối nếu thất bại.
 */
function saveBbgh() {
    return new Promise((resolve, reject) => {
        const btn = $('#save-bbgh-btn');
        const bbghId = new URLSearchParams(window.location.search).get('id');
        const payload = {
            bbgh_id: bbghId,
            tenCongTy: $('#bbgh-tencongty-khach').val(),
            diaChiKhach: $('#bbgh-diachi-khach').val(),
            nguoiNhanHang: $('#bbgh-nguoi-nhan').val(),
            sdtNhanHang: $('#bbgh-sdt-nguoi-nhan').val(),
            duAn: $('#bbgh-duan').val(),
            diaChiGiaoHang: $('#bbgh-diachi-giaohang').val(),
            nguoiGiaoHang: $('#bbgh-nguoi-lap-phieu').val(),
            sdtNguoiGiao: $('#bbgh-sdt-nguoi-lap').val(),
            sanPham: $('#bbgh-sanpham').val(),
            ngayGiao: $('#bbgh-ngay-giao').val(),
            items: $('#bbgh-items-body tr').map(function() {
                const id = $(this).find('.so-thung-input').data('id');
                if (id) return {
                    id: id,
                    soThung: $(this).find('.so-thung-input').val(),
                    ghiChu: $(this).find('.ghi-chu-input').val()
                };
            }).get()
        };
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...');

        $.ajax({
            url: 'api/update_bbgh.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    markAsSaved();
                    // Không hiển thị modal ở đây nữa, để hàm gọi tự quyết định
                    resolve(true);
                } else {
                    App.showMessageModal(`Lỗi khi lưu BBGH: ${response.message}`, 'error');
                    reject(new Error(response.message));
                }
            },
            error: (xhr) => {
                App.showMessageModal('Lỗi hệ thống, không thể kết nối đến server.', 'error');
                reject(new Error(xhr.responseText));
            },
            complete: () => {
                btn.prop('disabled', false);
                updateSaveButtonState();
            }
        });
    });
}

/**
 * Lưu dữ liệu Chứng chỉ chất lượng (CCCL) và trả về một Promise.
 * @returns {Promise<boolean>} Promise được giải quyết là true nếu thành công, và bị từ chối nếu thất bại.
 */
function saveCccl() {
    return new Promise((resolve, reject) => {
        const btn = $('#save-cccl-btn');
        const ccclId = new URLSearchParams(window.location.search).get('id');
        const payload = {
            cccl_id: ccclId,
            tenCongTy: $('#cccl-tencongty-khach').val(),
            diaChiKhach: $('#cccl-diachi-khach').val(),
            tenDuAn: $('#cccl-duan').val(),
            diaChiDuAn: $('#cccl-diachi-duan').val(),
            sanPham: $('#cccl-sanpham').val(),
            nguoiKiemTra: $('#cccl-footer-nguoikiemtra').val(),
            ngayCap: $('#cccl-ngay-cap').val(),
            items: $('#cccl-items-body tr').map(function() {
                const row = $(this);
                const id = row.find('input').first().data('id');
                if (id) return {
                    id: id,
                    tieuChuan: row.find('td:nth-child(6) input').val(),
                    ghiChu: row.find('td:nth-child(7) input').val()
                };
            }).get()
        };
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...');
        $.ajax({
            url: 'api/update_cccl.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    markAsSavedCccl();
                    // Không hiển thị modal ở đây nữa, để hàm gọi tự quyết định
                    resolve(true);
                } else {
                    App.showMessageModal(`Lỗi khi lưu CCCL: ${response.message}`, 'error');
                    reject(new Error(response.message));
                }
            },
            error: (xhr) => {
                App.showMessageModal('Lỗi hệ thống, không thể kết nối đến server.', 'error');
                reject(new Error(xhr.responseText));
            },
            complete: () => {
                btn.prop('disabled', false);
                updateSaveButtonStateCccl();
            }
        });
    });
}


/**
 * =================================================================================
 * PHẦN 5: HÀM CHUNG VÀ CÁC EVENT LISTENERS
 * =================================================================================
 */

/**
 * Hàm helper để format ngày hiển thị cho chức năng in.
 */
function formatDateForDisplay(dateValue) {
    if (!dateValue) return '';
    const date = new Date(dateValue);
    return `${date.getDate()} tháng ${String(date.getMonth() + 1).padStart(2, '0')} năm ${date.getFullYear()}`;
}

// --- EVENT DELEGATION (Tổng hợp) ---

// Theo dõi thay đổi trên các trường có thể chỉnh sửa
$(document).on('input change', '.editable-field, .editable-date', markAsChanged);
$(document).on('input change', '.editable-cccl-field', markAsChangedCccl);

// --- Sự kiện chung ---
$(document).on('click', '#back-to-issued-list-btn', function(e) {
    e.preventDefault();
    if (window.location.search.includes('bbgh_view') && !checkUnsavedChanges('quay lại')) return;
    if (window.location.search.includes('cccl_view') && !checkUnsavedChangesCccl('quay lại')) return;
    history.pushState({ page: 'xuatkho_issued_list' }, '', `?page=xuatkho_issued_list`);
    window.App.handleRouting();
});

// --- Sự kiện của BBGH ---
$(document).on('click', '.view-bbgh-btn', function () {
    const bbghId = $(this).data('bbgh-id');
    history.pushState({ page: 'bbgh_view', id: bbghId }, '', `?page=bbgh_view&id=${bbghId}`);
    window.App.handleRouting();
});
$(document).on('input', '#bbgh-nguoi-nhan', function() { $('#bbgh-footer-nguoinhan').val($(this).val()); });
$(document).on('input', '#bbgh-nguoi-lap-phieu', function() { $('#bbgh-footer-nguoigiao').val($(this).val()); });
$(document).on('click', '#print-btn', function() { if (checkUnsavedChanges('in biên bản')) window.print(); });

$(document).on('click', '#save-bbgh-btn', function() {
    saveBbgh()
        .then(() => App.showMessageModal('Cập nhật BBGH thành công!', 'success'))
        .catch(error => console.error("Lưu BBGH thất bại:", error.message));
});

$(document).on('click', '#export-excel-btn-bbgh', function() {
    const exportAction = () => {
        const bbghId = new URLSearchParams(window.location.search).get('id');
        if (bbghId > 0) window.location.href = `api/export_bbgh_excel.php?bbgh_id=${bbghId}`;
    };

    App.showMessageModal('Đang lưu dữ liệu và chuẩn bị xuất file...', 'info');
    saveBbgh().then(() => {
        App.showMessageModal('Lưu thành công. Bắt đầu tải file Excel.', 'success', 2000);
        exportAction();
    }).catch(error => {
        console.error("Không thể xuất Excel do lưu BBGH thất bại:", error.message);
    });
});

$(document).on('click', '#export-pdf-btn-bbgh', function() {
    const exportAction = () => {
        const bbghId = new URLSearchParams(window.location.search).get('id');
        if (bbghId > 0) window.open(`api/export_bbgh_pdf.php?bbgh_id=${bbghId}`, '_blank');
    };

    App.showMessageModal('Đang lưu dữ liệu và chuẩn bị xuất file...', 'info');
    saveBbgh().then(() => {
        App.showMessageModal('Lưu thành công. Mở file PDF trong tab mới.', 'success', 2000);
        exportAction();
    }).catch(error => {
        console.error("Không thể xuất PDF do lưu BBGH thất bại:", error.message);
    });
});


// --- Sự kiện của CCCL ---
$(document).on('click', '.view-cccl-btn', function() {
    const ccclId = $(this).data('cccl-id');
    history.pushState({ page: 'cccl_view', id: ccclId }, '', `?page=cccl_view&id=${ccclId}`);
    window.App.handleRouting();
});
$(document).on('click', '#print-cccl-btn', function() { if (checkUnsavedChangesCccl('in chứng chỉ')) window.print(); });

$(document).on('click', '#save-cccl-btn', function() {
    saveCccl()
        .then(() => App.showMessageModal('Cập nhật CCCL thành công!', 'success'))
        .catch(error => console.error("Lưu CCCL thất bại:", error.message));
});

$(document).on('click', '#export-excel-btn-cccl', function() {
    const exportAction = () => {
        const ccclId = new URLSearchParams(window.location.search).get('id');
        if (ccclId > 0) window.location.href = `api/export_cccl_excel.php?cccl_id=${ccclId}`;
    };

    App.showMessageModal('Đang lưu dữ liệu và chuẩn bị xuất file...', 'info');
    saveCccl().then(() => {
        App.showMessageModal('Lưu thành công. Bắt đầu tải file Excel.', 'success', 2000);
        exportAction();
    }).catch(error => {
        console.error("Không thể xuất Excel do lưu CCCL thất bại:", error.message);
    });
});

$(document).on('click', '#export-pdf-btn-cccl', function() {
    const exportAction = () => {
        const ccclId = new URLSearchParams(window.location.search).get('id');
        if (ccclId > 0) window.open(`api/export_cccl_pdf.php?cccl_id=${ccclId}`, '_blank');
    };

    App.showMessageModal('Đang lưu dữ liệu và chuẩn bị xuất file...', 'info');
    saveCccl().then(() => {
        App.showMessageModal('Lưu thành công. Mở file PDF trong tab mới.', 'success', 2000);
        exportAction();
    }).catch(error => {
        console.error("Không thể xuất PDF do lưu CCCL thất bại:", error.message);
    });
});


// --- Logic xử lý IN ẤN ---
window.addEventListener('beforeprint', () => {
    $('#printable-area .print-replacement').remove();
    $('#printable-area .editable-field, #printable-area .editable-date, #printable-area .editable-cccl-field').each(function() {
        const $input = $(this);
        
        if ($input.is(':visible')) {
            let value = $input.is('textarea') ? ($input.val() || '').replace(/\n/g, '<br>') : $input.val();
            if ($input.is('input[type="date"]')) {
                value = formatDateForDisplay($input.val());
            }
            
            const $span = $('<span></span>')
                .addClass('print-replacement')
                .attr('class', ($input.attr('class') || '').replace(/editable-\w+-field|editable-date/g, '').trim())
                .html(value);

            if ($input.is('#bbgh-nguoi-nhan, #bbgh-footer-nguoinhan')) {
                $span.css('font-weight', 'bold');
            }

            $input.hide().after($span);
        }
    });
});

window.addEventListener('afterprint', () => {
    location.reload();
});

