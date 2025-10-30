/**
 * =================================================================================
 * MODULE QUẢN LÝ NHẬP KHO THÀNH PHẨM (TP) - PHIÊN BẢN HOÀN CHỈNH
 * =================================================================================
 * - [CẬP NHẬT] Bổ sung chức năng Thêm/Xóa sản phẩm trên trang tạo phiếu.
 * - [CẬP NHẬT] Thêm các hàm modal tiện ích để cải thiện trải nghiệm người dùng.
 * - Chức năng Tạo/Xem/Lưu phiếu nhập kho thành phẩm.
 * - Tích hợp bộ lọc và phân trang nâng cao vào trang lịch sử.
 * - Xử lý dữ liệu API linh hoạt.
 * - Giao diện người dùng động.
 * =================================================================================
 */

// =================================================================================
// --- CÁC HÀM TIỆN ÍCH VÀ MODAL (MỚI) ---
// =================================================================================

/**
 * Tạo và hiển thị một modal tùy chỉnh.
 * @param {string} id ID của modal.
 * @param {string} title Tiêu đề của modal.
 * @param {string} content Nội dung HTML của modal.
 * @param {boolean} showOkButton Hiển thị nút OK hay không.
 * @param {boolean} showCancelButton Hiển thị nút Hủy hay không.
 */
function createCustomModal(id, title, content, showOkButton = true, showCancelButton = false) {
    $(`#${id}`).remove();
    const modalHtml = `
        <div id="${id}" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
            <div class="relative p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 text-center">${title}</h3>
                    <div class="mt-2 px-7 py-3"><div class="text-sm text-gray-500">${content}</div></div>
                    <div class="items-center px-4 py-3 space-y-2 md:space-y-0 md:space-x-4 md:flex md:justify-center">
                        ${showOkButton ? `<button id="${id}-ok-btn" class="w-full md:w-auto px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-blue-700 focus:outline-none">OK</button>` : ''}
                        ${showCancelButton ? `<button id="${id}-cancel-btn" class="w-full md:w-auto px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none">Hủy</button>` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
    $('body').append(modalHtml);
    $(`#${id}-cancel-btn`).on('click', () => $(`#${id}`).remove());
}

/**
 * Hiển thị modal xác nhận hành động.
 * @param {string} message Nội dung câu hỏi xác nhận.
 * @param {function} onConfirm Hàm callback sẽ được gọi khi người dùng nhấn OK.
 */
function showConfirmationModal(message, onConfirm) {
    createCustomModal('confirmation-modal', 'Xác Nhận', message, true, true);
    $('#confirmation-modal-ok-btn').on('click', function() {
        $(`#confirmation-modal`).remove();
        if (onConfirm) onConfirm();
    });
}

/**
 * Hiển thị modal tìm kiếm sản phẩm để thêm vào phiếu.
 * @param {function} onProductAdd Callback được gọi khi một sản phẩm được chọn.
 */
function showProductSearchModal(onProductAdd) {
    const modalContent = `
        <div class="relative">
            <input type="text" id="product-search-input" placeholder="Gõ mã hoặc tên sản phẩm..." class="w-full p-2 border border-gray-300 rounded-md">
            <div id="product-search-results" class="absolute z-10 w-full bg-white border mt-1 rounded-md max-h-60 overflow-y-auto shadow-lg"></div>
        </div>
    `;
    createCustomModal('product-search-modal', 'Tìm và Thêm Sản Phẩm', modalContent, false, true);

    let searchTimeout;
    $('#product-search-input').on('keyup', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();
        const resultsContainer = $('#product-search-results');
        if (query.length < 2) {
            resultsContainer.empty();
            return;
        }
        searchTimeout = setTimeout(() => {
            resultsContainer.html('<div class="p-3 text-center text-gray-500">Đang tìm...</div>');
            $.getJSON('api/search_production_items.php', { query: query, type: 'TP' })
                .done(res => {
                    resultsContainer.empty();
                    if (res.success && res.data.length > 0) {
                        res.data.forEach(item => {
                            resultsContainer.append(`
                                <div class="p-3 border-b hover:bg-gray-100 cursor-pointer search-result-item" data-item='${JSON.stringify(item)}'>
                                    <p class="font-semibold text-blue-600">${item.variant_sku}</p>
                                    <p class="text-sm text-gray-600">${item.variant_name}</p>
                                </div>`);
                        });
                    } else {
                        resultsContainer.html('<div class="p-3 text-center text-gray-500">Không tìm thấy.</div>');
                    }
                })
                .fail(() => resultsContainer.html('<div class="p-3 text-center text-red-500">Lỗi khi tìm kiếm.</div>'));
        }, 300);
    });

    $('#product-search-results').on('click', '.search-result-item', function() {
        if (onProductAdd) onProductAdd($(this).data('item'));
        $('#product-search-modal').remove();
    });
}


