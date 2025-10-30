$(document).ready(function () {
    // === BIẾN TRẠNG THÁI ===
    let currentPage = 1;
    const productsPerPage = 10; // Số sản phẩm hiển thị trên mỗi trang

    // === DOM ELEMENTS (SẢN PHẨM) ===
    const productForm = $('#product-form');
    const formTitle = $('#product-form-title');
    const saveButton = $('#save-product-btn');
    const clearButton = $('#clear-form-btn');
    const productListBody = $('#product-list-body');
    const categorySelect = $('#product-category');
    const productIdInput = $('#product-id');
    const priceInput = $('#product-base-price');
    const paginationContainer = $('#pagination-container');

    // === DOM ELEMENTS (HỘP THOẠI LOẠI SẢN PHẨM) ===
    const categoryModal = $('#category-modal');
    const openModalBtn = $('#open-category-modal-btn');
    const closeModalBtn = $('#close-category-modal-btn');
    const categoryAddForm = $('#category-add-form');
    const newCategoryNameInput = $('#new-category-name');
    const addCategoryBtn = $('#add-category-btn');
    const categoryListContainer = $('#category-list-container');

    // === CÁC HÀM TIỆN ÍCH ===
    function formatNumber(num) {
        if (isNaN(num) || num === null || num === undefined) return '0';
        return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
    }

    function parseNumber(str) {
        if (typeof str !== 'string' || str.trim() === '') return 0;
        return parseFloat(String(str).replace(/,/g, '')) || 0;
    }

    function showMessage(message, isSuccess = true) {
        const messageType = isSuccess ? 'success' : 'error';
        if (typeof showMessageModal === 'function') {
            showMessageModal(message, messageType);
        } else {
            alert((isSuccess ? 'Thành công: ' : 'Lỗi: ') + message);
        }
    }

    // === QUẢN LÝ HỘP THOẠI (MODAL) ===
    function openCategoryModal() {
        categoryModal.removeClass('hidden');
        loadAndRenderCategories(); // Tải dữ liệu mới nhất khi mở hộp thoại
    }

    function closeCategoryModal() {
        categoryModal.addClass('hidden');
    }

    // === TẠO PHÂN TRANG ===
    function renderPagination(totalProducts) {
        paginationContainer.empty();
        const totalPages = Math.ceil(totalProducts / productsPerPage);
        if (totalPages <= 1) return;

        for (let i = 1; i <= totalPages; i++) {
            const pageButton = $(`<button class="px-3 py-1 mx-1 rounded-md text-sm transition-colors duration-200">`);
            pageButton.text(i);
            if (i === currentPage) {
                pageButton.addClass('bg-blue-600 text-white cursor-default');
            } else {
                pageButton.addClass('bg-gray-200 hover:bg-gray-300');
            }
            pageButton.on('click', function (e) {
                e.preventDefault();
                if (i !== currentPage) {
                    currentPage = i;
                    loadProducts(currentPage);
                }
            });
            paginationContainer.append(pageButton);
        }
    }

    // === QUẢN LÝ TRẠNG THÁI FORM SẢN PHẨM ===
    function setFormState(state, product = null) {
        productForm[0].reset();
        if (state === 'add') {
            formTitle.text('Thêm sản phẩm mới');
            saveButton.html('<i class="fas fa-plus mr-2"></i>Thêm mới');
            productIdInput.val('');
            $('#product-code').focus();
        } else if (state === 'edit' && product) {
            formTitle.text('Cập nhật sản phẩm');
            saveButton.html('<i class="fas fa-save mr-2"></i>Lưu thay đổi');
            productIdInput.val(product.SanPhamID);
            $('#product-code').val(product.MaHang);
            $('#product-name').val(product.TenSanPham);
            categorySelect.val(product.LoaiID);
            $('#product-id-thongso').val(product.ID_ThongSo);
            $('#product-thickness').val(product.DoDay);
            $('#product-width').val(product.BanRong);
            priceInput.val(formatNumber(product.GiaGoc));
        }
    }

    // === TẢI VÀ HIỂN THỊ DỮ LIỆU ===
    function loadProducts(page) {
        productListBody.html('<tr><td colspan="5" class="text-center p-4">Đang tải dữ liệu...</td></tr>');
        $.ajax({
            url: `api/get_all_products.php?page=${page}&limit=${productsPerPage}`,
            dataType: 'json',
            success: function (response) {
                productListBody.empty();
                if (response && response.data && response.data.length > 0) {
                    response.data.forEach(p => {
                        const row = `
                            <tr data-product-id="${p.SanPhamID}">
                                <td class="py-2 px-4 border-b">${p.MaHang}</td>
                                <td class="py-2 px-4 border-b">${p.TenSanPham}</td>
                                <td class="py-2 px-4 border-b text-sm text-gray-600">${p.TenLoai || 'N/A'}</td>
                                <td class="py-2 px-4 border-b text-right font-mono">${formatNumber(p.GiaGoc)}</td>
                                <td class="py-2 px-4 border-b text-center">
                                    <button class="edit-btn text-blue-500 hover:text-blue-700 mr-3" title="Sửa"><i class="fas fa-edit"></i></button>
                                    <button class="delete-btn text-red-500 hover:text-red-700" title="Xóa"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>`;
                        productListBody.append(row);
                        $(`tr[data-product-id="${p.SanPhamID}"]`).data('product', p);
                    });
                    renderPagination(response.total);
                } else {
                    productListBody.html('<tr><td colspan="5" class="text-center p-4">Không tìm thấy sản phẩm nào.</td></tr>');
                    paginationContainer.empty();
                }
            },
            error: () => {
                productListBody.html('<tr><td colspan="5" class="text-center p-4 text-red-500">Lỗi khi tải danh sách sản phẩm.</td></tr>');
            }
        });
    }

    function loadAndRenderCategories() {
        $.ajax({
            url: 'api/get_product_categories.php',
            dataType: 'json',
            success: function (categories) {
                const selectedCategory = categorySelect.val();
                categorySelect.empty().append('<option value="">-- Chọn loại sản phẩm --</option>');
                categoryListContainer.html('');

                if (categories && categories.length > 0) {
                    categories.forEach(cat => {
                        categorySelect.append(`<option value="${cat.LoaiID}">${cat.TenLoai}</option>`);

                        const itemHTML = `
                            <div class="flex items-center justify-between p-2 bg-gray-100 rounded" data-category-id="${cat.LoaiID}" data-category-name="${cat.TenLoai}">
                                <span class="font-medium text-gray-700">${cat.TenLoai}</span>
                                <div>
                                    <button class="edit-category-btn text-blue-500 hover:text-blue-700 mr-2" title="Sửa"><i class="fas fa-edit"></i></button>
                                    <button class="delete-category-btn text-red-500 hover:text-red-700" title="Xóa"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>`;
                        categoryListContainer.append(itemHTML);
                    });
                    categorySelect.val(selectedCategory);
                } else {
                    categoryListContainer.html('<p class="text-sm text-gray-500 text-center py-4">Chưa có loại sản phẩm nào.</p>');
                }
            },
            error: () => showMessage('Không thể tải danh sách loại sản phẩm.', false)
        });
    }

    // === CÁC BỘ LẮNG NGHE SỰ KIỆN ===

    // Sự kiện cho hộp thoại
    openModalBtn.on('click', openCategoryModal);
    closeModalBtn.on('click', closeCategoryModal);
    categoryModal.on('click', function (e) {
        if ($(e.target).is(categoryModal)) {
            closeCategoryModal();
        }
    });

    // Sự kiện Thêm/Sửa/Xóa cho Loại sản phẩm (trong hộp thoại)
    categoryAddForm.on('submit', function (e) {
        e.preventDefault();
        const newName = newCategoryNameInput.val().trim();
        if (!newName) {
            showMessage('Tên loại sản phẩm không được để trống.', false);
            return;
        }

        addCategoryBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: 'api/manage_category.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: 'add', TenLoai: newName }),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    showMessage(res.message);
                    newCategoryNameInput.val('');
                    loadAndRenderCategories();
                } else {
                    showMessage(res.message, false);
                }
            },
            error: (jqXHR) => {
                const res = jqXHR.responseJSON;
                showMessage(res ? res.message : 'Lỗi server khi thêm loại sản phẩm.', false);
            },
            complete: () => addCategoryBtn.prop('disabled', false).html('<i class="fas fa-plus"></i>')
        });
    });

    categoryListContainer.on('click', '.edit-category-btn', function () {
        const categoryItem = $(this).closest('[data-category-id]');
        const id = categoryItem.data('category-id');
        const currentName = categoryItem.data('category-name');
        const newName = prompt('Nhập tên mới cho loại sản phẩm:', currentName);

        if (newName && newName.trim() && newName.trim() !== currentName) {
            $.ajax({
                url: 'api/manage_category.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ action: 'update', LoaiID: id, TenLoai: newName.trim() }),
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        showMessage(res.message);
                        loadAndRenderCategories(); // Tải lại danh sách loại
                        loadProducts(currentPage); // Tải lại sản phẩm để cập nhật tên loại
                    } else {
                        showMessage(res.message, false);
                    }
                },
                error: (jqXHR) => {
                    const res = jqXHR.responseJSON;
                    showMessage(res ? res.message : 'Lỗi server khi cập nhật.', false);
                }
            });
        }
    });

    categoryListContainer.on('click', '.delete-category-btn', function () {
        const categoryItem = $(this).closest('[data-category-id]');
        const id = categoryItem.data('category-id');
        const name = categoryItem.data('category-name');

        const confirmAction = () => {
            $.ajax({
                url: 'api/manage_category.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ action: 'delete', LoaiID: id }),
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        showMessage(res.message);
                        loadAndRenderCategories(); // Tải lại danh sách loại
                        setFormState('add'); // Reset form sản phẩm đề phòng loại bị xóa đang được chọn
                        loadProducts(currentPage); // Tải lại danh sách sản phẩm
                    } else {
                        showMessage(res.message, false);
                    }
                },
                error: (jqXHR) => {
                    const res = jqXHR.responseJSON;
                    showMessage(res ? res.message : 'Lỗi server khi xóa.', false);
                }
            });
        };

        const message = `Bạn có chắc muốn xóa loại "${name}"? Hành động này sẽ không thể hoàn tác.`;
        if (typeof showConfirmationModal === 'function') {
            showConfirmationModal(message, confirmAction);
        } else if (confirm(message)) {
            confirmAction();
        }
    });

    // Sự kiện cho Form Sản phẩm
    priceInput.on('input', function () {
        const value = parseNumber($(this).val());
        $(this).val(formatNumber(value));
    });

    clearButton.on('click', () => setFormState('add'));

    productForm.on('submit', function (e) {
        e.preventDefault();
        const productId = productIdInput.val();
        const action = productId ? 'update' : 'add';

        let formData = {
            action: action, SanPhamID: productId,
            MaHang: $('#product-code').val(), TenSanPham: $('#product-name').val(),
            LoaiID: categorySelect.val(), ID_ThongSo: $('#product-id-thongso').val(),
            DoDay: $('#product-thickness').val(), BanRong: $('#product-width').val(),
            GiaGoc: parseNumber(priceInput.val())
        };

        $.ajax({
            url: 'api/manage_product.php', method: 'POST',
            contentType: 'application/json', data: JSON.stringify(formData),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    showMessage(res.message);
                    setFormState('add');
                    // Nếu là thêm mới, quay về trang 1. Nếu cập nhật, ở lại trang hiện tại.
                    if (action === 'add') {
                        currentPage = 1;
                    }
                    loadProducts(currentPage);
                } else {
                    showMessage(res.message, false);
                }
            },
            error: () => showMessage('Lỗi kết nối đến server.', false)
        });
    });

    productListBody.on('click', '.edit-btn', function () {
        const productData = $(this).closest('tr').data('product');
        setFormState('edit', productData);
        $('html, body').animate({ scrollTop: 0 }, 'slow');
    });

    productListBody.on('click', '.delete-btn', function () {
        const productData = $(this).closest('tr').data('product');
        const deleteAction = () => {
            $.ajax({
                url: 'api/manage_product.php', method: 'POST',
                contentType: 'application/json', data: JSON.stringify({ action: 'delete', SanPhamID: productData.SanPhamID }),
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        showMessage(res.message);
                        // Tải lại trang hiện tại sau khi xóa
                        loadProducts(currentPage);
                    } else {
                        showMessage(res.message, false);
                    }
                },
                error: () => showMessage('Lỗi kết nối đến server khi xóa.', false)
            });
        };

        const message = `Bạn có chắc muốn xóa sản phẩm "${productData.TenSanPham}"?`;
        if (typeof showConfirmationModal === 'function') {
            showConfirmationModal(message, deleteAction);
        } else if (confirm(message)) {
            deleteAction();
        }
    });

    // === KHỞI TẠO TRANG ===
    function initializePage() {
        loadProducts(currentPage);
        loadAndRenderCategories();
        setFormState('add');
    }

    initializePage();
});