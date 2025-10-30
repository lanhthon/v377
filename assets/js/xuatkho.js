// public/js/modules/xuatkho_module.js

/**
 * Khởi tạo trang danh sách các đơn hàng chờ xuất kho.
 * Danh sách này lấy từ các đơn hàng có trạng thái 'Chờ xuất kho'.
 * @param {jQuery} mainContentContainer - Container chính của nội dung trang.
 */
function initializeXuatKhoListPage(mainContentContainer) {
    const listBody = $('#xuatkho-list-body');

    function renderList(orders) {
        listBody.empty();
        if (!orders || orders.length === 0) {
            listBody.html('<tr><td colspan="5" class="text-center p-6 text-gray-500">Không có đơn hàng nào đang chờ xuất kho.</td></tr>');
            return;
        }

        orders.forEach(order => {
            const ngayTao = new Date(order.NgayTao).toLocaleDateString('vi-VN');
            const ngayGiao = order.NgayGiaoDuKien ? new Date(order.NgayGiaoDuKien).toLocaleDateString('vi-VN') : 'N/A';
            const row = `
                <tr class="hover:bg-gray-50">
                    <td class="p-3 border-b font-semibold text-blue-600">${order.SoYCSX}</td>
                    <td class="p-3 border-b">${order.TenCongTy}</td>
                    <td class="p-3 border-b">${ngayTao}</td>
                    <td class="p-3 border-b">${ngayGiao}</td>
                    <td class="p-3 border-b text-center">
                        <a href="#" data-page="xuatkho_create" data-id="${order.CBH_ID}" class="create-slip-btn bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600" title="Tạo phiếu xuất từ phiếu chuẩn bị hàng tương ứng">
                           <i class="fas fa-plus-circle mr-1"></i>Tạo Phiếu Xuất
                        </a>
                    </td>
                </tr>`;
            listBody.append(row);
        });
    }

    function loadData() {
        listBody.html('<tr><td colspan="5" class="text-center p-10"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i></td></tr>');
        // API endpoint mới, lấy danh sách dựa trên đơn hàng và phiếu CBH
        $.ajax({
            url: 'api/get_preparations_pending_issuance.php',
            dataType: 'json',
            success: (res) => {
                if (res.success) {
                    renderList(res.data);
                } else {
                    App.showMessageModal('Lỗi tải danh sách chờ xuất kho: ' + res.message, 'error');
                }
            },
            error: () => App.showMessageModal('Lỗi kết nối server khi tải danh sách chờ xuất kho.', 'error')
        });
    }

    // Gắn sự kiện click, đảm bảo hoạt động đúng
    mainContentContainer.off('click', '.create-slip-btn').on('click', '.create-slip-btn', function (e) {
        e.preventDefault();
        const cbhId = $(this).data('id'); // ID bây giờ là CBH_ID
        const page = $(this).data('page');

        if (page && cbhId) {
            history.pushState({ page: page, id: cbhId }, '', `?page=${page}&id=${cbhId}`);
            App.handleRouting();
        } else {
            console.error("Thiếu data-page hoặc data-id (CBH_ID) trên nút:", this);
            App.showMessageModal('Lỗi: Không tìm thấy ID của phiếu chuẩn bị hàng. Phiếu CBH có thể chưa được tạo cho đơn hàng này.', 'error');
        }
    });

    loadData();
}


/**
 * Khởi tạo trang tạo/xem phiếu xuất kho từ Phiếu Chuẩn Bị Hàng.
 * @param {jQuery} mainContentContainer
 */