// =================================================================================
// --- CÁC HÀM KHỞI TẠO TRANG ---
// =================================================================================

/**
 * Khởi tạo trang Danh sách chờ nhập kho Thành Phẩm (từ phiếu CBH).
 */
function initializeNhapKhoTPListPage() {
    const listBody = $('#nhapkho-tp-list-body');
    if (listBody.length === 0) return;
    listBody.html('<tr><td colspan="5" class="p-4 text-center">Đang tải...</td></tr>');

    $.getJSON('api/get_ready_cbh_for_tp_receipt.php', function(response) {
        if (response && response.success && Array.isArray(response.data) && response.data.length > 0) {
            listBody.empty();
            response.data.forEach(cbh => {
                const ngayGiao = cbh.NgayGiaoDuKien ? new Date(cbh.NgayGiaoDuKien).toLocaleDateString('vi-VN') : 'N/A';
                const row = `
                    <tr class="hover:bg-gray-50">
                        <td class="p-3 font-semibold text-blue-600">${cbh.SoYCSX || 'N/A'}</td>
                        <td class="p-3">${cbh.TenCongTy || 'N/A'}</td>
                        <td class="p-3">${cbh.TenDuAn || 'N/A'}</td>
                        <td class="p-3">${ngayGiao}</td>
                        <td class="p-3 text-center">
                            <button data-cbh-id="${cbh.CBH_ID}" data-type="${cbh.type}" class="create-receipt-btn action-btn bg-blue-500 text-white px-3 py-1 rounded-md text-xs hover:bg-blue-600">
                                <i class="fas fa-plus-circle mr-1"></i> Tạo Phiếu Nhập (${(cbh.type || '').toUpperCase()})
                            </button>
                        </td>
                    </tr>`;
                listBody.append(row);
            });
        } else {
            listBody.html('<tr><td colspan="5" class="p-4 text-center">Không có phiếu chuẩn bị hàng nào chờ nhập kho thành phẩm.</td></tr>');
        }
    }).fail(function() {
        listBody.html('<tr><td colspan="5" class="p-4 text-center text-red-500">Lỗi khi tải dữ liệu. Vui lòng thử lại.</td></tr>');
    });
}

/**
 * Khởi tạo trang Lịch sử Phiếu Nhập Kho TP (với bộ lọc và phân trang).
 */
