/**
 * MODULE QUẢN LÝ NHẬP KHO TỪ LỆNH SẢN XUẤT (LK)
 * CẬP NHẬT: Hiển thị cột Trạng thái Nhập kho mới.
 */

function initializeNhapKhoLKListPage(mainContentContainer) {
    const listBody = mainContentContainer.find('#nhapkho-lk-list-body');
    listBody.html('<tr><td colspan="5" class="p-4 text-center">Đang tải dữ liệu...</td></tr>');

    $.getJSON('api/get_lsx_for_receipt_lk.php', function(response) {
        if (response.success && response.data.length > 0) {
            listBody.empty();
            response.data.forEach(lsx => {
                const ngayTao = lsx.NgayTao ? new Date(lsx.NgayTao).toLocaleDateString('vi-VN') : 'N/A';
                
                let actionHtml = '';
                let statusNhapKhoHtml = '';
                let statusColor = 'text-gray-500';

                switch(lsx.TrangThaiNhapKho) {
                    case 'Đã nhập đủ':
                        statusColor = 'text-green-600';
                        actionHtml = `<span class="text-xs italic text-gray-400">Đã xong</span>`;
                        break;
                    case 'Đang nhập':
                        statusColor = 'text-blue-600';
                        actionHtml = `<button data-lsx-id="${lsx.LenhSX_ID}" class="create-receipt-lk-btn action-btn bg-blue-500 text-white px-3 py-1 rounded-md text-xs hover:bg-blue-600"><i class="fas fa-plus-circle mr-1"></i> Nhập tiếp</button>`;
                        break;
                    default: // 'Chưa nhập'
                        statusColor = 'text-red-600';
                        actionHtml = `<button data-lsx-id="${lsx.LenhSX_ID}" class="create-receipt-lk-btn action-btn bg-blue-500 text-white px-3 py-1 rounded-md text-xs hover:bg-blue-600"><i class="fas fa-plus-circle mr-1"></i> Tạo Phiếu Nhập</button>`;
                        break;
                }
                statusNhapKhoHtml = `<span class="font-semibold ${statusColor}">${lsx.TrangThaiNhapKho}</span>`;

                const row = `
                    <tr class="hover:bg-gray-50">
                        <td class="p-3 font-semibold text-blue-600">${lsx.SoLenhSX}</td>
                        <td class="p-3">${ngayTao}</td>
                        <td class="p-3"><span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full">${lsx.TrangThai}</span></td>
                        <td class="p-3">${statusNhapKhoHtml}</td>
                        <td class="p-3 text-center">${actionHtml}</td>
                    </tr>`;
                listBody.append(row);
            });
        } else if (response.success) {
            listBody.html('<tr><td colspan="5" class="p-4 text-center">Không có lệnh sản xuất (LK) nào chờ nhập kho.</td></tr>');
        } else {
            listBody.html(`<tr><td colspan="5" class="p-4 text-center text-red-500">Lỗi: ${response.message}</td></tr>`);
        }
    }).fail(function() {
        listBody.html('<tr><td colspan="5" class="p-4 text-center text-red-500">Không thể tải dữ liệu từ máy chủ.</td></tr>');
    });
}

