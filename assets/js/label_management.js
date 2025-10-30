// js/label_management.js

function initializeLabelManagementPage(mainContentContainer) {
    const tableBody = mainContentContainer.find('#labels-table-body');
    const modal = $('#label-modal');
    const modalTitle = modal.find('#modal-title');
    const labelForm = modal.find('#label-form');
    const labelIdInput = modal.find('#label-id');
    const labelKeyInput = modal.find('#label-key');
    const labelViInput = modal.find('#label-vi');
    const labelZhInput = modal.find('#label-zh');
    const labelEnInput = modal.find('#label-en');
    const cancelBtn = modal.find('#modal-cancel-btn');

    // Tải và hiển thị danh sách nhãn
    function loadLabels() {
        tableBody.html('<tr><td colspan="6" class="text-center py-10"><i class="fas fa-spinner fa-spin fa-3x"></i></td></tr>');
        
        $.ajax({
            url: 'api/labels_handler.php?action=get_all',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                tableBody.empty();
                if (response.success && response.data.length > 0) {
                    response.data.forEach((label, index) => {
                        const row = $(`
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-center">${index + 1}</td>
                                <td class="px-6 py-4 font-mono text-sm text-red-600">${label.label_key}</td>
                                <td class="px-6 py-4">${label.label_vi}</td>
                                <td class="px-6 py-4">${label.label_zh}</td>
                                <td class="px-6 py-4">${label.label_en}</td>
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <button class="edit-btn text-indigo-600 hover:text-indigo-900" title="Sửa"><i class="fas fa-edit"></i></button>
                                </td>
                            </tr>
                        `);
                        row.find('.edit-btn').on('click', () => openEditModal(label));
                        tableBody.append(row);
                    });
                } else {
                    tableBody.html('<tr><td colspan="6" class="text-center py-10">Không có dữ liệu.</td></tr>');
                }
            },
            error: () => {
                tableBody.html('<tr><td colspan="6" class="text-center py-10 text-red-500">Lỗi khi tải dữ liệu.</td></tr>');
            }
        });
    }

    // Mở modal để sửa
    function openEditModal(data) {
        labelForm[0].reset();
        modalTitle.text('Chỉnh sửa Nhãn');
        labelIdInput.val(data.id);
        labelKeyInput.val(data.label_key);
        // Sử dụng .val() đơn giản cho input
        labelViInput.val(data.label_vi);
        labelZhInput.val(data.label_zh);
        labelEnInput.val(data.label_en);
        modal.removeClass('hidden');
    }

    // Đóng modal
    function closeModal() {
        modal.addClass('hidden');
    }

    // Xử lý sự kiện submit form (chỉ còn chức năng update)
    function handleFormSubmit(e) {
        e.preventDefault();
        const labelData = {
            id: labelIdInput.val(),
            // Lấy giá trị từ input
            label_vi: labelViInput.val().trim(),
            label_zh: labelZhInput.val().trim(),
            label_en: labelEnInput.val().trim(),
        };

        const url = 'api/labels_handler.php?action=update';
        
        $.ajax({
            url: url,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(labelData),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    App.showMessageModal(response.message, 'success');
                    closeModal();
                    loadLabels();
                } else {
                    App.showMessageModal(response.message, 'error');
                }
            },
            error: function() {
                App.showMessageModal('Đã có lỗi xảy ra.', 'error');
            }
        });
    }

    // --- Gán sự kiện ---
    cancelBtn.on('click', closeModal);
    labelForm.on('submit', handleFormSubmit);

    // --- Khởi tạo ---
    loadLabels();
}