function initializeDanhSachPNKTPPage() {
    let currentPage = 1;
    let totalPages = 1;
    const itemsPerPage = 15;

    function loadPNKTPList(page = 1) {
        currentPage = page;
        const filterParams = {
            page: currentPage,
            limit: itemsPerPage,
            so_phieu: $('#filter-so-phieu').val(),
            so_ycsx: $('#filter-ycsx').val(),
            tu_ngay: $('#filter-tu-ngay').val(),
            den_ngay: $('#filter-den-ngay').val()
        };
        const listBody = $('#pnk-tp-list-body');
        if (listBody.length === 0) return;
        listBody.html('<tr><td colspan="7" class="p-4 text-center"><i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...</td></tr>');
        $.ajax({
            url: 'api/get_list_pnk_tp_filtered.php',
            method: 'GET',
            data: filterParams,
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    displayPNKTPList(response.data, response.pagination);
                } else {
                    listBody.html('<tr><td colspan="7" class="p-4 text-center text-red-500">Lỗi: ' + (response.message || 'Dữ liệu không hợp lệ') + '</td></tr>');
                }
            },
            error: function() {
                listBody.html('<tr><td colspan="7" class="p-4 text-center text-red-500">Không thể kết nối đến máy chủ.</td></tr>');
            }
        });
    }

    function displayPNKTPList(data, pagination) {
        const listBody = $('#pnk-tp-list-body');
        listBody.empty();
        if (!Array.isArray(data) || data.length === 0) {
            listBody.html('<tr><td colspan="7" class="p-4 text-center">Không có dữ liệu phù hợp.</td></tr>');
            updatePagination({ current_page: 1, total_pages: 1, total_records: 0, start: 0, end: 0 });
            return;
        }
        data.forEach((pnk, index) => {
            const stt = (pagination.start || 0) + index;
            const ngayNhap = pnk.NgayNhap ? new Date(pnk.NgayNhap).toLocaleDateString('vi-VN') : 'N/A';
            const row = `
                <tr class="hover:bg-gray-50">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-500 sm:pl-6">${stt}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-blue-600">${pnk.SoPhieuNhapKho}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${ngayNhap}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${pnk.SoYCSX || 'N/A'}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${pnk.TenNguoiTao || 'N/A'}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${pnk.LyDoNhap}</td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-center text-sm font-medium sm:pr-6">
                        <button class="view-pnk-tp-btn text-indigo-600 hover:text-indigo-900" data-pnk-id="${pnk.PhieuNhapKhoID}">
                            <i class="fas fa-eye mr-1"></i>Xem
                        </button>
                    </td>
                </tr>`;
            listBody.append(row);
        });
        updatePagination(pagination);
    }

    function updatePagination(pagination) {
        if (!pagination) return;
        totalPages = pagination.total_pages || 1;
        $('#start-record').text(pagination.start || 0);
        $('#end-record').text(pagination.end || 0);
        $('#total-records').text(pagination.total_records || 0);
        const pageNumbers = $('#page-numbers');
        pageNumbers.empty();
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPage ? 'bg-blue-600 text-white' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50';
            pageNumbers.append(`<button class="page-number relative inline-flex items-center px-4 py-2 text-sm font-semibold ${activeClass}" data-page="${i}">${i}</button>`);
        }
        $('#prev-page').prop('disabled', currentPage === 1);
        $('#next-page').prop('disabled', currentPage === totalPages);
    }

    $(document)
        .off('click', '#apply-filter-btn').on('click', '#apply-filter-btn', () => loadPNKTPList(1))
        .off('click', '#reset-filter-btn').on('click', '#reset-filter-btn', function() {
            if ($('#filter-form').length) $('#filter-form')[0].reset();
            loadPNKTPList(1);
        })
        .off('click', '.page-number').on('click', '.page-number', function() { loadPNKTPList($(this).data('page')); })
        .off('click', '#prev-page').on('click', '#prev-page', () => { if (currentPage > 1) loadPNKTPList(currentPage - 1); })
        .off('click', '#next-page').on('click', '#next-page', () => { if (currentPage < totalPages) loadPNKTPList(currentPage + 1); });

    loadPNKTPList(1);
}

/**
 * Khởi tạo trang Tạo/Xem Phiếu Nhập Kho Thành Phẩm.
 */
