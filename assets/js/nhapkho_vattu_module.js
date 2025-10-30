// File: public/js/nhapkho_vattu_module.js
// Module này quản lý toàn bộ logic cho chức năng Nhập kho Vật tư từ Nhà cung cấp.

/**
 * Khởi tạo trang danh sách phiếu nhập kho vật tư.
 * Tải và hiển thị danh sách các phiếu đã được tạo.
 */
function initializeNhapKhoVatTuListPage() {
    const listBody = $('#nhapkho-vattu-list-body');
    listBody.html('<tr><td colspan="6" class="p-4 text-center">Đang tải dữ liệu...</td></tr>');

    $.ajax({
        url: 'api/get_phieunhapkho_vattu.php', // API để lấy danh sách phiếu nhập vật tư
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data.length > 0) {
                listBody.empty();
                response.data.forEach(phieu => {
                    const row = `
                        <tr class="hover:bg-gray-50">
                            <td class="p-3 font-semibold text-blue-600">${phieu.SoPhieuNhapKho}</td>
                            <td class="p-3">${new Date(phieu.NgayNhap).toLocaleDateString('vi-VN')}</td>
                            <td class="p-3">${phieu.TenNhaCungCap || 'N/A'}</td>
                            <td class="p-3">${phieu.LyDoNhap || ''}</td>
                            <td class="p-3 text-right font-mono">${App.formatNumber(phieu.TongTien)}</td>
                            <td class="p-3 text-center">
                                <button class="view-edit-pnk-vattu-btn bg-gray-500 text-white px-3 py-1 rounded-md text-xs hover:bg-gray-600" data-pnk-id="${phieu.PhieuNhapKhoID}">
                                    <i class="fas fa-eye mr-1"></i> Xem/Sửa
                                </button>
                            </td>
                        </tr>
                    `;
                    listBody.append(row);
                });
            } else {
                listBody.html('<tr><td colspan="6" class="p-4 text-center">Chưa có phiếu nhập kho vật tư nào.</td></tr>');
            }
        },
        error: function() {
            listBody.html('<tr><td colspan="6" class="p-4 text-center text-red-500">Lỗi tải dữ liệu.</td></tr>');
        }
    });
}

/**
 * Khởi tạo trang tạo hoặc chỉnh sửa phiếu nhập kho vật tư.
 */
