function initializeCreateStockPOPage() {
    // === KHAI BÁO BIẾN ===
    let selectedProduct = null;
    let productionItems = [];
    let debounceTimeout = null;
    let highlightedIndex = -1;

    // === LẤY CÁC PHẦN TỬ DOM ===
    const productSearchInput = $('#product-search');
    const searchResultsDiv = $('#product-search-results');
    const quantityInput = $('#product-quantity');
    const tableBody = $('#stock-po-items-body');
    const stockPoTable = $('#stock-po-table');
    
    // Filter elements
    const filterGroup = $('#filter-group');
    const filterType = $('#filter-type');
    const filterThickness = $('#filter-thickness');
    const filterWidth = $('#filter-width');
    const filterOnlyLowStock = $('#filter-only-low-stock');
    const addFilteredItemsBtn = $('#add-filtered-items-btn');
    const addAllLowStockBtn = $('#add-all-low-stock-btn');


    // === CÁC HÀM TIỆN ÍCH ===

    /**
     * Vẽ lại toàn bộ bảng danh sách sản phẩm từ mảng productionItems.
     */
    function renderItemsTable() {
        tableBody.empty();
        if (productionItems.length === 0) {
            const emptyRow = '<tr><td colspan="7" class="text-center p-6 text-gray-500">Chưa có sản phẩm nào được thêm.</td></tr>';
            tableBody.html(emptyRow);
            return;
        }

        const allSelected = productionItems.length > 0 && productionItems.every(item => item.isSelected);
        $('#select-all-checkbox').prop('checked', allSelected);

        productionItems.forEach((item, index) => {
            const formattedQuantity = (window.App && App.formatNumber) ? App.formatNumber(item.quantity) : item.quantity;
            const formattedStock = (window.App && App.formatNumber) ? App.formatNumber(item.currentStock) : item.currentStock;
            
            const row = `
                <tr>
                    <td class="p-3 border-b text-center">
                        <input type="checkbox" class="item-checkbox h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" data-id="${item.productId}" ${item.isSelected ? 'checked' : ''}>
                    </td>
                    <td class="p-3 border-b text-center">${index + 1}</td>
                    <td class="p-3 border-b">${item.code}</td>
                    <td class="p-3 border-b text-right font-bold">
                        <input type="number" class="w-24 text-right p-1 border rounded item-quantity" value="${item.quantity}" data-id="${item.productId}">
                    </td>
                    <td class="p-3 border-b text-right font-semibold text-blue-600">${formattedStock}</td>
                    <td class="p-3 border-b text-center">
                        <button class="remove-item-btn text-red-500 hover:text-red-700" data-id="${item.productId}" title="Xóa sản phẩm">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                    <td class="p-3 border-b font-medium text-gray-800">${item.name}</td>
                </tr>`;
            tableBody.append(row);
        });
    }

    /**
     * Tải và điền dữ liệu cho các dropdown bộ lọc.
     */
    function populateFilters() {
        $.getJSON('api/get_products_with_stock.php')
            .done(function(response) {
                if (response.success && response.filters) {
                    const filters = response.filters;
                    filters.productGroups.forEach(group => filterGroup.append(`<option value="${group}">${group}</option>`));
                    filters.productTypes.forEach(type => filterType.append(`<option value="${type}">${type}</option>`));
                    filters.thicknesses.forEach(thick => filterThickness.append(`<option value="${thick}">${thick}</option>`));
                    filters.widths.forEach(width => filterWidth.append(`<option value="${width}">${width}</option>`));
                }
            })
            .fail(function() {
                console.error("Không thể tải dữ liệu bộ lọc.");
            });
    }

    /**
     * Hiển thị modal lỗi cấu hình và cung cấp link điều hướng
     */
    function showConfigErrorModal(message) {
        // Remove existing modal first to prevent duplicates
        $('#config-error-modal').remove();

        const modalHtml = `
            <div id="config-error-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center" style="display: flex;">
              <div class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                  <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                  </div>
                  <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Lỗi Cấu Hình Năng Suất</h3>
                  <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 text-left">${message}</p>
                  </div>
                  <div class="items-center px-4 py-3 space-x-4">
                    <button id="go-to-config-btn" class="px-4 py-2 bg-blue-600 text-white text-base font-medium rounded-md w-auto shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                      Đến trang cấu hình
                    </button>
                    <button id="close-config-modal-btn" class="px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md w-auto shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400">
                      Đóng
                    </button>
                  </div>
                </div>
              </div>
            </div>
        `;

        $('body').append(modalHtml);

        $('#go-to-config-btn').on('click', function() {
            window.location.href = 'index.php?page=quanly_cauhinh_sanxuat';
        });

        $('#close-config-modal-btn').on('click', function() {
            $('#config-error-modal').remove();
        });
    }


    // === CÁC TRÌNH XỬ LÝ SỰ KIỆN (EVENT HANDLERS) ===

    /**
     * 1A. THÊM SẢN PHẨM TỰ ĐỘNG THEO BỘ LỌC
     */
    addFilteredItemsBtn.on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang tải...');

        const params = {
            group: filterGroup.val(),
            type: filterType.val(),
            thickness: filterThickness.val(),
            width: filterWidth.val(),
            onlyLowStock: filterOnlyLowStock.is(':checked')
        };

        $.getJSON('api/get_low_stock_for_po.php', params)
            .done(function(response) {
                if (response.success && response.data) {
                    let addedCount = 0;
                    response.data.forEach(product => {
                        const existingItem = productionItems.find(item => item.productId === parseInt(product.productId));
                        if (!existingItem) {
                            const neededQuantity = Math.max(0, parseInt(product.minimumStockLevel) - parseInt(product.currentStock));
                            productionItems.push({
                                productId: parseInt(product.productId),
                                code: product.code,
                                name: product.name,
                                currentStock: parseInt(product.currentStock),
                                quantity: neededQuantity > 0 ? neededQuantity : 1,
                                isSelected: true
                            });
                            addedCount++;
                        }
                    });
                    
                    const message = addedCount > 0 ? `Đã thêm ${addedCount} sản phẩm mới vào danh sách.` : 'Không có sản phẩm mới nào phù hợp được tìm thấy.';
                    const type = addedCount > 0 ? 'success' : 'info';
                    (window.App && App.showToast) ? App.showToast(message, type) : alert(message);

                    renderItemsTable();
                } else {
                    alert('Lỗi: ' + (response.message || 'Không thể tải dữ liệu sản phẩm.'));
                }
            })
            .fail(() => alert('Lỗi kết nối đến máy chủ.'))
            .always(() => btn.prop('disabled', false).html('<i class="fas fa-filter mr-2"></i>Thêm theo bộ lọc'));
    });

    /**
     * 1B. THÊM TẤT CẢ SẢN PHẨM DƯỚI ĐỊNH MỨC
     */
    addAllLowStockBtn.on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang tải...');

        $.getJSON('api/get_low_stock_for_po.php', { onlyLowStock: true })
            .done(function(response) {
                if (response.success && response.data) {
                    let addedCount = 0;
                    response.data.forEach(product => {
                        const existingItem = productionItems.find(item => item.productId === parseInt(product.productId));
                        if (!existingItem) {
                             const neededQuantity = Math.max(0, parseInt(product.minimumStockLevel) - parseInt(product.currentStock));
                            productionItems.push({
                                productId: parseInt(product.productId),
                                code: product.code,
                                name: product.name,
                                currentStock: parseInt(product.currentStock),
                                quantity: neededQuantity > 0 ? neededQuantity : 1,
                                isSelected: true
                            });
                            addedCount++;
                        }
                    });
                    
                    const message = addedCount > 0 ? `Đã thêm ${addedCount} sản phẩm dưới định mức.` : 'Không có sản phẩm mới nào dưới định mức.';
                    const type = addedCount > 0 ? 'success' : 'info';
                    (window.App && App.showToast) ? App.showToast(message, type) : alert(message);

                    renderItemsTable();
                } else {
                    alert('Lỗi: ' + (response.message || 'Không thể tải dữ liệu sản phẩm.'));
                }
            })
            .fail(() => alert('Lỗi kết nối đến máy chủ.'))
            .always(() => btn.prop('disabled', false).html('<i class="fas fa-layer-group mr-2"></i>Thêm tất cả (dưới định mức)'));
    });

    /**
     * 2. TÌM KIẾM SẢN PHẨM THỦ CÔNG
     */
    productSearchInput.on('keyup', function(e) {
        if (['ArrowDown', 'ArrowUp', 'Enter', 'Escape'].includes(e.key) || (e.keyCode >= 112 && e.keyCode <= 123)) {
            return;
        }
        const query = $(this).val().trim();
        clearTimeout(debounceTimeout);
        highlightedIndex = -1;
        if (query.length < 2) { // Chỉ tìm kiếm khi có ít nhất 2 ký tự
            searchResultsDiv.empty().hide();
            return;
        }
        debounceTimeout = setTimeout(() => {
            $.getJSON('api/search_products_kho.php', { q: query }, function(response) {
                searchResultsDiv.empty();
                if (response && response.success && response.data.length > 0) {
                    response.data.forEach((product, index) => {
                        const shortcutKey = (index < 12) ? `<span class="text-xs font-bold text-blue-600 ml-2">[F${index + 1}]</span>` : '';
                        const itemHtml = `<div class="p-2 hover:bg-gray-100 cursor-pointer search-result-item flex justify-between items-center" 
                                             data-id="${product.productId}" 
                                             data-name="${product.name}" 
                                             data-code="${product.code}"
                                             data-stock="${product.currentStock}">
                                            <span><strong>${product.name}</strong> (${product.code})</span>
                                            ${shortcutKey}
                                         </div>`;
                        searchResultsDiv.append(itemHtml);
                    });
                    searchResultsDiv.show();
                } else {
                    const message = (response && response.message) ? response.message : "Không tìm thấy sản phẩm.";
                    searchResultsDiv.html(`<div class="p-2 text-gray-500">${message}</div>`).show();
                }
            });
        }, 300);
    });
    
    /**
     * 3. CHỌN MỘT SẢN PHẨM TỪ KẾT QUẢ TÌM KIẾM (BẰNG CHUỘT)
     */
    $(document).on('click', '.search-result-item', function() {
        const el = $(this);
        selectedProduct = {
            productId: parseInt(el.data('id')),
            name: el.data('name'),
            code: el.data('code'),
            currentStock: parseInt(el.data('stock'))
        };
        productSearchInput.val(selectedProduct.name);
        searchResultsDiv.empty().hide();
        quantityInput.focus();
    });
    
    /**
     * 4. THÊM SẢN PHẨM ĐÃ CHỌN VÀO DANH SÁCH
     */
    $('#add-product-btn').on('click', function() {
        if (!selectedProduct) {
            alert('Vui lòng tìm kiếm và chọn một sản phẩm.');
            return;
        }
        const quantity = parseInt(quantityInput.val());
        if (isNaN(quantity) || quantity <= 0) {
            alert('Vui lòng nhập số lượng hợp lệ (lớn hơn 0).');
            return;
        }
        const existingItem = productionItems.find(item => item.productId === selectedProduct.productId);
        if (existingItem) {
            alert('Sản phẩm này đã có trong danh sách. Bạn có thể chỉnh sửa số lượng trực tiếp trong bảng.');
            return;
        }
        productionItems.push({ ...selectedProduct, quantity: quantity, isSelected: true });
        renderItemsTable();
        productSearchInput.val('');
        quantityInput.val('');
        selectedProduct = null;
        productSearchInput.focus();
    });

    /**
     * 5. XỬ LÝ CÁC NÚT XÓA, CẬP NHẬT VÀ CHECKBOX TRONG BẢNG
     */
    tableBody.on('click', '.remove-item-btn', function() {
        const productIdToRemove = parseInt($(this).data('id'));
        productionItems = productionItems.filter(item => item.productId !== productIdToRemove);
        renderItemsTable();
    });

    $('#remove-all-items-btn').on('click', function() {
        if (productionItems.length === 0) return;
        if (confirm('Bạn có chắc chắn muốn xóa tất cả sản phẩm khỏi danh sách không?')) {
            productionItems = [];
            renderItemsTable();
        }
    });

    tableBody.on('change', '.item-quantity', function() {
        const newQuantity = parseInt($(this).val()) || 0;
        const productIdToUpdate = parseInt($(this).data('id'));
        const item = productionItems.find(p => p.productId === productIdToUpdate);
        if (item) {
            item.quantity = newQuantity;
        }
    });

    tableBody.on('change', '.item-checkbox', function() {
        const productIdToUpdate = parseInt($(this).data('id'));
        const isChecked = $(this).is(':checked');
        const item = productionItems.find(p => p.productId === productIdToUpdate);
        if (item) {
            item.isSelected = isChecked;
        }
        const allSelected = productionItems.length > 0 && productionItems.every(i => i.isSelected);
        $('#select-all-checkbox').prop('checked', allSelected);
    });

    stockPoTable.on('change', '#select-all-checkbox', function() {
        const isChecked = $(this).is(':checked');
        productionItems.forEach(item => item.isSelected = isChecked);
        renderItemsTable();
    });

    /**
     * 6. TẠO LỆNH SẢN XUẤT (SUBMIT)
     */
    $('#create-stock-po-btn').on('click', function() {
        const finalItems = productionItems.filter(item => item.isSelected && item.quantity > 0);
        
        if (finalItems.length === 0) {
            alert('Vui lòng chọn ít nhất một sản phẩm và nhập số lượng lớn hơn 0.');
            return;
        }
        if (!confirm('Bạn có chắc chắn muốn tạo Lệnh Sản Xuất Lưu Kho với các sản phẩm đã chọn?')) {
            return;
        }
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...');
        $.ajax({
            url: 'api/create_stock_production_order.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ items: finalItems }),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    window.location.href = '?page=quanly_sanxuat_list';
                } else {
                    alert('Lỗi: ' + response.message);
                }
            },
            error: function(jqXHR) {
                let errorMessage = 'Lỗi kết nối đến máy chủ.';
                let isProductivityError = false;
                let specificMessage = '';

                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    specificMessage = jqXHR.responseJSON.message;
                    errorMessage = 'Lỗi: ' + specificMessage;
                    // Check for the specific error string
                    if (specificMessage.includes('Năng suất không hợp lệ')) {
                        isProductivityError = true;
                    }
                }

                if (isProductivityError) {
                    showConfigErrorModal(specificMessage);
                } else {
                    // Fallback to the original alert behavior for other errors
                    alert(errorMessage);
                }
            },
            complete: () => btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-2"></i>Xác Nhận Tạo Lệnh')
        });
    });

    /**
     * 7. XỬ LÝ SỰ KIỆN BÀN PHÍM ĐỂ CHỌN NHANH KẾT QUẢ
     */
     $(document).on('keydown', function(e) {
        if (!searchResultsDiv.is(':visible')) return;
        const results = searchResultsDiv.children('.search-result-item');
        if (results.length === 0) return;

        if (e.keyCode >= 112 && e.keyCode <= 123) {
            e.preventDefault();
            const index = e.keyCode - 112;
            if (index < results.length) {
                results.eq(index).trigger('click');
            }
            return;
        }
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                highlightedIndex++;
                if (highlightedIndex >= results.length) highlightedIndex = 0;
                break;
            case 'ArrowUp':
                e.preventDefault();
                highlightedIndex--;
                if (highlightedIndex < 0) highlightedIndex = results.length - 1;
                break;
            case 'Enter':
                e.preventDefault();
                if (highlightedIndex > -1) {
                    results.eq(highlightedIndex).trigger('click');
                }
                return;
            case 'Escape':
                searchResultsDiv.empty().hide();
                return;
            default:
                return;
        }
        results.removeClass('bg-blue-100');
        results.eq(highlightedIndex).addClass('bg-blue-100');
     });


    // --- KHỞI CHẠY BAN ĐẦU ---
    populateFilters();
    renderItemsTable(); // Render empty table
}

