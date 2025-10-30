$(document).ready(function () {
    // === DOM ELEMENTS ===
    const customerForm = $('#customer-form');
    const formTitle = $('#customer-form-title');
    const saveButton = $('#save-customer-btn');
    const clearButton = $('#clear-customer-form-btn');
    const customerListBody = $('#customer-list-body');
    const priceSchemaSelect = $('#customer-price-schema');
    const customerIdInput = $('#customer-id');

    // === UTILITY FUNCTIONS ===
    function showMessage(message, isSuccess = true) {
        const messageType = isSuccess ? 'success' : 'error';
        const title = isSuccess ? 'Thành công!' : 'Có lỗi xảy ra!';
        if (typeof showMessageModal === 'function') {
            showMessageModal(message, messageType);
        } else {
            alert(`${title}\n${message}`);
        }
    }

    // === FORM & STATE MANAGEMENT ===
    function setFormState(state, customer = null) {
        customerForm[0].reset();
        if (state === 'add') {
            formTitle.text('Thêm khách hàng mới');
            saveButton.html('<i class="fas fa-plus mr-2"></i>Thêm mới');
            customerIdInput.val('');
            $('#customer-company-name').focus();
        } else if (state === 'edit' && customer) {
            formTitle.text('Cập nhật thông tin khách hàng');
            saveButton.html('<i class="fas fa-save mr-2"></i>Lưu thay đổi');

            customerIdInput.val(customer.KhachHangID);
            $('#customer-company-name').val(customer.TenCongTy);
            $('#customer-contact-person').val(customer.NguoiLienHe);
            $('#customer-tax-code').val(customer.MaSoThue);
            $('#customer-email').val(customer.Email);
            $('#customer-phone').val(customer.SoDienThoai);
            $('#customer-mobile').val(customer.SoDiDong);
            $('#customer-fax').val(customer.SoFax);
            $('#customer-address').val(customer.DiaChi);
            priceSchemaSelect.val(customer.CoCheGiaID);
        }
    }

    // === DATA FETCHING & RENDERING ===
    function loadPriceSchemas() {
        $.ajax({
            url: 'api/get_price_schemas.php',
            dataType: 'json',
            success: function (schemas) {
                priceSchemaSelect.empty().append('<option value="">-- Chọn cơ chế giá --</option>');
                if (schemas && schemas.length > 0) {
                    schemas.forEach(schema => {
                        priceSchemaSelect.append(`<option value="${schema.CoCheGiaID}">${schema.TenCoChe} (${schema.MaCoChe})</option>`);
                    });
                }
            },
            error: () => showMessage('Không thể tải danh sách cơ chế giá.', false)
        });
    }

    function loadCustomers() {
        customerListBody.html('<tr><td colspan="4" class="text-center p-4">Đang tải dữ liệu...</td></tr>');
        $.ajax({
            url: 'api/get_all_customers.php', // This API needs to be created
            dataType: 'json',
            success: function (customers) {
                customerListBody.empty();
                if (customers && customers.length > 0) {
                    customers.forEach(c => {
                        const row = `
                            <tr data-customer-id="${c.KhachHangID}">
                                <td class="py-2 px-4 border-b font-semibold">${c.TenCongTy}</td>
                                <td class="py-2 px-4 border-b">${c.NguoiLienHe || ''}</td>
                                <td class="py-2 px-4 border-b text-sm text-gray-600">${c.Email || ''}</td>
                                <td class="py-2 px-4 border-b text-center">
                                    <button class="edit-btn text-blue-500 hover:text-blue-700 mr-3" title="Sửa"><i class="fas fa-edit"></i></button>
                                    <button class="delete-btn text-red-500 hover:text-red-700" title="Xóa"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        `;
                        customerListBody.append(row);
                        $(`tr[data-customer-id="${c.KhachHangID}"]`).data('customer', c);
                    });
                } else {
                    customerListBody.html('<tr><td colspan="4" class="text-center p-4">Chưa có khách hàng nào.</td></tr>');
                }
            },
            error: () => {
                customerListBody.html('<tr><td colspan="4" class="text-center p-4 text-red-500">Lỗi khi tải danh sách khách hàng.</td></tr>');
            }
        });
    }

    // === EVENT HANDLERS ===
    clearButton.on('click', function () {
        setFormState('add');
    });

    customerForm.on('submit', function (e) {
        e.preventDefault();
        const customerId = customerIdInput.val();
        const action = customerId ? 'update' : 'add';

        let formData = {
            action: action,
            KhachHangID: customerId,
            TenCongTy: $('#customer-company-name').val(),
            NguoiLienHe: $('#customer-contact-person').val(),
            MaSoThue: $('#customer-tax-code').val(),
            Email: $('#customer-email').val(),
            SoDienThoai: $('#customer-phone').val(),
            SoDiDong: $('#customer-mobile').val(),
            SoFax: $('#customer-fax').val(),
            DiaChi: $('#customer-address').val(),
            CoCheGiaID: priceSchemaSelect.val()
        };

        $.ajax({
            url: 'api/manage_customer.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    showMessage(res.message);
                    setFormState('add');
                    loadCustomers();
                } else {
                    showMessage(res.message, false);
                }
            },
            error: () => showMessage('Lỗi kết nối đến server.', false)
        });
    });

    customerListBody.on('click', '.edit-btn', function () {
        const customerData = $(this).closest('tr').data('customer');
        setFormState('edit', customerData);
        $('html, body').animate({ scrollTop: 0 }, 'slow');
    });

    customerListBody.on('click', '.delete-btn', function () {
        const customerData = $(this).closest('tr').data('customer');
        if (typeof showConfirmationModal === 'function') {
            showConfirmationModal(`Bạn có chắc muốn xóa khách hàng "${customerData.TenCongTy}"?`, function () {
                deleteCustomer(customerData.KhachHangID);
            });
        } else if (confirm(`Bạn có chắc muốn xóa khách hàng "${customerData.TenCongTy}"?`)) {
            deleteCustomer(customerData.KhachHangID);
        }
    });

    function deleteCustomer(id) {
        $.ajax({
            url: 'api/manage_customer.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: 'delete', KhachHangID: id }),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    showMessage(res.message);
                    loadCustomers();
                } else {
                    showMessage(res.message, false);
                }
            },
            error: () => showMessage('Lỗi kết nối đến server khi xóa.', false)
        });
    }

    // === INITIALIZATION ===
    function initializeCustomerPage() {
        loadPriceSchemas();
        loadCustomers();
        setFormState('add');
    }

    initializeCustomerPage();
});
