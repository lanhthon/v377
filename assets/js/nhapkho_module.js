// File: public/js/nhapkho_module.js

function initializeDanhSachNhapKhoPage(mainContentContainer) {
    const listBody = $('#nhapkho-list-body');

    function renderList(slips) {
        listBody.empty();
        if (!slips || slips.length === 0) {
            listBody.html('<tr><td colspan="6" class="text-center p-6 text-gray-500">Không có phiếu nhập kho nào.</td></tr>');
            return;
        }
        slips.forEach(slip => {
            const ngayNhap = new Date(slip.NgayNhap).toLocaleDateString('vi-VN');
            const row = `
                <tr class="hover:bg-gray-50">
                    <td class="p-3 border-b font-semibold text-blue-600">${slip.SoPhieuNhapKho}</td>
                    <td class="p-3 border-b">${ngayNhap}</td>
                    <td class="p-3 border-b">${slip.NhaCungCap || 'N/A'}</td>
                    <td class="p-3 border-b">${slip.NguoiGiaoHang || 'N/A'}</td>
                    <td class="p-3 border-b">${slip.LyDoNhap || 'N/A'}</td>
                    <td class="p-3 border-b text-center no-print">
                        <button class="view-edit-btn bg-green-500 text-white px-3 py-1 rounded text-xs hover:bg-green-600" data-pnk-id="${slip.PhieuNhapKhoID}">
                            <i class="fas fa-eye mr-1"></i>Xem/Sửa
                        </button>
                    </td>
                </tr>`;
            listBody.append(row);
        });
    }

    function loadData() {
        listBody.html('<tr><td colspan="6" class="text-center p-10"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i></td></tr>');
        $.ajax({
            url: 'api/get_received_slips.php',
            dataType: 'json',
            success: (res) => {
                if (res.success) {
                    renderList(res.data);
                } else {
                    App.showMessageModal('Lỗi tải danh sách: ' + res.message, 'error');
                }
            },
            error: () => App.showMessageModal('Lỗi kết nối server.', 'error')
        });
    }
    loadData();
}