function initializeNhapKhoVatTuCreatePage() {
    const pnkId = $('#save-nhapkho-vattu-btn').data('pnk-id');
    const isEditMode = pnkId > 0;

    // --- CÁC HÀM TIỆN ÍCH NỘI BỘ ---

    // Cập nhật thành tiền cho một dòng sản phẩm
    const updateRowTotal = (row) => {
        const quantity = parseFloat(row.find('.quantity-vattu').val()) || 0;
        const price = parseFloat(row.find('.price-vattu').val()) || 0;
        const total = quantity * price;
        row.find('.total-row-vattu').text(App.formatNumber(total.toFixed(0)));
        updateGrandTotal();
    };

    // Cập nhật tổng tiền của toàn bộ phiếu
    const updateGrandTotal = () => {
        let grandTotal = 0;
        $('#nhapkho-vattu-items-body tr').each(function() {
            const quantity = parseFloat($(this).find('.quantity-vattu').val()) || 0;
            const price = parseFloat($(this).find('.price-vattu').val()) || 0;
            grandTotal += quantity * price;
        });
        $('#total-amount-vattu').text(App.formatNumber(grandTotal.toFixed(0)));
    };
    
    // Thêm một dòng sản phẩm mới vào bảng chi tiết
    const addProductRow = (product) => {
        // Kiểm tra xem sản phẩm đã tồn tại trong bảng chưa
        if ($(`#nhapkho-vattu-items-body tr[data-variant-id="${product.variant_id}"]`).length > 0) {
            App.showMessageModal('Vật tư này đã có trong phiếu.', 'info');
            return;
        }

        const newRow = `
            <tr data-variant-id="${product.variant_id}">
                <td class="p-2 border align-middle">${product.variant_sku}</td>
                <td class="p-2 border align-middle">${product.variant_name}</td>
                <td class="p-2 border align-middle"><input type="number" class="w-full p-1 text-center quantity-vattu" value="${product.quantity || 1}" min="1"></td>
                <td class="p-2 border align-middle"><input type="number" class="w-full p-1 text-right price-vattu" value="${product.price || 0}" min="0"></td>
                <td class="p-2 border text-right total-row-vattu font-mono align-middle">0</td>
                <td class="p-2 border text-center align-middle">
                    <button class="remove-row-vattu-btn text-red-500 hover:text-red-700"><i class="fas fa-trash-alt"></i></button>
                </td>
            </tr>`;
        $('#nhapkho-vattu-items-body').append(newRow);
        updateRowTotal($('#nhapkho-vattu-items-body tr:last-child'));
    };

    // --- TẢI DỮ LIỆU BAN ĐẦU ---

    // Tải danh sách nhà cung cấp và điền vào dropdown
    $.getJSON('api/get_suppliers.php', function(response) {
        if (response.success) {
            const supplierSelect = $('#nha-cung-cap');
            response.data.forEach(supplier => {
                supplierSelect.append(`<option value="${supplier.NhaCungCapID}">${supplier.TenNhaCungCap}</option>`);
            });
        }
    });

    // Nếu là chế độ sửa, tải dữ liệu của phiếu nhập kho đã có
    if (isEditMode) {
        $('#page-title-vattu').text('Chỉnh Sửa Phiếu Nhập Kho Vật Tư');
        $.getJSON(`api/get_phieunhapkho_vattu_details.php?pnk_id=${pnkId}`, function(response) {
            if (response.success) {
                const { header, items } = response.data;
                $('#nha-cung-cap').val(header.NhaCungCapID);
                $('#ngay-nhap').val(header.NgayNhap);
                $('#so-hoa-don').val(header.SoHoaDon);
                $('#nguoi-giao-hang').val(header.NguoiGiaoHang);
                $('#ly-do-nhap').val(header.LyDoNhap);

                items.forEach(item => {
                    addProductRow({
                        variant_id: item.SanPhamID,
                        variant_sku: item.MaHang,
                        variant_name: item.TenSanPham,
                        quantity: item.SoLuong,
                        price: item.DonGiaNhap
                    });
                });
            } else {
                App.showMessageModal(response.message, 'error');
            }
        });
    } else {
         // Nếu là tạo mới, tự động điền ngày hiện tại
         $('#ngay-nhap').val(new Date().toISOString().slice(0, 10));
          // [NEW] KIỂM TRA VÀ TỰ ĐỘNG ĐIỀN DỮ LIỆU TỪ TRANG CHUẨN BỊ HÀNG
        const pendingDataJson = sessionStorage.getItem('pendingEcuImportData');
        if (pendingDataJson) {
            try {
                const itemsToImport = JSON.parse(pendingDataJson);
                const urlParams = new URLSearchParams(window.location.search);
                const cbhId = urlParams.get('cbh_id');

                // Tự động điền lý do nhập
                if (cbhId) {
                    $('#ly-do-nhap').val(`Nhập vật tư còn thiếu cho Phiếu CBH ${cbhId}`);
                }

                // Thêm các dòng sản phẩm vào bảng
                itemsToImport.forEach(item => {
                    addProductRow(item);
                });
                
                // Cập nhật lại tổng tiền sau khi thêm
                updateGrandTotal();

            } catch (e) {
                console.error('Lỗi khi đọc dữ liệu vật tư từ sessionStorage:', e);
            } finally {
                // Xóa dữ liệu khỏi sessionStorage sau khi đã sử dụng để tránh lặp lại
                sessionStorage.removeItem('pendingEcuImportData');
            }
        }
    
    }

    // --- GÁN CÁC SỰ KIỆN (EVENT LISTENERS) ---

    $('#back-to-vattu-list-btn').on('click', () => history.back());

    // Mở/đóng modal tìm kiếm sản phẩm
    $('#add-product-vattu-btn').on('click', () => $('#search-product-modal-vattu').removeClass('hidden'));
    $('#close-search-modal-vattu').on('click', () => $('#search-product-modal-vattu').addClass('hidden'));

    // Xử lý tìm kiếm sản phẩm khi người dùng gõ
    $('#product-search-input-vattu').on('input', function() {
        const query = $(this).val();
        if (query.length < 2) {
            $('#product-search-results-vattu').empty();
            return;
        }
        $.getJSON(`api/search_products.php?q=${query}`, function(response) {
            const resultsContainer = $('#product-search-results-vattu');
            resultsContainer.empty();
            if (response.success && response.data.length > 0) {
                response.data.forEach(product => {
                    const resultItem = `
                        <div class="p-2 border-b hover:bg-gray-100 cursor-pointer select-product-vattu" data-product='${JSON.stringify(product)}'>
                            <strong>${product.variant_sku}</strong> - ${product.variant_name}
                        </div>`;
                    resultsContainer.append(resultItem);
                });
            } else {
                resultsContainer.html('<p class="p-2 text-gray-500">Không tìm thấy vật tư.</p>');
            }
        });
    });

    // Xử lý khi chọn một sản phẩm từ kết quả tìm kiếm
    $('#search-product-modal-vattu').on('click', '.select-product-vattu', function() {
        const product = $(this).data('product');
        addProductRow(product);
        $('#search-product-modal-vattu').addClass('hidden');
        $('#product-search-input-vattu').val('');
        $('#product-search-results-vattu').empty();
    });

    // Xóa một dòng sản phẩm và tính toán lại tổng
    $('#nhapkho-vattu-items-body').on('click', '.remove-row-vattu-btn', function() {
        $(this).closest('tr').remove();
        updateGrandTotal();
    });

    // Tự động tính toán lại khi thay đổi số lượng hoặc đơn giá
    $('#nhapkho-vattu-items-body').on('input', '.quantity-vattu, .price-vattu', function() {
        updateRowTotal($(this).closest('tr'));
    });

    // Xử lý lưu phiếu nhập (cả tạo mới và cập nhật)
    $('#save-nhapkho-vattu-btn').on('click', function() {
         const urlParams = new URLSearchParams(window.location.search);
    const cbhId = urlParams.get('cbh_id') || null;
        const payload = {
            pnk_id: pnkId,
        cbh_id: cbhId, // [NEW] Thêm cbh_id vào payload
            nha_cung_cap_id: $('#nha-cung-cap').val(),
            ngay_nhap: $('#ngay-nhap').val(),
            so_hoa_don: $('#so-hoa-don').val(),
            nguoi_giao_hang: $('#nguoi-giao-hang').val(),
            ly_do_nhap: $('#ly-do-nhap').val(),
            items: []
        };

        $('#nhapkho-vattu-items-body tr').each(function() {
            payload.items.push({
                variant_id: $(this).data('variant-id'),
                quantity: $(this).find('.quantity-vattu').val(),
                price: $(this).find('.price-vattu').val()
            });
        });

        if (!payload.nha_cung_cap_id || !payload.ngay_nhap || payload.items.length === 0) {
            App.showMessageModal('Vui lòng điền đầy đủ thông tin Nhà cung cấp, Ngày nhập và thêm ít nhất một vật tư.', 'error');
            return;
        }

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...');

        $.ajax({
            url: 'api/save_phieunhapkho_vattu.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    App.showMessageModal(response.message, 'success');
                    history.pushState({ page: 'nhapkho_vattu_list' }, '', '?page=nhapkho_vattu_list');
                    window.App.handleRouting();
                } else {
                    App.showMessageModal(response.message, 'error');
                    $('#save-nhapkho-vattu-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Lưu Phiếu Nhập');
                }
            },
            error: function() {
                App.showMessageModal('Lỗi hệ thống khi lưu phiếu.', 'error');
                $('#save-nhapkho-vattu-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Lưu Phiếu Nhập');
            }
        });
    });
}

// --- EVENT DELEGATION CHO CÁC NÚT BẤM TRÊN TRANG DANH SÁCH ---
// Sử dụng event delegation để các nút bấm vẫn hoạt động sau khi trang được tải lại bằng AJAX.

// Nút "Tạo Phiếu Nhập Mới"
$(document).on('click', '#create-new-pnk-vattu-btn', function() {
    history.pushState({ page: 'nhapkho_vattu_create' }, '', `?page=nhapkho_vattu_create`);
    window.App.handleRouting();
});

// Nút "Xem/Sửa" trong danh sách
$(document).on('click', '.view-edit-pnk-vattu-btn', function() {
    const pnkId = $(this).data('pnk-id');
    history.pushState({ page: 'nhapkho_vattu_create', pnk_id: pnkId }, '', `?page=nhapkho_vattu_create&pnk_id=${pnkId}`);
    window.App.handleRouting();
});
