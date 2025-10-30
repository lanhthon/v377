/**
 * File: js/chuanbi_hang_list.js
 * Description: Quản lý toàn diện trang danh sách Phiếu Chuẩn Bị Hàng.
 * - Cột Trạng Thái và Cảnh Báo được đưa lên sau cột Hành Động.
 */
function initializeChuanBiHangListPage(mainContentContainer) {
    // === DOM Elements ===
    const tableBody = mainContentContainer.find('#chuanbihang-list-body');
    const filterStatus = mainContentContainer.find('#filter-status');
    const searchTerm = mainContentContainer.find('#filter-search');
    const startDate = mainContentContainer.find('#filter-start-date');
    const endDate = mainContentContainer.find('#filter-end-date');
    const applyBtn = mainContentContainer.find('#apply-filters-btn');
    const paginationContainer = mainContentContainer.find('#pagination-container');
    const paginationInfo = mainContentContainer.find('#pagination-info');
    const limitPerPageSelect = mainContentContainer.find('#limit-per-page');
    const exportBtn = mainContentContainer.find('#export-excel-btn');
    const filterTabs = mainContentContainer.find('.filter-tab');
    const overdueCountBadge = mainContentContainer.find('#overdue-count');

    // === State ===
    let currentPage = 1;
    let currentLimit = parseInt(limitPerPageSelect.val(), 10) || 200;
    let currentFilterType = 'all'; // 'all' or 'overdue'

    // === Helper Functions ===

    /**
     * Định dạng ngày từ chuỗi YYYY-MM-DD sang DD/MM/YYYY.
     * @param {string} dateString - Chuỗi ngày đầu vào.
     * @returns {string} - Chuỗi ngày đã định dạng hoặc 'N/A'.
     */
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'N/A';
            return date.toLocaleDateString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        } catch (e) {
            return 'N/A';
        }
    }

    /**
     * Tạo huy hiệu trạng thái với màu sắc tương ứng.
     * @param {string} status - Tên trạng thái.
     * @returns {string} - HTML của huy hiệu.
     */
    function createStatusBadge(status) {
        let badgeClass = 'bg-gray-100 text-gray-800'; // Default
        switch (status) {
            case 'Mới tạo':
                badgeClass = 'bg-yellow-100 text-yellow-800';
                break;
            case 'Đã chuẩn bị':
                badgeClass = 'bg-blue-100 text-blue-800';
                break;
            case 'Chờ xuất kho':
                badgeClass = 'bg-purple-100 text-purple-800';
                break;
            case 'Đã xuất kho':
                 badgeClass = 'bg-cyan-100 text-cyan-800';
                break;
            case 'Đã giao':
            case 'Đã giao hàng': // Handle both variations
                badgeClass = 'bg-green-100 text-green-800';
                break;
            case 'Đã hủy':
                badgeClass = 'bg-red-100 text-red-800';
                break;
        }
        return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${badgeClass}">${status || 'N/A'}</span>`;
    }

    /**
     * Tạo huy hiệu cảnh báo quá hạn.
     * @param {string} deliveryDateStr - Ngày giao hàng.
     * @param {string} status - Trạng thái hiện tại của phiếu.
     * @returns {string} - HTML của huy hiệu cảnh báo.
     */
    function createOverdueBadge(deliveryDateStr, status) {
        if (status === 'Đã giao' || status === 'Đã giao hàng') {
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Đã hoàn thành</span>';
        }
        if (status === 'Đã hủy') {
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Đã hủy</span>';
        }
        if (!deliveryDateStr) {
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Chưa có</span>';
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const deliveryDate = new Date(deliveryDateStr);
        deliveryDate.setHours(0, 0, 0, 0);

        const diffTime = deliveryDate - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays < 0) {
            return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-200 text-red-800">Quá hạn ${-diffDays} ngày</span>`;
        } else if (diffDays === 0) {
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-200 text-yellow-800">Giao hôm nay</span>';
        } else {
            return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Còn ${diffDays} ngày</span>`;
        }
    }
    
    // === Render Functions ===

    /**
     * Hiển thị dữ liệu lên bảng.
     * @param {Array} data - Mảng dữ liệu các phiếu.
     */
    function renderTable(data) {
        tableBody.empty();
        if (!data || data.length === 0) {
            tableBody.html('<tr><td colspan="10" class="text-center py-10 text-gray-500">Không tìm thấy phiếu nào.</td></tr>');
            return;
        }

        data.forEach((phieu, index) => {
            const stt = (currentPage - 1) * currentLimit + index + 1;
            const ngayTao = formatDate(phieu.NgayTao);
            const ngayGiao = formatDate(phieu.NgayGiao);
            const statusBadge = createStatusBadge(phieu.TrangThai);
            const overdueBadge = createOverdueBadge(phieu.NgayGiao, phieu.TrangThai);
            
            let actionButtonHTML = '';
            if (phieu.TrangThai === 'Mới tạo') {
                actionButtonHTML = `<button data-id="${phieu.CBH_ID}" class="process-btn text-blue-600 hover:text-blue-900 font-medium" title="Tính toán & Chuẩn bị hàng"><i class="fas fa-cogs mr-1"></i>Chuẩn bị</button>`;
            } else {
                actionButtonHTML = `<a href="?page=chuanbi_hang_edit&id=${phieu.CBH_ID}" class="text-indigo-600 hover:text-indigo-900 font-medium" title="Xem chi tiết"><i class="fas fa-eye mr-1"></i>Xem</a>`;
            }

            const row = `
                <tr class="hover:bg-gray-50" id="row-${phieu.CBH_ID}">
                    <td class="px-6 py-4 text-center text-sm text-gray-500">${stt}</td>
                    <td class="px-6 py-4 text-center text-sm">${actionButtonHTML}</td>
                    <td class="px-6 py-4 text-center">${statusBadge}</td>
                    <td class="px-6 py-4 text-center">${overdueBadge}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${phieu.SoYCSX || 'N/A'}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${phieu.MaCongTy || 'N/A'}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${phieu.TenDuAn || 'N/A'}</td>
                    <td class="px-6 py-4 text-center text-sm text-gray-600">${ngayTao}</td>
                    <td class="px-6 py-4 text-center text-sm text-gray-600">${ngayGiao}</td>
                    <td class="px-6 py-4 font-medium text-gray-900 text-sm">${phieu.SoCBH || 'N/A'}</td>
                </tr>
            `;
            tableBody.append(row);
        });
    }

    /**
     * Hiển thị thông tin phân trang.
     * @param {number} totalItems - Tổng số mục.
     */
    function renderPaginationInfo(totalItems) {
        if (totalItems === undefined || totalItems === null) {
            paginationInfo.text('');
            return;
        }
        
        if (totalItems === 0) {
            paginationInfo.text('Không tìm thấy mục nào');
            return;
        }
        const startItem = (currentPage - 1) * currentLimit + 1;
        const endItem = Math.min(startItem + currentLimit - 1, totalItems);
        paginationInfo.text(`Hiển thị ${startItem} đến ${endItem} của ${totalItems} mục`);
    }

    /**
     * Hiển thị các nút phân trang.
     * @param {number} totalPages - Tổng số trang.
     */
    function renderPagination(totalPages) {
        paginationContainer.empty();
        if (totalPages <= 1) return;
    
        const maxPagesToShow = 5;
        let startPage, endPage;
    
        if (totalPages <= maxPagesToShow) {
            startPage = 1;
            endPage = totalPages;
        } else {
            const maxPagesBeforeCurrent = Math.floor(maxPagesToShow / 2);
            const maxPagesAfterCurrent = Math.ceil(maxPagesToShow / 2) - 1;
            if (currentPage <= maxPagesBeforeCurrent) {
                startPage = 1;
                endPage = maxPagesToShow;
            } else if (currentPage + maxPagesAfterCurrent >= totalPages) {
                startPage = totalPages - maxPagesToShow + 1;
                endPage = totalPages;
            } else {
                startPage = currentPage - maxPagesBeforeCurrent;
                endPage = currentPage + maxPagesAfterCurrent;
            }
        }
    
        let paginationHTML = '<nav class="flex items-center space-x-1">';
        
        paginationHTML += `<a href="#" data-page="${currentPage - 1}" class="pagination-link relative inline-flex items-center px-3 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${currentPage === 1 ? 'pointer-events-none opacity-50' : ''}"><i class="fas fa-chevron-left"></i></a>`;
    
        if (startPage > 1) {
            paginationHTML += `<a href="#" data-page="1" class="pagination-link relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>`;
            if (startPage > 2) {
                paginationHTML += `<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>`;
            }
        }
    
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPage ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50';
            paginationHTML += `<a href="#" data-page="${i}" class="pagination-link relative inline-flex items-center px-4 py-2 border text-sm font-medium ${activeClass}">${i}</a>`;
        }
    
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += `<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>`;
            }
            paginationHTML += `<a href="#" data-page="${totalPages}" class="pagination-link relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">${totalPages}</a>`;
        }
    
        paginationHTML += `<a href="#" data-page="${currentPage + 1}" class="pagination-link relative inline-flex items-center px-3 py-2 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${currentPage === totalPages ? 'pointer-events-none opacity-50' : ''}"><i class="fas fa-chevron-right"></i></a>`;
        paginationHTML += '</nav>';
        paginationContainer.html(paginationHTML);
    }

    /**
     * Tải danh sách phiếu từ API.
     * @param {boolean} isInitialLoad - Cờ xác định có phải lần tải đầu tiên không.
     */
    function loadPhieuChuanBiHang(isInitialLoad = false) {
        tableBody.html('<tr><td colspan="10" class="text-center py-10"><i class="fas fa-spinner fa-spin fa-3x text-gray-400"></i></td></tr>');
        
        const params = {
            status: filterStatus.val(),
            search: searchTerm.val(),
            start_date: startDate.val(),
            end_date: endDate.val(),
            page: currentPage,
            limit: currentLimit,
            filter_type: currentFilterType
        };

        $.ajax({
            url: 'api/get_chuanbihang_list.php',
            method: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderTable(response.data);
                    renderPagination(response.pagination.totalPages);
                    renderPaginationInfo(response.pagination.totalItems);
                    
                    if (isInitialLoad && response.statuses) {
                        filterStatus.find('option:not(:first)').remove();
                        response.statuses.forEach(status => {
                            filterStatus.append(`<option value="${status}">${status}</option>`);
                        });
                    }

                    if (response.overdueCount > 0) {
                        overdueCountBadge.text(response.overdueCount).removeClass('hidden').addClass('inline-flex');
                    } else {
                        overdueCountBadge.addClass('hidden');
                    }
                } else {
                    tableBody.html(`<tr><td colspan="10" class="text-center py-10 text-red-500">${response.message || 'Không thể tải dữ liệu.'}</td></tr>`);
                }
            },
            error: function() {
                tableBody.html('<tr><td colspan="10" class="text-center py-10 text-red-500">Lỗi kết nối đến máy chủ.</td></tr>');
            }
        });
    }

     /**
     * Xuất dữ liệu ra file Excel.
     */
    function exportToExcel() {
        const button = exportBtn;
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang xuất...');

        const params = {
            status: filterStatus.val(),
            search: searchTerm.val(),
            start_date: startDate.val(),
            end_date: endDate.val(),
            filter_type: currentFilterType,
            limit: 10000 
        };

        $.ajax({
            url: 'api/get_chuanbihang_list.php',
            method: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    const dataToExport = response.data.map((item, index) => ({
                        'STT': index + 1,
                        'Trạng Thái': item.TrangThai,
                        'Số Đơn Gốc (YCSX)': item.SoYCSX,
                        'Mã Khách Hàng': item.MaCongTy,
                        'Tên Dự Án': item.TenDuAn,
                        'Ngày Tạo': formatDate(item.NgayTao),
                        'Ngày Giao': formatDate(item.NgayGiao),
                        'Số Phiếu CBH': item.SoCBH
                    }));

                    const worksheet = XLSX.utils.json_to_sheet(dataToExport);
                    const workbook = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(workbook, worksheet, "DanhSachCBH");
                    XLSX.writeFile(workbook, "DanhSachChuanBiHang.xlsx");
                } else {
                    showMessageModal('Không có dữ liệu để xuất.', 'info');
                }
            },
            error: function() {
                showMessageModal('Lỗi khi lấy dữ liệu để xuất Excel.', 'error');
            },
            complete: function() {
                button.prop('disabled', false).html('<i class="fas fa-file-excel mr-2"></i>Xuất Excel');
            }
        });
    }

    // === Event Listeners ===
    
    applyBtn.on('click', function() {
        currentPage = 1;
        loadPhieuChuanBiHang();
    });
    
    searchTerm.on('keyup', function(e) {
        if (e.key === 'Enter') {
            currentPage = 1;
            loadPhieuChuanBiHang();
        }
    });
    
    filterTabs.on('click', function(e) {
        e.preventDefault();
        const filter = $(this).data('filter');
        if (filter === currentFilterType) return;

        currentFilterType = filter;
        currentPage = 1;

        filterTabs.removeClass('border-indigo-500 text-indigo-600').addClass('border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300');
        $(this).removeClass('border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300').addClass('border-indigo-500 text-indigo-600');

        if (filter === 'overdue') {
            filterStatus.val('').prop('disabled', true);
            startDate.val('');
            endDate.val('');
            searchTerm.val('');
        } else {
            filterStatus.prop('disabled', false);
        }

        loadPhieuChuanBiHang();
    });

    paginationContainer.on('click', '.pagination-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && page !== currentPage) {
            currentPage = page;
            loadPhieuChuanBiHang();
        }
    });

    limitPerPageSelect.on('change', function() {
        currentLimit = parseInt($(this).val(), 10);
        currentPage = 1;
        loadPhieuChuanBiHang();
    });

    exportBtn.on('click', exportToExcel);

    tableBody.on('click', '.process-btn', function() {
        const button = $(this);
        const cbhId = button.data('id');

        showConfirmationModal(`Bạn có chắc muốn thực hiện "Chuẩn bị hàng" cho phiếu ID ${cbhId} không?`, () => {
            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: 'api/process_cbh_details_CBH.php',
                method: 'POST',
                data: { cbh_id: cbhId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showMessageModal(response.message, 'success');
                        loadPhieuChuanBiHang();
                    } else {
                        // Kiểm tra xem có mảng 'errors' chi tiết và không rỗng không
                        if (response.errors && Array.isArray(response.errors) && response.errors.length > 0) {
                            
                            // Bắt đầu xây dựng thông điệp lỗi chi tiết bằng HTML
                            let detailedErrorMessage = (response.message || 'Lỗi khi xử lý, vui lòng kiểm tra:') + 
                                                       '<ul class="mt-2 list-disc list-inside text-left">';

                            // Lặp qua từng lỗi trong mảng và thêm vào danh sách
                            response.errors.forEach(error => {
                                // Sử dụng template literals để dễ dàng chèn biến vào chuỗi
                                detailedErrorMessage += `<li>${error}</li>`; 
                            });

                            detailedErrorMessage += '</ul>';

                            // Hiển thị modal với thông điệp đã được định dạng
                            showMessageModal(detailedErrorMessage, 'error');

                        } else {
                            // Nếu không có mảng lỗi chi tiết, hiển thị thông báo chung như cũ
                            showMessageModal(response.message || 'Có lỗi xảy ra.', 'error');
                        }
                    }
                },
                error: function() {
                     showMessageModal('Không thể kết nối đến máy chủ.', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).html('<i class="fas fa-cogs mr-1"></i>Chuẩn bị');
                }
            });
        });
    });

    // === Initial Load ===
    loadPhieuChuanBiHang(true);
}