function initializeNhapKhoTPCreatePage() {
    const params = new URLSearchParams(window.location.search);
    const pnkId = params.get('pnk_id');
    const cbhId = params.get('cbh_id');
    const type = params.get('type') || 'N/A';

    /**
     * Hàm chung để hiển thị dữ liệu lên form (cho cả chế độ Tạo và Xem).
     * @param {object} data - Dữ liệu phiếu nhập kho có cấu trúc { header, details }.
     * @param {boolean} isViewMode - Cờ xác định có phải chế độ chỉ xem hay không.
     */
    function renderPNKDetails(data, isViewMode = false) {
        const detailsBody = $('#nhapkho-tp-items-body');
        const formContainer = $('#pnk-tp-form');
        if (detailsBody.length === 0 || formContainer.length === 0) {
            console.error("LỖI: Thiếu các phần tử HTML cần thiết (#nhapkho-tp-items-body hoặc #pnk-tp-form).");
            return;
        }

        const header = data.header || {};
        const details = data.details || [];
        const currentType = type || header.Type || 'N/A';

        // Cập nhật thông tin chung
        $('#info-lydo-tp').text(header.LyDoNhap || `Nhập kho TP (${currentType.toUpperCase()}) từ YCSX ${header.SoYCSX || ''}`);
        $('#info-ycsx-tp').text(header.SoYCSX || 'N/A');

        if (isViewMode) {
            $('#info-nguoilap-tp').text(header.TenNguoiTao || 'N/A');
        } else {
            const tenNguoiLap = window.App?.currentUser?.fullName || 'Không xác định';
            $('#info-nguoilap-tp').text(tenNguoiLap);
        }

        let ngayNhap = header.NgayNhap ? new Date(header.NgayNhap) : new Date();
        $('#info-ngaynhap-tp').text(`Ngày ${ngayNhap.getDate()} tháng ${ngayNhap.getMonth() + 1} năm ${ngayNhap.getFullYear()}`);
        $('#info-sophieu-tp').text(header.SoPhieuNhapKho || 'Tạo tự động');

        formContainer.data({
            'pnk-id': header.PhieuNhapKhoID || null,
            'cbh-id': cbhId || header.CBH_ID || null,
            'type': currentType
        });

        detailsBody.empty();
        
        // *** THAY ĐỔI: Thêm cột Xóa vào bảng nếu không ở chế độ xem ***
        const tableHeaders = $('#nhapkho-tp-items-body').closest('table').find('thead tr');
        tableHeaders.find('.th-action').remove(); // Xóa header cũ nếu có
        if (!isViewMode) {
            tableHeaders.append('<th class="p-2 border th-action">Xóa</th>');
        }

        if (details.length === 0 && isViewMode) {
            detailsBody.html(`<tr><td colspan="8" class="p-4 text-center">Không có sản phẩm.</td></tr>`);
        } else {
            details.forEach((item, index) => {
                const actionCell = isViewMode ? '' : `
                    <td class="p-2 border text-center">
                        <button class="delete-item-btn text-red-500 hover:text-red-700">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>`;
                const row = `
                    <tr data-variant-id="${item.variant_id}">
                        <td class="p-2 border text-center">${index + 1}</td>
                        <td class="p-2 border">${item.MaThanhPham || 'N/A'}</td>
                        <td class="p-2 border">${item.TenThanhPham || 'N/A'}</td>
                        <td class="p-2 border text-center">${item.DonViTinh || 'N/A'}</td>
                        <td class="p-2 border text-center">${item.SoLuongYeuCau || 'N/A'}</td>
                        <td class="p-2 border">
                            <input type="number" class="w-full text-center p-1 border rounded so-luong-nhap" value="${item.SoLuongNhap || 0}" ${isViewMode ? 'readonly' : ''}>
                        </td>
                        <td class="p-2 border">
                            <input type="text" class="w-full p-1 border rounded vi-tri-kho" value="${item.ViTriKho || ''}" placeholder="Vị trí/Ghi chú" ${isViewMode ? 'readonly' : ''}>
                        </td>
                        ${actionCell}
                    </tr>`;
                detailsBody.append(row);
            });
        }
        
        // *** THAY ĐỔI: Thêm nút "Thêm Sản Phẩm" nếu không ở chế độ xem ***
        $('#add-product-btn-container').remove(); // Xóa nút cũ
        if (!isViewMode) {
            const addButtonHtml = `
                <div id="add-product-btn-container" class="mt-4 flex justify-end">
                    <button id="add-product-btn" type="button" class="action-btn bg-green-600 text-white">
                        <i class="fas fa-plus mr-2"></i> Thêm Sản Phẩm
                    </button>
                </div>`;
            formContainer.append(addButtonHtml);
        }

        // Ẩn/hiện các nút chức năng
        const exportContainer = $('#export-buttons-container');
        if (isViewMode && header.PhieuNhapKhoID) {
            $('#page-title').text(`Chi Tiết Phiếu Nhập Kho: ${header.SoPhieuNhapKho || ''}`);
            $('#save-nhapkho-tp-btn').hide();
            $('#export-pnk-tp-pdf-btn').data('pnk-tp-id', header.PhieuNhapKhoID);
            $('#export-pnk-tp-excel-btn').data('pnk-tp-id', header.PhieuNhapKhoID);
            exportContainer.show();
        } else {
            $('#page-title').text(`Tạo Phiếu Nhập Kho TP (${currentType.toUpperCase()})`);
            $('#save-nhapkho-tp-btn').show();
            exportContainer.hide();
        }
    }

    // --- Luồng xử lý chính ---
    if (pnkId) {
        $.getJSON(`api/get_pnk_tp_details.php?pnk_id=${pnkId}`, function(response) {
            if (response && response.success && response.data) {
                const dataForRenderer = {
                    header: response.data.header,
                    details: Array.isArray(response.data.items) ? response.data.items.map(item => ({
                        variant_id: item.variant_id, MaThanhPham: item.MaHang, TenThanhPham: item.TenSanPham,
                        DonViTinh: item.DonViTinh, SoLuongYeuCau: item.SoLuongTheoDonHang || 'N/A',
                        SoLuongNhap: item.SoLuong, ViTriKho: item.GhiChu || ''
                    })) : []
                };
                renderPNKDetails(dataForRenderer, true);
            } else {
                alert('Lỗi: ' + (response ? response.message : 'Dữ liệu không hợp lệ'));
            }
        }).fail(() => alert('Lỗi kết nối máy chủ.'));
    } else if (cbhId && type) {
        $.getJSON(`api/get_cbh_details_for_tp_receipt.php?cbh_id=${cbhId}&type=${type}`, function(response) {
            if (response && response.success && response.header && Array.isArray(response.items)) {
                const groupedItems = {};
                response.items.forEach(item => {
                    const variantId = item.variant_id;
                    const soLuong = parseInt(item.SoLuong, 10) || 0;
                    if (!variantId) return;
                    if (groupedItems[variantId]) {
                        groupedItems[variantId].SoLuongYeuCau += soLuong;
                        groupedItems[variantId].SoLuongNhap += soLuong;
                    } else {
                        groupedItems[variantId] = {
                            variant_id: variantId, MaThanhPham: item.MaHang, TenThanhPham: item.TenSanPham,
                            DonViTinh: item.DonViTinh, SoLuongYeuCau: soLuong, SoLuongNhap: soLuong, ViTriKho: ''
                        };
                    }
                });
                const finalDetails = Object.values(groupedItems);
                const dataForRenderer = {
                    header: { ...response.header, Type: type, CBH_ID: cbhId },
                    details: finalDetails
                };
                renderPNKDetails(dataForRenderer, false);
            } else {
                alert('Lỗi: ' + (response ? response.message : 'Dữ liệu không hợp lệ'));
            }
        }).fail(() => alert('Lỗi kết nối máy chủ.'));
    }
}


