/**
 * assets/js/actions.js
 * Chứa logic cho các hành động sau khi chốt đơn hàng như
 * tạo YCSX, BBGH, CCCL, PXK.
 */
$(document).ready(function () {
    const mainContentContainer = $('#main-content-container');

    // Hàm hiển thị thông báo chung (giả định đã có trong main.js)
    function showActionMessage(message, type = 'info') {
        if (typeof showMessageModal === 'function') {
            showMessageModal(message, type);
        } else {
            alert(message);
        }
    }

    // Hàm hiển thị modal xác nhận (giả định đã có trong main.js)
    function showActionConfirmation(message, callback) {
        if (typeof showConfirmationModal === 'function') {
            showConfirmationModal(message, callback);
        } else {
            if (confirm(message)) {
                callback();
            }
        }
    }

    /**
     * Hàm xử lý chung cho việc tạo và xem các loại tài liệu.
     * @param {string} action - 'create' hoặc 'view'
     * @param {string} docType - Loại tài liệu (ví dụ: 'ycsx', 'bbgh', 'pxk', 'cccl')
     * @param {number} id - ID của Báo giá (khi tạo) hoặc ID của tài liệu (khi xem)
     */
    function handleDocumentAction(action, docType, id) {
        const docConfig = {
            ycsx: { name: 'Yêu cầu sản xuất', createApi: 'create_ycsx.php', viewUrl: 'ycsx_view.php', viewIdParam: 'id' },
            bbgh: { name: 'Biên bản giao hàng', createApi: 'create_bbgh.php', viewUrl: 'bbgh_view.php', viewIdParam: 'id' },
            pxk: { name: 'Phiếu xuất kho', createApi: 'create_pxk.php', viewUrl: 'pxk_view.php', viewIdParam: 'id' },
            cccl: { name: 'Chứng chỉ chất lượng', createApi: 'create_cccl.php', viewUrl: 'cccl_view.php', viewIdParam: 'id' }
        };

        const config = docConfig[docType];
        if (!config) {
            showActionMessage('Loại tài liệu không xác định.', 'error');
            return;
        }

        if (action === 'create') {
            showActionConfirmation(`Bạn có chắc muốn tạo ${config.name} cho báo giá này?`, function () {
                const loadingOverlay = $('<div class="loading-overlay fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center"><div class="text-white text-lg">Đang xử lý...</div></div>').appendTo('body');

                $.ajax({
                    url: `api/${config.createApi}`,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ baoGiaID: id }),
                    dataType: 'json',
                    success: function (res) {
                        if (res.success) {
                            showActionMessage(`${config.name} đã được tạo thành công!`, 'success');
                            // Tải lại trang báo giá để cập nhật trạng thái nút
                            const currentUrl = new URL(window.location.href);
                            loadPage(currentUrl.pathname + currentUrl.search, initializeQuoteCreatePage);
                        } else {
                            showActionMessage(`Lỗi khi tạo ${config.name}: ${res.message || 'Lỗi không xác định.'}`, 'error');
                        }
                    },
                    error: function (xhr) {
                        console.error(`Lỗi AJAX khi tạo ${config.name}:`, xhr.responseText);
                        showActionMessage(`Lỗi server khi tạo ${config.name}. Vui lòng kiểm tra console (F12).`, 'error');
                    },
                    complete: function () {
                        loadingOverlay.remove();
                    }
                });
            });
        } else if (action === 'view') {
            const newPageUrl = `pages/${config.viewUrl}?${config.viewIdParam}=${id}`;
            const stateUrl = `?page=${docType}_view&id=${id}`;

            // Sử dụng các hàm toàn cục từ main.js để điều hướng
            if (typeof loadPage === 'function' && typeof history.pushState === 'function') {
                history.pushState({ page: newPageUrl }, `Xem ${config.name}`, stateUrl);
                // Giả sử có một hàm khởi tạo chung hoặc riêng cho từng trang xem
                const initializer = window[`initialize${docType.toUpperCase()}ViewPage`] || null;
                loadPage(newPageUrl, initializer);
            } else {
                window.open(newPageUrl, '_blank');
            }
        }
    }

    // Gán sự kiện cho các nút action, sử dụng event delegation
    mainContentContainer.on('click', '[data-action]', function () {
        const button = $(this);
        const action = button.data('action'); // 'create' or 'view'
        const docType = button.data('doc-type'); // 'ycsx', 'bbgh', etc.
        const id = button.data('id'); // BaoGiaID for create, DocID for view

        if (action && docType && id) {
            handleDocumentAction(action, docType, id);
        }
    });
});
