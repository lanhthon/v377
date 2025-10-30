$(document).ready(function () {
    // === DOM ELEMENTS ===
    const priceForm = $('#price-form');
    const formTitle = $('#price-form-title');
    const saveButton = $('#save-price-btn');
    const clearButton = $('#clear-price-form-btn');
    const priceListBody = $('#price-list-body');
    const priceIdInput = $('#price-id');

    // === UTILITY FUNCTIONS (assuming main.js functions are available) ===
    function showLocalMessage(message, isSuccess = true) {
        const messageType = isSuccess ? 'success' : 'error';
        // Use the global modal function if available
        if (typeof showMessageModal === 'function') {
            showMessageModal(message, messageType);
        } else {
            alert(message);
        }
    }

    // === FORM & STATE MANAGEMENT ===
    function setFormState(state, price = null) {
        priceForm[0].reset();
        priceIdInput.val(''); // Clear hidden ID
        if (state === 'add') {
            formTitle.text('Thêm cơ chế giá mới');
            saveButton.html('<i class="fas fa-plus mr-2"></i>Thêm mới');
            $('#price-code').focus();
        } else if (state === 'edit' && price) {
            formTitle.text('Cập nhật cơ chế giá');
            saveButton.html('<i class="fas fa-save mr-2"></i>Lưu thay đổi');
            priceIdInput.val(price.CoCheGiaID);
            $('#price-code').val(price.MaCoChe);
            $('#price-name').val(price.TenCoChe);
            $('#price-adjustment').val(price.PhanTramDieuChinh);
        }
    }

    // === DATA FETCHING & RENDERING ===
    function loadPrices() {
        priceListBody.html('<tr><td colspan="4" class="text-center p-4">Đang tải dữ liệu...</td></tr>');
        $.ajax({
            url: 'api/get_all_prices.php',
            dataType: 'json',
            success: function (prices) {
                priceListBody.empty();
                if (prices && prices.length > 0) {
                    prices.forEach(p => {
                        const row = `
                            <tr data-price-id="${p.CoCheGiaID}">
                                <td class="py-2 px-4 border-b font-mono">${p.MaCoChe}</td>
                                <td class="py-2 px-4 border-b">${p.TenCoChe}</td>
                                <td class="py-2 px-4 border-b text-right font-mono">${p.PhanTramDieuChinh}%</td>
                                <td class="py-2 px-4 border-b text-center">
                                    <button class="edit-btn text-blue-500 hover:text-blue-700 mr-3" title="Sửa"><i class="fas fa-edit"></i></button>
                                    <button class="delete-btn text-red-500 hover:text-red-700" title="Xóa"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        `;
                        priceListBody.append(row);
                        // Store the full data object with the row
                        $(`tr[data-price-id="${p.CoCheGiaID}"]`).data('price', p);
                    });
                } else {
                    priceListBody.html('<tr><td colspan="4" class="text-center p-4">Chưa có cơ chế giá nào.</td></tr>');
                }
            },
            error: () => {
                priceListBody.html('<tr><td colspan="4" class="text-center p-4 text-red-500">Lỗi khi tải danh sách.</td></tr>');
            }
        });
    }

    // === EVENT HANDLERS ===
    clearButton.on('click', function () {
        setFormState('add');
    });

    priceForm.on('submit', function (e) {
        e.preventDefault();
        const priceId = priceIdInput.val();
        const action = priceId ? 'update' : 'add';

        let formData = {
            action: action,
            CoCheGiaID: priceId,
            MaCoChe: $('#price-code').val().toUpperCase(),
            TenCoChe: $('#price-name').val(),
            PhanTramDieuChinh: $('#price-adjustment').val()
        };

        saveButton.prop('disabled', true).fadeTo(500, 0.5);

        $.ajax({
            url: 'api/manage_price.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    showLocalMessage(res.message, true);
                    setFormState('add');
                    loadPrices();
                } else {
                    showLocalMessage(res.message, false);
                }
            },
            error: () => showLocalMessage('Lỗi kết nối đến server.', false),
            complete: () => saveButton.prop('disabled', false).fadeTo(500, 1.0)
        });
    });

    priceListBody.on('click', '.edit-btn', function () {
        const priceData = $(this).closest('tr').data('price');
        setFormState('edit', priceData);
        $('html, body').animate({ scrollTop: 0 }, 'slow');
    });

    priceListBody.on('click', '.delete-btn', function () {
        const priceData = $(this).closest('tr').data('price');
        // Use global confirmation modal if available
        if (typeof showConfirmationModal === 'function') {
            showConfirmationModal(`Bạn có chắc muốn xóa cơ chế giá "${priceData.TenCoChe}"?`, function () {
                deletePrice(priceData.CoCheGiaID);
            });
        } else if (confirm(`Bạn có chắc muốn xóa cơ chế giá "${priceData.TenCoChe}"?`)) {
            deletePrice(priceData.CoCheGiaID);
        }
    });

    function deletePrice(id) {
        $.ajax({
            url: 'api/manage_price.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: 'delete', CoCheGiaID: id }),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    showLocalMessage(res.message, true);
                    loadPrices();
                    setFormState('add'); // Clear form if the deleted item was being edited
                } else {
                    showLocalMessage(res.message, false);
                }
            },
            error: () => showLocalMessage('Lỗi kết nối đến server khi xóa.', false)
        });
    }

    // === INITIALIZATION ===
    function initializePricePage() {
        loadPrices();
        setFormState('add');
    }

    // Call the initializer. This script is loaded specifically for the prices.php page.
    initializePricePage();
});