// =================================================================================
// --- CÁC HÀM XỬ LÝ SỰ KIỆN ---
// =================================================================================

/**
 * Xử lý sự kiện lưu Phiếu Nhập Kho Thành Phẩm.
 */
function handleSavePNKTP() {
    const form = $('#pnk-tp-form');
    if (form.length === 0) return alert("Lỗi cấu trúc HTML: Không tìm thấy #pnk-tp-form.");

    const dataToSave = {
        cbh_id: form.data('cbh-id'),
        items: []
    };

    $('#nhapkho-tp-items-body tr').each(function() {
        const row = $(this);
        const variantId = row.data('variant-id');
        const soLuongThucNhap = parseFloat(row.find('.so-luong-nhap').val());

        if (variantId && soLuongThucNhap > 0) {
            dataToSave.items.push({
                variant_id: variantId,
                soLuongThucNhap: soLuongThucNhap,
                ghiChu: row.find('.vi-tri-kho').val().trim()
            });
        }
    });

    if (dataToSave.items.length === 0) return alert('Vui lòng nhập số lượng cho ít nhất một sản phẩm.');
    
    const saveBtn = $('#save-nhapkho-tp-btn');
    saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...');

    $.ajax({
        url: 'api/save_phieunhapkho_tp.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(dataToSave),
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                alert('Lưu phiếu nhập kho thành công!');
                history.back();
            } else {
                alert('Lỗi khi lưu: ' + (response ? response.message : 'Phản hồi không hợp lệ.'));
            }
        },
        error: function() {
            alert('Không thể kết nối đến máy chủ. Vui lòng thử lại.');
        },
        complete: function() {
            saveBtn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Hoàn Tất Nhập Kho');
        }
    });
}

