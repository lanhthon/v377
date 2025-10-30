/**
 * =================================================================================
 * MODULE QUẢN LÝ NHẬP KHO BTP TỪ NHÀ CUNG CẤP
 * PHIÊN BẢN HOÀN CHỈNH: Sử dụng pop-up tìm kiếm (lookup modal)
 * =================================================================================
 */

function initializeNhapKhoBTPNgoaiPage() {
    // DOM Elements
    const nhaCungCapSelect = $('#nha-cung-cap');
    const itemsBody = $('#nhapkho-btp-ngoai-items-body');
    const saveBtn = $('#save-nhapkho-btp-ngoai-btn');
    const searchModal = $('#search-product-modal-btp');
    const searchInput = $('#product-search-input-btp');
    const searchResults = $('#product-search-results-btp');
    
    // --- HÀM TIỆN ÍCH NỘI BỘ ---

    // Cập nhật thành tiền của một dòng
    const updateRowTotal = (row) => {
        const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
        const price = parseFloat(row.find('.price-input').val()) || 0;
        row.find('.line-total').text(window.App.formatNumber((quantity * price).toFixed(0)));
        updateGrandTotal();
    };

    // Cập nhật tổng tiền toàn phiếu
    const updateGrandTotal = () => {
        let grandTotal = 0;
        itemsBody.find('tr').each(function() {
            const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
            const price = parseFloat($(this).find('.price-input').val()) || 0;
            grandTotal += quantity * price;
        });
        $('#tong-tien-nhap').text(window.App.formatNumber(grandTotal.toFixed(0)));
    };

    // Thêm một dòng sản phẩm vào bảng
    const addProductRow = (product) => {
        // Kiểm tra sản phẩm đã tồn tại chưa
        if ($(`#nhapkho-btp-ngoai-items-body tr[data-variant-id="${product.variant_id}"]`).length > 0) {
            window.App.showMessageModal('BTP này đã có trong phiếu.', 'info');
            return;
        }
        const newRow = `
            <tr data-variant-id="${product.variant_id}">
                <td class="p-2 border align-middle">${product.variant_sku}</td>
                <td class="p-2 border align-middle">${product.variant_name}</td>
                <td class="p-2 border align-middle"><input type="number" class="w-full p-1 text-center quantity-input" value="1" min="1"></td>
                <td class="p-2 border align-middle"><input type="number" class="w-full p-1 text-right price-input" value="0" min="0"></td>
                <td class="p-2 border text-right total-row-vattu font-mono align-middle line-total">0</td>
                <td class="p-2 border text-center align-middle">
                    <button class="remove-row-btp-btn text-red-500 hover:text-red-700"><i class="fas fa-trash-alt"></i></button>
                </td>
            </tr>`;
        itemsBody.append(newRow);
        updateRowTotal(itemsBody.find('tr:last-child'));
    };

    // --- TẢI DỮ LIỆU BAN ĐẦU ---

    // Tải danh sách nhà cung cấp
    $.getJSON('api/get_suppliers.php', function(response) {
        if (response.success) {
            nhaCungCapSelect.empty().append('<option value="">Chọn nhà cung cấp...</option>');
            response.data.forEach(supplier => {
                nhaCungCapSelect.append(`<option value="${supplier.NhaCungCapID}">${supplier.TenNhaCungCap}</option>`);
            });
        }
    });

    // --- GÁN CÁC SỰ KIỆN (EVENT LISTENERS) ---
    
    // Mở/đóng modal tìm kiếm
    $('#add-product-btp-btn').on('click', () => searchModal.removeClass('hidden').addClass('flex'));
    $('#close-search-modal-btp').on('click', () => searchModal.addClass('hidden').removeClass('flex'));

    // Tìm kiếm khi gõ phím trong modal
    searchInput.on('input', function() {
        const query = $(this).val();
        if (query.length < 2) {
            searchResults.empty();
            return;
        }
        $.getJSON(`api/search_btp_variants.php?q=${query}`, function(response) {
            searchResults.empty();
            if (response.success && response.data.length > 0) {
                response.data.forEach(product => {
                    const resultItem = `
                        <div class="p-2 border-b hover:bg-gray-100 cursor-pointer select-product-btp" data-product='${JSON.stringify(product)}'>
                            <strong>${product.variant_sku}</strong> - ${product.variant_name}
                        </div>`;
                    searchResults.append(resultItem);
                });
            } else {
                searchResults.html('<p class="p-2 text-gray-500">Không tìm thấy BTP.</p>');
            }
        });
    });

    // Chọn sản phẩm từ kết quả tìm kiếm
    searchModal.on('click', '.select-product-btp', function() {
        const product = $(this).data('product');
        addProductRow(product);
        searchModal.addClass('hidden').removeClass('flex');
        searchInput.val('');
        searchResults.empty();
    });

    // Xóa dòng sản phẩm
    itemsBody.on('click', '.remove-row-btp-btn', function() {
        $(this).closest('tr').remove();
        updateGrandTotal();
    });

    // Cập nhật tổng tiền khi sửa số lượng/đơn giá
    itemsBody.on('input', '.quantity-input, .price-input', function() {
        updateRowTotal($(this).closest('tr'));
    });

    // Lưu phiếu nhập
    saveBtn.on('click', function() {
        const payload = {
            nhaCungCapID: nhaCungCapSelect.val(),
            ngayNhap: $('#ngay-nhap').val(),
            nguoiGiaoHang: $('#nguoi-giao-hang').val().trim(),
            ghiChuChung: $('#ghi-chu-chung').val().trim(),
            items: []
        };

        itemsBody.find('tr').each(function() {
            payload.items.push({
                variant_id: $(this).data('variant-id'),
                soLuong: $(this).find('.quantity-input').val(),
                donGia: $(this).find('.price-input').val(),
                ghiChu: '' // Ghi chú từng dòng không có trong UI này, để trống
            });
        });

        if (!payload.nhaCungCapID || !payload.ngayNhap || payload.items.length === 0) {
            window.App.showMessageModal('Vui lòng điền đủ NCC, Ngày nhập và thêm ít nhất một BTP.', 'error');
            return;
        }
        
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...');
        $.ajax({
            url: 'api/save_nhapkho_btp_ngoai.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    window.App.showMessageModal(res.message, 'success');
                    setTimeout(() => {
                        history.pushState({ page: 'danhsach_pnk_btp' }, '', `?page=danhsach_pnk_btp`);
                        window.App.handleRouting();
                    }, 1500);
                } else {
                    window.App.showMessageModal('Lỗi: ' + res.message, 'error');
                }
            },
            error: function() {
                window.App.showMessageModal('Lỗi kết nối tới máy chủ.', 'error');
            },
            complete: function() {
                 saveBtn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Lưu Phiếu Nhập');
            }
        });
    });
}