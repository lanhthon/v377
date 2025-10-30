// File: js/nhapkho_tp_ngoai.js
function initializeNhapKhoTPNgoaiPage() {
    const itemsBody = $('#nhapkho-tp-ngoai-items-body');
    const saveBtn = $('#save-nhapkho-tp-ngoai-btn');
    const searchModal = $('#search-product-modal-tp');
    const searchInput = $('#product-search-input-tp');
    const searchResults = $('#product-search-results-tp');
    
    const updateTotalQuantity = () => {
        let total = 0;
        itemsBody.find('.quantity-input').each(function() { total += parseFloat($(this).val()) || 0; });
        $('#total-quantity-xuat-btp').text(window.App.formatNumber(total)); // Sửa ID này nếu cần
    };

    const addProductRow = (product) => {
        if ($(`#nhapkho-tp-ngoai-items-body tr[data-variant-id="${product.variant_id}"]`).length > 0) {
            window.App.showMessageModal('Sản phẩm này đã có trong phiếu.', 'info'); return;
        }
        const newRow = `
            <tr data-variant-id="${product.variant_id}">
                <td class="p-2 border align-middle">${product.variant_sku}</td>
                <td class="p-2 border align-middle">${product.variant_name}</td>
                <td class="p-2 border align-middle"><input type="number" class="w-full p-1 text-center quantity-input" value="1" min="1"></td>
                <td class="p-2 border text-center align-middle"><button class="remove-row-tp-btn text-red-500"><i class="fas fa-trash-alt"></i></button></td>
            </tr>`;
        itemsBody.append(newRow);
        updateTotalQuantity();
    };
    
    $('#add-product-tp-btn').on('click', () => searchModal.removeClass('hidden').addClass('flex'));
    $('#close-search-modal-tp').on('click', () => searchModal.addClass('hidden').removeClass('flex'));

    searchInput.on('input', function() {
        const query = $(this).val();
        if (query.length < 2) { searchResults.empty(); return; }
        $.getJSON(`api/search_tp_variants.php?q=${query}`, function(response) {
            searchResults.empty();
            if (response.success && response.data.length > 0) {
                response.data.forEach(product => {
                    searchResults.append(`<div class="p-2 border-b hover:bg-gray-100 cursor-pointer select-product-tp" data-product='${JSON.stringify(product)}'><strong>${product.variant_sku}</strong> - ${product.variant_name}</div>`);
                });
            } else { searchResults.html('<p class="p-2 text-gray-500">Không tìm thấy.</p>'); }
        });
    });

    searchModal.on('click', '.select-product-tp', function() {
        addProductRow($(this).data('product'));
        searchModal.addClass('hidden').removeClass('flex');
        searchInput.val('');
        searchResults.empty();
    });

    itemsBody.on('click', '.remove-row-tp-btn', function() { $(this).closest('tr').remove(); updateTotalQuantity(); });
    itemsBody.on('input', '.quantity-input', updateTotalQuantity);

    saveBtn.on('click', function() {
        const payload = {
            ngayNhap: $('#ngay-nhap-tp').val(),
            nguoiGiao: $('#nguoi-giao-tp').val().trim(),
            lyDoNhap: $('#ly-do-nhap-tp').val().trim(),
            items: []
        };
        itemsBody.find('tr').each(function() {
            payload.items.push({ variant_id: $(this).data('variant-id'), soLuong: $(this).find('.quantity-input').val() });
        });

        if (!payload.ngayNhap || !payload.lyDoNhap || payload.items.length === 0) {
            window.App.showMessageModal('Vui lòng điền đủ Ngày nhập, Lý do và thêm ít nhất một sản phẩm.', 'error'); return;
        }

        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...');
        $.ajax({
            url: 'api/save_phieunhapkho_tp_ngoai.php', method: 'POST', contentType: 'application/json',
            data: JSON.stringify(payload), dataType: 'json',
            success: (res) => {
                if (res.success) {
                    window.App.showMessageModal(res.message, 'success');
                    setTimeout(() => {
                        history.pushState({ page: 'danhsach_pnk_tp' }, '', `?page=danhsach_pnk_tp`);
                        window.App.handleRouting();
                    }, 1500);
                } else { window.App.showMessageModal('Lỗi: ' + res.message, 'error'); }
            },
            error: () => window.App.showMessageModal('Lỗi kết nối.', 'error'),
            complete: () => saveBtn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Lưu Phiếu Nhập')
        });
    });
}