function initializeXuatKhoCreatePage(mainContentContainer) {
    const params = new URLSearchParams(window.location.search);
    const cbh_id = params.get('id'); // ID bây giờ là CBH_ID
    const pxk_id = params.get('pxk_id');
    const viewMode = params.get('view') === 'true';
    const itemsBody = $('#xuatkho-items-body');
    const saveBtn = $('#save-xuatkho-btn');

    if (viewMode) {
        $('#page-title').text(`Chi Tiết Phiếu Xuất Kho`);
        saveBtn.addClass('hidden');
        $('#printable-area input, #printable-area textarea').prop('readonly', true).css({ 'border': 'none', 'background-color': 'transparent' });
    } else {
        $('#page-title').text(`Tạo Phiếu Xuất Kho`);
        saveBtn.removeClass('hidden');
        saveBtn.attr('data-cbh-id', cbh_id);
    }

    function generateSoPhieuXuat() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        const randomNum = Math.floor(1000 + Math.random() * 9000);
        return `PXK-${year}${month}${day}-${randomNum}`;
    }

    function renderDetails(data) {
        const info = data.chuan_bi_hang || data.phieu_xuat_kho;

        if (viewMode && info.SoPhieuXuat) {
            $('#page-title').text(`Chi Tiết PXK #${info.SoPhieuXuat}`);
        } else if (info.SoCBH) {
            $('#page-title').text(`Tạo PXK từ CBH #${info.SoCBH}`);
        }

        const today = new Date();
        $('#info-ngayxuat').text(`Ngày ${today.getDate()} tháng ${today.getMonth() + 1} năm ${today.getFullYear()}`);
        $('#info-tencongty').text(info.TenCongTy || info.NguoiNhan || 'N/A');
        $('#info-diachi').text(info.DiaDiemGiaoHang || info.DiaChiGiaoHang || 'N/A');
        $('#info-lydoxuat').text(info.LyDoXuat || `Xuất hàng theo YCSX số: ${info.SoYCSX || 'N/A'}`);

        let refText = [];
        if (info.SoYCSX) refText.push(`Đơn hàng: ${info.SoYCSX}`);
        if (info.SoCBH) refText.push(`CBH: ${info.SoCBH}`);
        $('#info-refs').text(refText.join(' / '));

        if (viewMode) {
            $('#info-sophieu').text(`Số: ${info.SoPhieuXuat || 'N/A'}`);
        } else {
            $('#info-sophieu').text(`Số: ${generateSoPhieuXuat()}`);
        }

        if (App.currentUser && App.currentUser.fullName) {
            $('#footer-nguoilap').text(App.currentUser.fullName);
        }

        itemsBody.empty();
        const mainItems = data.main_items || [];
        const extraItems = data.extra_items || [];

        if (mainItems.length === 0 && extraItems.length === 0) {
            itemsBody.html('<tr><td colspan="10" class="text-center p-6 text-gray-500">Không có sản phẩm trong phiếu chuẩn bị hàng.</td></tr>');
            return;
        }

        let stt = 1;
        // Render sản phẩm chính
        mainItems.forEach(item => {
            const slYeuCau = parseInt(item.SoLuong || 0, 10);
            const slThucXuat = viewMode ? parseInt(item.SoLuongThucXuat || 0, 10) : slYeuCau;
            const row = createRow(stt++, item, slYeuCau, slThucXuat, viewMode, false);
            itemsBody.append(row);
        });

        // Render vật tư đi kèm
        if (extraItems.length > 0) {
            const headerRow = `
                <tr class="bg-gray-200 font-bold">
                    <td colspan="10" class="p-2 text-left">Vật tư đi kèm</td>
                </tr>`;
            itemsBody.append(headerRow);

            extraItems.forEach(item => {
                const slYeuCau = parseInt(item.SoLuongEcu || 0, 10);
                const slThucXuat = viewMode ? parseInt(item.SoLuongThucXuat || 0, 10) : slYeuCau;
                const row = createRow(stt++, item, slYeuCau, slThucXuat, viewMode, true);
                itemsBody.append(row);
            });
        }
    }

    function createRow(stt, item, slYeuCau, slThucXuat, isViewMode, isExtra) {
        // Lấy thông tin sản phẩm tùy thuộc là vật tư hay sản phẩm chính
        const sanPhamID = item.SanPhamID || item.EcuVariantID; // Giả sử vật tư có ID riêng
        const maHang = item.MaHang || '';
        const tenSanPham = item.TenSanPham || item.TenSanPhamEcu;
        const taiSo = item.SoThung || item.SoKgEcu || '';

        return `
            <tr class="border border-black text-center" data-sanpham-id="${sanPhamID}" data-is-extra="${isExtra}">
                <td class="p-2 border border-black">${stt}</td>
                <td class="p-2 border border-black text-left">${maHang}</td>
                <td class="p-2 border border-black text-left">${tenSanPham}</td>
                <td class="p-2 border border-black">${item.ID_ThongSo || ''}</td>
                <td class="p-2 border border-black">${item.DoDay || ''}</td>
                <td class="p-2 border border-black">${item.BanRong || ''}</td>
                <td class="p-2 border border-black">
                    <input type="number" class="sl-yeu-cau w-full text-center bg-gray-100" value="${slYeuCau}" readonly>
                </td>
                <td class="p-2 border border-black">
                    <input type="number" class="sl-thuc-xuat form-input w-full text-center" value="${slThucXuat}" ${isViewMode ? 'readonly' : ''}>
                </td>
                <td class="p-2 border border-black">
                    <input type="text" class="tai-so-input form-input w-full text-center" value="${taiSo}" ${isViewMode ? 'readonly' : ''}>
                </td>
                <td class="p-2 border border-black">
                    <input type="text" class="ghi-chu-input form-input w-full text-left" value="${item.GhiChu || ''}" ${isViewMode ? 'readonly' : ''}>
                </td>
            </tr>`;
    }


    function loadData() {
        itemsBody.html('<tr><td colspan="10" class="text-center p-10"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i></td></tr>');
        let apiUrl = `api/xuatkho.php?action=get_xuatkho_details`;
        if (viewMode && pxk_id) {
            apiUrl += `&pxk_id=${pxk_id}`;
        } else if (cbh_id) {
            apiUrl += `&id=${cbh_id}`; // API sẽ nhận id là CBH_ID
        } else {
            App.showMessageModal('Không tìm thấy ID hợp lệ.', 'error');
            return;
        }

        $.ajax({
            url: apiUrl,
            dataType: 'json',
            success: (res) => {
                if (res.success) {
                    renderDetails(res.data);
                } else {
                    App.showMessageModal('Lỗi tải chi tiết: ' + res.message, 'error');
                }
            },
            error: (xhr) => App.showMessageModal('Lỗi kết nối hoặc xử lý dữ liệu: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'), 'error')
        });
    }

    $('#back-to-list-btn').on('click', function () {
        const targetPage = viewMode ? 'xuatkho_issued_list' : 'xuatkho_list';
        history.pushState({ page: targetPage }, '', `?page=${targetPage}`);
        App.handleRouting();
    });

    saveBtn.off('click').on('click', function (event) {
        event.preventDefault();

        const phieuXuatKhoData = {
            action: 'save_xuatkho',
            cbh_id: $(this).attr('data-cbh-id'),
            soPhieuXuat: $('#info-sophieu').text().replace('Số: ', '').trim(),
            ngayXuat: new Date().toISOString().slice(0, 10),
            nguoiNhan: $('#info-tencongty').text().trim(),
            diaChiGiaoHang: $('#info-diachi').text().trim(),
            ghiChu: $('#info-lydoxuat').text().trim(),
            items: []
        };

        let hasError = false;
        $('#xuatkho-items-body tr').each(function () {
            const row = $(this);
            // Bỏ qua các dòng tiêu đề nhóm
            if (row.find('td').length < 10) return;

            const slThucXuat = parseInt(row.find('.sl-thuc-xuat').val(), 10);

            if (isNaN(slThucXuat)) {
                App.showMessageModal('Số lượng thực xuất phải là một con số.', 'warning');
                hasError = true;
                return false;
            }

            const itemData = {
                sanPhamID: row.data('sanpham-id'),
                maHang: row.find('td:nth-child(2)').text(),
                tenSanPham: row.find('td:nth-child(3)').text(),
                soLuongYeuCau: parseInt(row.find('.sl-yeu-cau').val(), 10),
                soLuongThucXuat: slThucXuat,
                taiSo: row.find('.tai-so-input').val(),
                ghiChu: row.find('.ghi-chu-input').val()
            };
            phieuXuatKhoData.items.push(itemData);
        });

        if (hasError) return;

        if (phieuXuatKhoData.items.length === 0) {
            App.showMessageModal('Phiếu xuất kho phải có ít nhất một sản phẩm.', 'info');
            return;
        }

        $.ajax({
            url: 'api/xuatkho.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(phieuXuatKhoData),
            beforeSend: () => saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...'),
            success: function (response) {
                if (response.success) {
                    App.showMessageModal('Phiếu xuất kho đã được lưu thành công!', 'success');
                    history.pushState({ page: 'xuatkho_issued_list' }, '', '?page=xuatkho_issued_list');
                    App.handleRouting();
                } else {
                    App.showMessageModal('Lỗi khi lưu: ' + response.message, 'error');
                }
            },
            error: (xhr) => App.showMessageModal('Lỗi kết nối server: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'), 'error'),
            complete: () => saveBtn.prop('disabled', false).html('<i class="fas fa-check-circle mr-2"></i>Hoàn Tất Xuất Kho')
        });
    });

    loadData();
}


