/**
 * File: assets/js/gia_cong_list.js
 * Description: JavaScript cho trang danh sách phiếu gia công mạ nhúng nóng
 */

function initGiaCongListPage(container) {
    console.log('[GIA_CONG_LIST] Khởi tạo trang danh sách');

    // State
    let currentPage = 1;
    const limit = 50;

    // Elements
    const tableBody = container.find('#gia-cong-table-body');
    const filterTrangThai = container.find('#filter-trang-thai');
    const filterTuNgay = container.find('#filter-tu-ngay');
    const filterDenNgay = container.find('#filter-den-ngay');
    const btnApplyFilter = container.find('#btn-apply-filter');
    const paginationContainer = container.find('#pagination-container');
    const paginationInfo = container.find('#pagination-info');

    // Stats elements
    const statDaXuat = container.find('#stat-da-xuat');
    const statDangGC = container.find('#stat-dang-gc');
    const statDaNhap = container.find('#stat-da-nhap');

    /**
     * Load dữ liệu phiếu gia công
     */
    function loadData() {
        tableBody.html('<tr><td colspan="10" class="px-6 py-10 text-center text-gray-500"><i class="fas fa-spinner fa-spin text-3xl mb-3"></i><p>Đang tải dữ liệu...</p></td></tr>');

        const params = {
            page: currentPage,
            limit: limit,
            trang_thai: filterTrangThai.val(),
            tu_ngay: filterTuNgay.val(),
            den_ngay: filterDenNgay.val()
        };

        $.ajax({
            url: 'api/get_danh_sach_gia_cong.php',
            method: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderTable(response.data);
                    renderPagination(response.pagination);
                    updateStats(response.stats);
                } else {
                    showError(response.message || 'Không thể tải dữ liệu');
                }
            },
            error: function(xhr) {
                console.error('Error loading data:', xhr);
                showError('Lỗi kết nối đến máy chủ');
            }
        });
    }

    /**
     * Render bảng dữ liệu
     */
    function renderTable(data) {
        if (!data || data.length === 0) {
            tableBody.html('<tr><td colspan="10" class="px-6 py-10 text-center text-gray-500"><i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i><p>Không có phiếu gia công nào</p></td></tr>');
            return;
        }

        let html = '';
        data.forEach((item, index) => {
            const stt = (currentPage - 1) * limit + index + 1;
            const statusClass = getStatusClass(item.TrangThai);
            const progressPercent = item.SoLuongXuat > 0 ? Math.round((item.SoLuongNhapVe / item.SoLuongXuat) * 100) : 0;
            const progressColor = progressPercent === 100 ? 'bg-green-500' : (progressPercent > 0 ? 'bg-yellow-500' : 'bg-gray-300');

            html += `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">${stt}</td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-blue-600">${item.MaPhieu}</div>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                        <a href="?page=chuanbi_hang_edit&id=${item.CBH_ID}" class="text-sm text-indigo-600 hover:text-indigo-900">
                            ${item.SoCBH || 'N/A'}
                        </a>
                    </td>
                    <td class="px-4 py-4">
                        <div class="text-sm text-gray-900">${item.MaSanPhamXuat}</div>
                        <div class="text-xs text-gray-500">${item.TenSPXuat || ''}</div>
                    </td>
                    <td class="px-4 py-4">
                        <div class="text-sm text-gray-900">${item.MaSanPhamNhan}</div>
                        <div class="text-xs text-gray-500">${item.TenSPNhan || ''}</div>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            ${item.SoLuongXuat}
                        </span>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex flex-col items-center">
                            <div class="text-xs font-medium text-gray-700 mb-1">${item.TienDoNhap}</div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="${progressColor} h-2 rounded-full" style="width: ${progressPercent}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">
                            ${item.TrangThai}
                        </span>
                    </td>
                    <td class="px-4 py-4 text-center text-sm text-gray-500">
                        ${item.NgayXuatFormatted}
                    </td>
                    <td class="px-4 py-4 text-center text-sm font-medium">
                        <a href="?page=gia_cong_view&id=${item.PhieuXuatGC_ID}" class="text-indigo-600 hover:text-indigo-900">
                            <i class="fas fa-eye mr-1"></i>Chi tiết
                        </a>
                    </td>
                </tr>
            `;
        });

        tableBody.html(html);
    }

    /**
     * Render pagination
     */
    function renderPagination(pagination) {
        const { page, totalPages, total } = pagination;

        // Update info
        const start = (page - 1) * limit + 1;
        const end = Math.min(page * limit, total);
        paginationInfo.html(`Hiển thị <span class="font-medium">${start}</span> đến <span class="font-medium">${end}</span> của <span class="font-medium">${total}</span> kết quả`);

        // Update buttons
        if (totalPages <= 1) {
            paginationContainer.html('');
            return;
        }

        let html = '';

        // Previous button
        html += `
            <button data-page="${page - 1}" class="pagination-btn relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${page === 1 ? 'opacity-50 cursor-not-allowed' : ''}" ${page === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>
        `;

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
                const activeClass = i === page ? 'z-10 bg-orange-50 border-orange-500 text-orange-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50';
                html += `
                    <button data-page="${i}" class="pagination-btn relative inline-flex items-center px-4 py-2 border text-sm font-medium ${activeClass}">
                        ${i}
                    </button>
                `;
            } else if (i === page - 3 || i === page + 3) {
                html += `<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>`;
            }
        }

        // Next button
        html += `
            <button data-page="${page + 1}" class="pagination-btn relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${page === totalPages ? 'opacity-50 cursor-not-allowed' : ''}" ${page === totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>
        `;

        paginationContainer.html(html);
    }

    /**
     * Update stats
     */
    function updateStats(stats) {
        statDaXuat.text(stats['Đã xuất'] || 0);
        statDangGC.text(stats['Đang gia công'] || 0);
        statDaNhap.text(stats['Đã nhập kho'] || 0);
    }

    /**
     * Get status class
     */
    function getStatusClass(status) {
        switch (status) {
            case 'Đã xuất':
                return 'bg-blue-100 text-blue-800';
            case 'Đang gia công':
                return 'bg-yellow-100 text-yellow-800';
            case 'Đã nhập kho':
                return 'bg-green-100 text-green-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    /**
     * Show error
     */
    function showError(message) {
        tableBody.html(`
            <tr>
                <td colspan="10" class="px-6 py-10 text-center text-red-500">
                    <i class="fas fa-exclamation-triangle text-4xl mb-3"></i>
                    <p>${message}</p>
                </td>
            </tr>
        `);
    }

    // Event listeners
    btnApplyFilter.on('click', function() {
        currentPage = 1;
        loadData();
    });

    paginationContainer.on('click', '.pagination-btn', function() {
        const page = parseInt($(this).data('page'));
        if (page && page !== currentPage && page > 0) {
            currentPage = page;
            loadData();
        }
    });

    // Initial load
    loadData();
}

// Export to global
window.initGiaCongListPage = initGiaCongListPage;
