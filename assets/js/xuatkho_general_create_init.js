function initializeXuatKhoGeneralCreatePage(mainContentContainer) {
    const pxkId = new URLSearchParams(window.location.search).get('pxk_id') ?? 0;
    let isEditMode = pxkId > 0;

    let productList = window.App.productList || []; // Get global product list
    let currentActiveProductInput = null;
    let filteredProducts = [];
    let productHighlightIndex = -1;
    const productSuggestionBox = $('#product-suggestion-box');

    function getCurrentDate() {
        const today = new Date();
        const day = String(today.getDate()).padStart(2, '0');
        const month = String(today.getMonth() + 1).padStart(2, '0'); // January is 0!
        const year = today.getFullYear();
        return `${day}/${month}/${year}`;
    }

    function generateSoPhieuXuat() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        const randomNum = Math.floor(10000 + Math.random() * 90000); // 5-digit random number
        return `PXK-GEN-${year}${month}${day}-${randomNum}`;
    }

    // Function to add a new empty product row
    function addProductRow(productData = null) {
        const newRowHtml = `
            <tr class="product-row border border-black">
                <td class="p-2 border border-black text-center stt"></td>
                <td class="p-2 border border-black">
                    <input type="text" class="w-full border p-1 rounded text-center product-code" placeholder="Gõ tìm sản phẩm..." data-field="maHang">
                    <input type="hidden" data-field="sanPhamID">
                </td>
                <td class="p-2 border border-black">
                    <input type="text" class="w-full border-none p-0 m-0 bg-transparent" readonly data-field="tenSanPham">
                </td>
                <td class="p-2 border border-black">
                    <input type="text" class="w-full border-none p-0 m-0 bg-transparent text-center" readonly data-field="ID_ThongSo">
                </td>
                <td class="p-2 border border-black">
                    <input type="text" class="w-full border-none p-0 m-0 bg-transparent text-center" readonly data-field="DoDay">
                </td>
                <td class="p-2 border border-black">
                    <input type="text" class="w-full border-none p-0 m-0 bg-transparent text-center" readonly data-field="BanRong">
                </td>
                <td class="p-2 border border-black">
                    <input type="number" class="w-full border p-1 rounded text-center" value="0" data-field="soLuongYeuCau">
                </td>
                <td class="p-2 border border-black">
                    <input type="number" class="w-full border p-1 rounded text-center" value="0" data-field="soLuongThucXuat">
                </td>
                <td class="p-2 border border-black">
                    <input type="text" class="w-full border p-1 rounded" data-field="taiSo">
                </td>
                <td class="p-2 border border-black">
                    <input type="text" class="w-full border p-1 rounded" data-field="ghiChu">
                </td>
                <td class="p-2 border border-black no-print text-center">
                    <button class="delete-row-btn text-red-500 hover:text-red-700">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        const newRow = $(newRowHtml);
        $('#xuatkho-items-body').append(newRow);
        renumberRows();

        if (productData) {
            updateRowWithProductData(newRow, productData);
        }
    }

    // Function to renumber rows
    function renumberRows() {
        $('#xuatkho-items-body .product-row').each(function (index) {
            $(this).find('.stt').text(index + 1);
        });
    }

    // Function to load data for existing general PXK
    function loadExistingXuatKhoData() {
        $.ajax({
            url: `api/xuatkho.php?action=get_xuatkho_details&pxk_id=${pxkId}`,
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success && response.data.phieu_xuat_kho) {
                    const pxk = response.data.phieu_xuat_kho;
                    $('#page-title').text('Sửa Phiếu Xuất Kho Chung');
                    $('#info-ngayxuat').text(`Ngày ${new Date(pxk.NgayXuat).getDate()} tháng ${new Date(pxk.NgayXuat).getMonth() + 1} năm ${new Date(pxk.NgayXuat).getFullYear()}`);
                    $('#info-sophieu').text(`Số: ${pxk.SoPhieuXuat}`);
                    $('#input-nguoinhan').val(pxk.NguoiNhan);
                    $('#input-diachi').val(pxk.DiaChiGiaoHang || '');
                    $('#input-lydoxuat').val(pxk.GhiChu || '');
                    $('#footer-nguoilap').text(pxk.NguoiLapPhieu || 'N/A');
                    $('#save-xuatkho-btn').data('pxk-id', pxk.PhieuXuatKhoID);

                    $('#xuatkho-items-body').empty(); // Clear existing rows
                    response.data.items.forEach(item => {
                        const newRowHtml = `
                            <tr class="product-row border border-black">
                                <td class="p-2 border border-black text-center stt"></td>
                                <td class="p-2 border border-black">
                                    <input type="text" class="w-full border p-1 rounded text-center product-code" value="${item.MaHang}" readonly data-field="maHang">
                                    <input type="hidden" value="${item.SanPhamID}" data-field="sanPhamID">
                                </td>
                                <td class="p-2 border border-black">
                                    <input type="text" class="w-full border-none p-0 m-0 bg-transparent" value="${item.TenSanPham}" readonly data-field="tenSanPham">
                                </td>
                                <td class="p-2 border border-black">
                                    <input type="text" class="w-full border-none p-0 m-0 bg-transparent text-center" value="${item.ID_ThongSo || ''}" readonly data-field="ID_ThongSo">
                                </td>
                                <td class="p-2 border border-black">
                                    <input type="text" class="w-full border-none p-0 m-0 bg-transparent text-center" value="${item.DoDay || ''}" readonly data-field="DoDay">
                                </td>
                                <td class="p-2 border border-black">
                                    <input type="text" class="w-full border-none p-0 m-0 bg-transparent text-center" value="${item.BanRong || ''}" readonly data-field="BanRong">
                                </td>
                                <td class="p-2 border border-black">
                                    <input type="number" class="w-full border p-1 rounded text-center" value="${item.SoLuongYeuCau || 0}" data-field="soLuongYeuCau">
                                </td>
                                <td class="p-2 border border-black">
                                    <input type="number" class="w-full border p-1 rounded text-center" value="${item.SoLuongThucXuat || 0}" data-field="soLuongThucXuat">
                                </td>
                                <td class="p-2 border border-black">
                                    <input type="text" class="w-full border p-1 rounded" value="${item.TaiSo || ''}" data-field="taiSo">
                                </td>
                                <td class="p-2 border border-black">
                                    <input type="text" class="w-full border p-1 rounded" value="${item.GhiChu || ''}" data-field="ghiChu">
                                </td>
                                <td class="p-2 border border-black no-print text-center">
                                    <button class="delete-row-btn text-red-500 hover:text-red-700">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                        const newRow = $(newRowHtml);
                        newRow.data('product-data', App.productList.find(p => p.productId == item.SanPhamID)); // Attach full product data
                        $('#xuatkho-items-body').append(newRow);
                    });
                    renumberRows();
                } else {
                    App.showMessageModal('Lỗi tải dữ liệu phiếu xuất kho: ' + response.message, 'error');
                    // Fallback to new form if not found
                    initializeNewForm();
                }
            },
            error: function (xhr) {
                console.error("AJAX Error loading existing PXK: ", xhr.responseText);
                App.showMessageModal('Lỗi kết nối khi tải phiếu xuất kho. Vui lòng thử lại.', 'error');
                // Fallback to new form on error
                initializeNewForm();
            }
        });
    }

    // Function to initialize a new form
    function initializeNewForm() {
        $('#page-title').text('Tạo Phiếu Xuất Kho Ngoài Đơn Hàng');
        $('#info-ngayxuat').text(`Ngày ${getCurrentDate().split('/')[0]} tháng ${getCurrentDate().split('/')[1]} năm ${getCurrentDate().split('/')[2]}`);
        $('#info-sophieu').text(`Số: ${generateSoPhieuXuat()}`);
        $('#input-nguoinhan').val('');
        $('#input-diachi').val('');
        $('#input-lydoxuat').val('');
        $('#footer-nguoilap').text(App.currentUser.fullName || 'N/A');
        $('#xuatkho-items-body').empty(); // Clear any pre-existing rows
        addProductRow(); // Add one empty row for input
    }

    // Load initial data (either existing PXK or a new form)
    if (isEditMode) {
        loadExistingXuatKhoData();
    } else {
        initializeNewForm();
    }

    // Event listener for adding a new product row
    mainContentContainer.on('click', '#add-empty-row-btn', function () {
        addProductRow();
        $('#xuatkho-items-body .product-row:last-child .product-code').focus(); // Focus on new row's product code
    });

    // Event listener for deleting a product row
    mainContentContainer.on('click', '.delete-row-btn', function () {
        $(this).closest('.product-row').remove();
        renumberRows();
        if ($('#xuatkho-items-body .product-row').length === 0) {
            addProductRow(); // Add back one empty row if all are deleted
        }
    });

    // Handle product search suggestions
    mainContentContainer.on('focus', '.product-code', function () {
        currentActiveProductInput = $(this);
        // Ensure suggestion box is appended to body to handle z-index correctly
        if (productSuggestionBox.parent().length === 0) {
            $('body').append(productSuggestionBox);
        }
    });

    mainContentContainer.on('keyup', '.product-code', function (e) {
        if (![13, 27, 38, 40].includes(e.keyCode) && !(e.keyCode >= 112 && e.keyCode <= 123)) {
            showProductSuggestions($(this));
        }
    });

    mainContentContainer.on('keydown', '.product-code', function (e) {
        if (!productSuggestionBox.is(':visible')) return;

        if (e.keyCode === 40) { // Down arrow
            e.preventDefault();
            productHighlightIndex = (productHighlightIndex + 1) % filteredProducts.length;
            highlightProductSuggestion(productHighlightIndex);
        } else if (e.keyCode === 38) { // Up arrow
            e.preventDefault();
            productHighlightIndex = (productHighlightIndex - 1 + filteredProducts.length) % filteredProducts.length;
            highlightProductSuggestion(productHighlightIndex);
        } else if (e.keyCode === 13) { // Enter
            e.preventDefault();
            if (productHighlightIndex !== -1) {
                const selectedProduct = filteredProducts[productHighlightIndex];
                updateRowWithProductData($(this).closest('.product-row'), selectedProduct);
                hideProductSuggestions();
                $(this).closest('.product-row').find('[data-field="soLuongThucXuat"]').focus().select();
            }
        } else if (e.keyCode >= 112 && e.keyCode <= 123) { // F1-F12 keys
            e.preventDefault();
            const selectedIndex = e.keyCode - 112;
            if (filteredProducts[selectedIndex]) {
                updateRowWithProductData($(this).closest('.product-row'), filteredProducts[selectedIndex]);
                hideProductSuggestions();
                $(this).closest('.product-row').find('[data-field="soLuongThucXuat"]').focus().select();
            }
        } else if (e.keyCode === 27) { // Escape
            e.preventDefault();
            hideProductSuggestions();
        }
    });

    productSuggestionBox.on('click', 'li', function () {
        const product = filteredProducts[$(this).data('index')];
        if (product && currentActiveProductInput) {
            updateRowWithProductData(currentActiveProductInput.closest('.product-row'), product);
            hideProductSuggestions();
            currentActiveProductInput.closest('.product-row').find('[data-field="soLuongThucXuat"]').focus().select();
        }
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.product-code, #product-suggestion-box').length) {
            hideProductSuggestions();
        }
    });


    function showProductSuggestions(input) {
        const query = input.val().toLowerCase();
        if (query.length < 2) {
            hideProductSuggestions();
            return;
        }
        const terms = query.split(' ').filter(t => t.length > 0);
        filteredProducts = productList.filter(p => {
            const searchString = `${p.code.toLowerCase()} ${p.name ? p.name.toLowerCase() : ''}`;
            return terms.every(term => searchString.includes(term));
        });

        if (filteredProducts.length === 0) {
            hideProductSuggestions();
            return;
        }

        // Limit to top 12 results for F-key hints
        const list = filteredProducts.slice(0, 12).map((p, index) => {
            const fKeyHint = `<span class="f-key-hint">F${index + 1}</span>`;
            return `<li data-index="${index}" class="p-2 hover:bg-blue-100 cursor-pointer flex justify-between items-center"><div><b>${p.code}</b> <small>${p.name || ''}</small></div>${fKeyHint}</li>`;
        }).join('');

        productSuggestionBox.html(`<ul class="list-none m-0 p-0">${list}</ul>`).show();
        const inputPos = input.offset();
        productSuggestionBox.css({
            top: inputPos.top + input.outerHeight(),
            left: inputPos.left,
            width: Math.max(input.outerWidth(), 450)
        });
        productHighlightIndex = -1; // Reset highlight
    }

    function hideProductSuggestions() {
        productSuggestionBox.hide().empty();
    }

    function highlightProductSuggestion(index) {
        productSuggestionBox.find('li').removeClass('bg-blue-200');
        productSuggestionBox.find(`li[data-index="${index}"]`).addClass('bg-blue-200');
    }

    function updateRowWithProductData(row, productData) {
        let isDuplicate = false;
        const newProductId = productData.productId;

        // Check for duplicates in other rows
        $('#xuatkho-items-body .product-row').not(row).each(function () {
            const existingRowProduct = $(this).data('product-data');
            if (existingRowProduct && existingRowProduct.productId === newProductId) {
                isDuplicate = true;
                return false; // Exit .each loop
            }
        });

        if (isDuplicate) {
            App.showMessageModal(`Sản phẩm "${productData.code}" đã có trong phiếu xuất kho.`, 'info');
            row.find('.product-code').val('').focus();
            hideProductSuggestions();
            return;
        }

        row.data('product-data', productData); // Store full product data in the row
        row.find('[data-field="sanPhamID"]').val(productData.productId);
        row.find('[data-field="maHang"]').val(productData.code).prop('readonly', true); // Make code readonly after selection
        row.find('[data-field="tenSanPham"]').val(productData.name);
        row.find('[data-field="ID_ThongSo"]').val(productData.id_thongso);
        row.find('[data-field="DoDay"]').val(productData.thickness);
        row.find('[data-field="BanRong"]').val(productData.width);
        row.find('[data-field="soLuongYeuCau"]').val('1'); // Default quantity to 1 for direct entry
        row.find('[data-field="soLuongThucXuat"]').val('1');
    }


    // Handle Save Button Click
    $('#save-xuatkho-btn').on('click', function () {
        const pxkIdToSave = $(this).data('pxk-id');
        const ngayXuatText = $('#info-ngayxuat').text();
        const soPhieuXuatText = $('#info-sophieu').text();
        const nguoiNhanInput = $('#input-nguoinhan').val();
        const diaChiInput = $('#input-diachi').val();
        const lyDoXuatInput = $('#input-lydoxuat').val();

        if (!nguoiNhanInput || !lyDoXuatInput) {
            App.showMessageModal('Vui lòng điền Người nhận hàng và Lý do xuất kho.', 'info');
            return;
        }

        // Extract date from text
        const ngayXuatParts = ngayXuatText.match(/Ngày (\d+) tháng (\d+) năm (\d+)/);
        let ngayXuatFormatted = null;
        if (ngayXuatParts) {
            ngayXuatFormatted = `${ngayXuatParts[3]}-${ngayXuatParts[2].padStart(2, '0')}-${ngayXuatParts[1].padStart(2, '0')}`;
        } else {
            const today = new Date();
            ngayXuatFormatted = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        }

        const soPhieuXuat = soPhieuXuatText.replace('Số: ', '').trim();

        const items = [];
        let hasProduct = false;
        $('#xuatkho-items-body .product-row').each(function () {
            const row = $(this);
            const sanPhamID = row.find('[data-field="sanPhamID"]').val();
            if (sanPhamID) { // Only include rows where a product has been selected
                hasProduct = true;
                items.push({
                    sanPhamID: sanPhamID,
                    maHang: row.find('[data-field="maHang"]').val(),
                    tenSanPham: row.find('[data-field="tenSanPham"]').val(),
                    soLuongYeuCau: parseInt(row.find('[data-field="soLuongYeuCau"]').val()) || 0,
                    soLuongThucXuat: parseInt(row.find('[data-field="soLuongThucXuat"]').val()) || 0,
                    taiSo: row.find('[data-field="taiSo"]').val(),
                    ghiChu: row.find('[data-field="ghiChu"]').val()
                });
            }
        });

        if (!hasProduct) {
            App.showMessageModal('Vui lòng thêm ít nhất một sản phẩm vào phiếu xuất kho.', 'info');
            return;
        }

        const postData = {
            action: 'save_xuatkho',
            phieuXuatKhoID: pxkIdToSave,
            // No ycsx_id for general export slips
            ngayXuat: ngayXuatFormatted,
            soPhieuXuat: soPhieuXuat,
            nguoiNhan: nguoiNhanInput,
            diaChiGiaoHang: diaChiInput, // Pass the address input
            ghiChu: lyDoXuatInput,
            items: items
        };

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...');

        $.ajax({
            url: 'api/xuatkho.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(postData),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    App.showMessageModal('Phiếu xuất kho đã được lưu thành công!', 'success');

                    // Show "Tạo PDF thất bại" after a 2-second delay
                    setTimeout(function () {
                        App.showMessageModal('Tạo PDF thất bại', 'error');
                    }, 2000); // 2000 milliseconds = 2 seconds

                    // --- CHUYỂN HƯỚNG SAU KHI LƯU THÀNH CÔNG ---
                    // This redirection will happen immediately after the first message,
                    // and before the delayed PDF failure message appears.
                    history.pushState({ page: 'xuatkho_issued_list' }, '', '?page=xuatkho_issued_list');
                    handleRouting(); // Call handleRouting to load the new page
                    // ------------------------------------------
                } else {
                    App.showMessageModal('Lỗi khi lưu phiếu xuất kho: ' + response.message, 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error: ", status, error, xhr.responseText);
                App.showMessageModal('Lỗi kết nối đến server. Vui lòng thử lại.', 'error');
            },
            complete: function () {
                $('#save-xuatkho-btn').prop('disabled', false).html('<i class="fas fa-check-circle mr-2"></i>Hoàn Tất Xuất Kho');
            }
        });
    });

    $('#back-to-list-btn').on('click', function () {
        window.location.href = 'pages/xuatkho_list.php'; // Or wherever your general export list page is
    });
}