// Các hàm còn lại (initializeNhapKhoLKCreatePage, event listeners) giữ nguyên không đổi
function initializeNhapKhoLKCreatePage(mainContentContainer) {
    const urlParams = new URLSearchParams(window.location.search);
    const lsxId = parseInt(urlParams.get('lsx_id')) || 0;
    const saveBtn = mainContentContainer.find('#save-nhapkho-lk-btn');
    const itemsBody = mainContentContainer.find('#nhapkho-lk-items-body');

    mainContentContainer.find('#back-to-lk-list-btn').off('click').on('click', () => history.back());

    if (!lsxId) {
        App.showMessageModal('ID Lệnh Sản Xuất không hợp lệ.', 'error');
        return;
    }

    $.getJSON(`api/get_lsx_details_for_receipt_lk.php?lsx_id=${lsxId}`, function(response) {
        if (response.success) {
            mainContentContainer.find('#info-ngaynhap-lk').text(`Ngày ${new Date().getDate()} tháng ${new Date().getMonth() + 1} năm ${new Date().getFullYear()}`);
            mainContentContainer.find('#info-lsx-lk').text(response.header.SoLenhSX);
            mainContentContainer.find('#info-nguoilap-lk').text(window.App.currentUser.fullName || 'N/A');
            
            itemsBody.empty();
            if (response.items && response.items.length > 0) {
                response.items.forEach((item, index) => {
                    const rowHtml = `
                        <tr class="product-row" data-variant-id="${item.variant_id}">
                            <td class="p-2 border text-center">${index + 1}</td>
                            <td class="p-2 border">${item.MaHang}</td>
                            <td class="p-2 border">${item.TenSanPham}</td>
                            <td class="p-2 border text-center">${item.DonViTinh || 'Cái'}</td>
                            <td class="p-2 border text-center so-luong-can-nhap">${item.SoLuongCanNhap}</td>
                            <td class="p-2 border"><input type="number" class="w-full p-1 text-center so-luong-thuc-nhap" value="${item.SoLuongCanNhap}" min="0"></td>
                            <td class="p-2 border"><input type="text" class="w-full p-1 ghi-chu"></td>
                        </tr>`;
                    itemsBody.append(rowHtml);
                });
            } else {
                itemsBody.html(`<tr><td colspan="7" class="p-4 text-center">Tất cả sản phẩm của lệnh sản xuất này đã được nhập kho.</td></tr>`);
                saveBtn.prop('disabled', true);
            }
        } else {
            App.showMessageModal(response.message || 'Không thể tải chi tiết Lệnh Sản Xuất.', 'error');
        }
    });

    saveBtn.off('click').on('click', function() {
        const itemsData = [];
        itemsBody.find('tr.product-row').each(function() {
            const row = $(this);
            const soLuongThucNhap = parseInt(row.find('.so-luong-thuc-nhap').val()) || 0;
            if (soLuongThucNhap > 0) {
                itemsData.push({
                    variant_id: row.data('variant-id'),
                    soLuongThucNhap: soLuongThucNhap,
                    ghiChu: row.find('.ghi-chu').val()
                });
            }
        });

        if (itemsData.length === 0) {
            App.showMessageModal('Vui lòng nhập số lượng thực nhập lớn hơn 0 cho ít nhất một sản phẩm.', 'warning');
            return;
        }

        App.showConfirmationModal('Xác Nhận Nhập Kho', 'Bạn chắc chắn muốn nhập kho các sản phẩm này?', function() {
            saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...');
            $.ajax({
                url: 'api/save_phieunhapkho_lk.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ 
                    lsx_id: lsxId, 
                    items: itemsData,
                    user_id: window.App.currentUser.id 
                }),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        App.showMessageModal('Nhập kho thành công!', 'success');
                        setTimeout(() => {
                            const url = `?page=nhapkho_lk`;
                            history.pushState({ page: 'nhapkho_lk' }, '', url);
                            window.App.handleRouting();
                        }, 1500);
                    } else {
                        App.showMessageModal(res.message, 'error');
                        saveBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Hoàn Tất Nhập Kho');
                    }
                },
                error: function() {
                    App.showMessageModal('Có lỗi xảy ra, không thể lưu phiếu nhập kho.', 'error');
                    saveBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Hoàn Tất Nhập Kho');
                }
            });
        });
    });
}

$(document).off('click', '.create-receipt-lk-btn').on('click', '.create-receipt-lk-btn', function() {
    const lsxId = $(this).data('lsx-id');
    const url = `?page=nhapkho_lk_create&lsx_id=${lsxId}`;
    history.pushState({ page: 'nhapkho_lk_create', lsx_id: lsxId }, '', url);
    window.App.handleRouting();
});