function initializeNhapKhoCreatePage(mainContentContainer) {
    const pnkId = new URLSearchParams(window.location.search).get('pnk_id') ?? 0;
    const isViewMode = pnkId > 0;

    let supplierList = [];
    let currentActiveProductInput = null;
    let currentActiveSupplierInput = null;
    let filteredProducts = [];
    let productHighlightIndex = -1;

    const productSuggestionBox = $('#product-suggestion-box');
    const supplierSuggestionBox = $('#supplier-suggestion-box');

    if (productSuggestionBox.length === 0) $('body').append('<div id="product-suggestion-box" class="suggestion-box hidden"></div>');
    if (supplierSuggestionBox.length === 0) $('body').append('<div id="supplier-suggestion-box" class="suggestion-box hidden"></div>');

    function addProductRow(itemData = null) {
        const newRow = $(`
            <tr class="product-row border border-black">
                <td class="p-2 border border-black text-center stt"></td>
                <td class="p-2 border border-black relative">
                    <input type="text" class="w-full border p-1 rounded text-left product-code" placeholder="Gõ tìm sản phẩm...">
                    <input type="hidden" data-field="sanPhamID">
                </td>
                <td class="p-2 border border-black ten-san-pham"></td>
                <td class="p-2 border border-black text-center id-thong-so"></td>
                <td class="p-2 border border-black text-center do-day"></td>
                <td class="p-2 border border-black text-center ban-rong"></td>
                <td class="p-2 border border-black"><input type="number" class="w-full border p-1 rounded text-center so-luong-input" value="1" min="1"></td>
                <td class="p-2 border border-black"><input type="number" class="w-full border p-1 rounded text-center don-gia-input" value="0" min="0"></td>
                <td class="p-2 border border-black text-right thanh-tien-display">0</td>
                <td class="p-2 border border-black"><input type="text" class="w-full border p-1 rounded" data-field="ghiChu"></td>
                <td class="p-2 border border-black no-print text-center">
                    <button class="delete-row-btn text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `);
        $('#nhapkho-items-body').append(newRow);
        renumberRows();
        if (itemData) updateRowWithProductData(newRow, itemData, true);
    }

    function renumberRows() {
        $('#nhapkho-items-body .product-row').each((index, row) => $(row).find('.stt').text(index + 1));
        calculateTotalAmount();
    }

    function calculateTotalAmount() {
        let total = 0;
        $('#nhapkho-items-body .product-row').each(function () {
            const row = $(this);
            const soLuong = parseFloat(row.find('.so-luong-input').val()) || 0;
            const donGia = parseFloat(row.find('.don-gia-input').val()) || 0;
            const thanhTien = soLuong * donGia;
            row.find('.thanh-tien-display').text(App.formatNumber(thanhTien));
            total += thanhTien;
        });
        $('#tong-tien-pnk').text(App.formatNumber(total));
    }

    function updateRowWithProductData(row, product, isFromLoad = false) {
        let isDuplicate = false;
        $('#nhapkho-items-body .product-row').not(row).each(function () {
            if ($(this).find('[data-field="sanPhamID"]').val() == product.productId) {
                isDuplicate = true;
            }
        });
        if (isDuplicate) {
            App.showMessageModal('Sản phẩm này đã có trong phiếu nhập.', 'warning');
            row.find('.product-code').val('').focus();
            return;
        }

        row.find('[data-field="sanPhamID"]').val(product.productId);
        row.find('.product-code').val(product.code).prop('readonly', true);
        row.find('.ten-san-pham').text(product.name);
        row.find('.id-thong-so').text(product.id_thongso || '');
        row.find('.do-day').text(product.thickness || '');
        row.find('.ban-rong').text(product.width || '');

        if (isFromLoad) {
            row.find('.so-luong-input').val(product.SoLuong);
            row.find('.don-gia-input').val(product.DonGiaNhap);
            row.find('[data-field="ghiChu"]').val(product.GhiChu);
        } else {
            row.find('.don-gia-input').val(product.price.p0 || 0);
            row.find('.so-luong-input').focus().select();
        }
        calculateTotalAmount();
    }

    function showProductSuggestions(input) {
        const query = input.val().toLowerCase();
        if (query.length < 2) {
            productSuggestionBox.hide();
            return;
        }
        filteredProducts = window.App.productList.filter(p => (p.code && p.code.toLowerCase().includes(query)) || (p.name && p.name.toLowerCase().includes(query)));
        if (!filteredProducts.length) {
            productSuggestionBox.hide();
            return;
        }
        const list = filteredProducts.slice(0, 12).map((p, index) => {
            const fKeyHint = `<span class="f-key-hint">F${index + 1}</span>`;
            return `<li data-index="${index}"><div><b>${p.code}</b> - ${p.name}</div>${fKeyHint}</li>`;
        }).join('');
        productSuggestionBox.html(`<ul>${list}</ul>`).show();
        const pos = input.offset();
        productSuggestionBox.css({
            top: pos.top + input.outerHeight(),
            left: pos.left,
            width: '450px'
        });
        productHighlightIndex = -1;
    }

    function highlightProductSuggestion(index) {
        productSuggestionBox.find('li').removeClass('highlighted');
        productSuggestionBox.find(`li[data-index="${index}"]`).addClass('highlighted');
    }

    function showSupplierSuggestions(input) {
        // ... (Giữ nguyên logic tìm nhà cung cấp)
    }

    function loadInitialData() {
        $.get('api/get_suppliers.php', (res) => {
            if (res.success) supplierList = res.data;
        });

        if (isViewMode) {
            $('#page-title').text('Xem/Sửa Phiếu Nhập Kho');
            $.get(`api/nhapkho.php?action=get_nhapkho_details&pnk_id=${pnkId}`, (res) => {
                if (res.success) {
                    const pnk = res.data.phieu_nhap_kho;
                    const items = res.data.items;
                    $('#info-ngaynhap').text(`Ngày ${new Date(pnk.NgayNhap).toLocaleDateString('vi-VN')}`);
                    $('#info-sophieu').text(`Số: ${pnk.SoPhieuNhapKho}`);
                    $('#input-nhacungcap').val(pnk.TenNhaCungCap).data('ncc-id', pnk.NhaCungCapID);
                    $('#input-nguoigiaohang').val(pnk.NguoiGiaoHang);
                    $('#input-sohoadon').val(pnk.SoHoaDon);
                    $('#input-lydonhap').val(pnk.LyDoNhap);
                    $('#input-ghichu').val(pnk.GhiChu);
                    $('#footer-nguoilap').text(pnk.NguoiLapPhieu || App.currentUser.fullName);
                    $('#nhapkho-items-body').empty();
                    items.forEach(item => addProductRow(item));
                }
            });
        } else {
            $('#page-title').text('Tạo Phiếu Nhập Kho Mới');
            const today = new Date();
            $('#info-ngaynhap').text(`Ngày ${today.toLocaleDateString('vi-VN')}`);
            // Note: Using a fixed date like 2025-07-28 for consistency.
            // Replace with `new Date()` for real-time applications.
            const fixedDate = new Date('2025-07-28T07:15:33+07:00');
            $('#info-sophieu').text(`Số: PNK-${fixedDate.getTime().toString().slice(-6)}`);
            $('#footer-nguoilap').text(App.currentUser.fullName);
            addProductRow();
        }
    }
    loadInitialData();

    // --- Gắn sự kiện ---
    mainContentContainer.off('.nhapKhoCreate'); // Xóa event handler cũ

    $('#add-empty-row-btn').on('click.nhapKhoCreate', () => addProductRow());
    mainContentContainer.on('click.nhapKhoCreate', '.delete-row-btn', function () {
        $(this).closest('tr').remove();
        renumberRows();
    });
    mainContentContainer.on('input.nhapKhoCreate', '.so-luong-input, .don-gia-input', calculateTotalAmount);

    mainContentContainer.on('focus.nhapKhoCreate', '.product-code', function () {
        currentActiveProductInput = $(this);
    });
    mainContentContainer.on('keyup.nhapKhoCreate', '.product-code', function (e) {
        if (![13, 27, 38, 40].includes(e.keyCode) && !(e.keyCode >= 112 && e.keyCode <= 123)) {
            showProductSuggestions($(this));
        }
    });
    mainContentContainer.on('keydown.nhapKhoCreate', '.product-code', function (e) {
        if (!productSuggestionBox.is(':visible')) return;
        if (e.keyCode === 40) { // Down arrow
            e.preventDefault();
            productHighlightIndex = (productHighlightIndex + 1) % filteredProducts.slice(0, 12).length;
            highlightProductSuggestion(productHighlightIndex);
        } else if (e.keyCode === 38) { // Up arrow
            e.preventDefault();
            productHighlightIndex = (productHighlightIndex - 1 + filteredProducts.slice(0, 12).length) % filteredProducts.slice(0, 12).length;
            highlightProductSuggestion(productHighlightIndex);
        } else if (e.keyCode === 13) { // Enter
            e.preventDefault();
            if (productHighlightIndex !== -1) {
                const selectedProduct = filteredProducts[productHighlightIndex];
                updateRowWithProductData($(this).closest('tr'), selectedProduct);
                productSuggestionBox.hide();
            }
        } else if (e.keyCode >= 112 && e.keyCode <= 123) { // F1-F12
            e.preventDefault();
            const selectedIndex = e.keyCode - 112;
            if (filteredProducts[selectedIndex]) {
                updateRowWithProductData($(this).closest('tr'), filteredProducts[selectedIndex]);
                productSuggestionBox.hide();
            }
        } else if (e.keyCode === 27) { // Escape
            e.preventDefault();
            productSuggestionBox.hide();
        }
    });
    productSuggestionBox.on('click.nhapKhoCreate', 'li', function () {
        const product = filteredProducts[$(this).data('index')];
        updateRowWithProductData(currentActiveProductInput.closest('tr'), product);
        productSuggestionBox.hide();
    });

    // Sự kiện cho tìm kiếm nhà cung cấp (giữ nguyên)
    // ...

    // =================================================================
    // === LOGIC LƯU PHIẾU (ĐÃ CẬP NHẬT) ===
    // =================================================================
    $('#save-nhapkho-btn').on('click.nhapKhoCreate', function () {
        const payload = {
            PhieuNhapKhoID: pnkId,
            SoPhieuNhapKho: $('#info-sophieu').text().replace('Số: ', ''),
            NgayNhap: new Date().toISOString().slice(0, 10),
            NhaCungCapID: $('#input-nhacungcap').data('ncc-id') || null,
            NguoiGiaoHang: $('#input-nguoigiaohang').val(),
            SoHoaDon: $('#input-sohoadon').val(),
            LyDoNhap: $('#input-lydonhap').val(),
            GhiChuPNK: $('#input-ghichu').val(),
            items: []
        };

        // --- Bắt đầu logic kiểm tra dữ liệu ---

        // 1. Kiểm tra các dòng sản phẩm
        let hasAttemptedToAddItem = false;
        let hasInvalidItem = false;

        $('#nhapkho-items-body .product-row').css('background-color', '');

        $('#nhapkho-items-body .product-row').each(function () {
            const row = $(this);
            const productCode = row.find('.product-code').val();
            const soLuong = row.find('.so-luong-input').val();
            const sanPhamID = row.find('[data-field="sanPhamID"]').val();

            // Bỏ qua dòng trống hoàn toàn
            if (!productCode && (!soLuong || soLuong <= 0)) {
                return;
            }

            hasAttemptedToAddItem = true;

            if (!sanPhamID || !soLuong || soLuong <= 0) {
                hasInvalidItem = true;
                row.css('background-color', '#fee2e2');
            } else {
                payload.items.push({
                    SanPhamID: sanPhamID,
                    SoLuong: soLuong,
                    DonGiaNhap: row.find('.don-gia-input').val(),
                    GhiChu: row.find('[data-field="ghiChu"]').val()
                });
            }
        });

        if (hasInvalidItem) {
            setTimeout(() => {
                $('#nhapkho-items-body .product-row').css('background-color', '');
            }, 3500);
        }

        if (hasInvalidItem) {
            App.showMessageModal('Một hoặc nhiều dòng sản phẩm không hợp lệ. Vui lòng chọn sản phẩm từ danh sách và nhập số lượng lớn hơn 0.', 'error');
            return;
        }

        if (!hasAttemptedToAddItem || payload.items.length === 0) {
            App.showMessageModal('Vui lòng thêm ít nhất một sản phẩm hợp lệ vào phiếu.', 'error');
            return;
        }

        // --- Kết thúc logic kiểm tra dữ liệu ---

        // Gửi dữ liệu lên server
        $.ajax({
            url: 'api/nhapkho.php?action=save_nhapkho',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: (res) => {
                if (res.success) {
                    App.showMessageModal(res.message, 'success');
                    history.pushState({ page: 'danhsach_nhapkho' }, '', '?page=danhsach_nhapkho');
                    window.App.handleRouting();
                } else {
                    App.showMessageModal('Lỗi: ' + res.message, 'error');
                }
            },
            error: () => App.showMessageModal('Lỗi kết nối server.', 'error')
        });
    });
    // =================================================================
    // === KẾT THÚC PHẦN LOGIC LƯU PHIẾU ===
    // =================================================================
}