/**
 * Cập nhật lại số thứ tự trong bảng.
 */
function reindexTableRows() {
    $('#nhapkho-tp-items-body tr').each(function(index) {
        $(this).find('td:first').text(index + 1);
    });
}

// --- EVENT DELEGATION (Được quản lý tập trung) ---

$(document).ready(function() {
    // Điều hướng: Tạo phiếu từ danh sách chờ
    $(document).on('click', '.create-receipt-btn', function() {
        const cbhId = $(this).data('cbh-id');
        const type = $(this).data('type');
        if (!cbhId || !type) return;
        const url = `?page=nhapkho_tp_create&cbh_id=${cbhId}&type=${type}`;
        history.pushState(null, '', url);
        window.App?.handleRouting();
    });

    // Điều hướng: Xem phiếu từ lịch sử
    $(document).on('click', '.view-pnk-tp-btn', function() {
        const pnkId = $(this).data('pnk-id');
        if (!pnkId) return;
        const url = `?page=nhapkho_tp_create&pnk_id=${pnkId}`;
        history.pushState(null, '', url);
        window.App?.handleRouting();
    });

    // Chức năng: Lưu phiếu
    $(document).on('click', '#save-nhapkho-tp-btn', handleSavePNKTP);
    
    // *** SỰ KIỆN MỚI: Thêm sản phẩm ***
    $(document).on('click', '#add-product-btn', function() {
        showProductSearchModal(function(item) {
            const tableBody = $('#nhapkho-tp-items-body');
            let isExisting = false;
            tableBody.find('tr').each(function() {
                if ($(this).data('variant-id') == item.variant_id) {
                    isExisting = true;
                    $(this).find('.so-luong-nhap').focus();
                    return false;
                }
            });

            if (isExisting) {
                alert(`Sản phẩm "${item.variant_sku}" đã có trong danh sách.`);
            } else {
                const newRowHtml = `
                    <tr data-variant-id="${item.variant_id}">
                        <td class="p-2 border text-center"></td>
                        <td class="p-2 border">${item.variant_sku}</td>
                        <td class="p-2 border">${item.variant_name}</td>
                        <td class="p-2 border text-center">${item.unit || 'Cái'}</td>
                        <td class="p-2 border text-center">0</td>
                        <td class="p-2 border">
                            <input type="number" class="w-full text-center p-1 border rounded so-luong-nhap" value="1">
                        </td>
                        <td class="p-2 border">
                            <input type="text" class="w-full p-1 border rounded vi-tri-kho" placeholder="Vị trí/Ghi chú">
                        </td>
                        <td class="p-2 border text-center">
                            <button class="delete-item-btn text-red-500 hover:text-red-700"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>`;
                tableBody.append(newRowHtml);
                reindexTableRows();
            }
        });
    });

    // *** SỰ KIỆN MỚI: Xóa sản phẩm ***
    $(document).on('click', '.delete-item-btn', function() {
        const row = $(this).closest('tr');
        showConfirmationModal('Bạn có chắc chắn muốn xóa sản phẩm này?', function() {
            row.remove();
            reindexTableRows();
        });
    });

    // Chức năng: Xuất file
    $(document).on('click', '#export-pnk-tp-pdf-btn', function() {
        const pnkId = $(this).data('pnk-tp-id');
        if (pnkId) window.open(`api/export_pnk_tp_pdf.php?pnk_id=${pnkId}`, '_blank');
        else alert("Lỗi: Không tìm thấy ID của phiếu.");
    });
    $(document).on('click', '#export-pnk-tp-excel-btn', function() {
        const pnkId = $(this).data('pnk-tp-id');
        if (pnkId) window.location.href = `api/export_pnk_tp_excel.php?pnk_id=${pnkId}`;
        else alert("Lỗi: Không tìm thấy ID của phiếu.");
    });
    
    // Điều hướng: Nút quay lại
    $(document).on('click', '#back-to-tp-list-btn', function() {
        history.back();
    });
});