/**
 * Khởi tạo trang danh sách các phiếu đã xuất kho.
 * @param {jQuery} mainContentContainer
 */
function initializeXuatKhoIssuedListPage(mainContentContainer) {
    const listBody = $('#issued-slips-list-body');

    function renderList(slips) {
        listBody.empty();
        if (!slips || slips.length === 0) {
            listBody.html('<tr><td colspan="5" class="text-center p-6 text-gray-500">Chưa có phiếu xuất kho nào được phát hành.</td></tr>');
            return;
        }

        slips.forEach(slip => {
            const ngayXuat = new Date(slip.NgayXuat).toLocaleDateString('vi-VN');
            const row = `
                <tr class="hover:bg-gray-50">
                    <td class="p-3 border-b font-semibold text-green-600">${slip.SoPhieuXuat}</td>
                    <td class="p-3 border-b">${slip.SoYCSX || 'Phiếu lẻ'}</td>
                    <td class="p-3 border-b">${slip.TenCongTy || slip.NguoiNhan || 'N/A'}</td>
                    <td class="p-3 border-b">${ngayXuat}</td>
                    <td class="p-3 border-b text-center no-print">
                        <button type="button" class="view-slip-btn bg-green-500 text-white px-2 py-1 rounded text-xs hover:bg-green-600 mr-1" data-pxk-id="${slip.PhieuXuatKhoID}">
                           <i class="fas fa-eye mr-1"></i>Xem/In
                        </button>
                    </td>
                </tr>`;
            listBody.append(row);
        });
    }

    function loadData() {
        listBody.html('<tr><td colspan="5" class="text-center p-10"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i></td></tr>');
        $.ajax({
            url: 'api/get_issued_slips.php',
            dataType: 'json',
            success: (res) => {
                if (res.success) {
                    res.data.sort((a, b) => new Date(b.NgayXuat) - new Date(a.NgayXuat));
                    renderList(res.data);
                } else {
                    App.showMessageModal('Lỗi tải danh sách phiếu đã xuất: ' + res.message, 'error');
                }
            },
            error: () => App.showMessageModal('Lỗi kết nối server.', 'error')
        });
    }

    mainContentContainer.off('click', '.view-slip-btn').on('click', '.view-slip-btn', function () {
        const pxkId = $(this).data('pxk-id');
        const url = `?page=xuatkho_create&pxk_id=${pxkId}&view=true`;
        history.pushState({ page: 'xuatkho_create', pxk_id: pxkId, view: true }, '', url);
        App.handleRouting();
    });

    loadData();
}
