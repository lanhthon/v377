<div class="p-4 sm:p-6 lg:p-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Lịch Sử Nhập Kho Thành Phẩm</h1>
            <p class="mt-2 text-sm text-gray-700">Danh sách các phiếu nhập kho thành phẩm đã được tạo.</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <button id="export-excel-btn" type="button" class="inline-flex items-center justify-center rounded-md border border-transparent bg-gray-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-700 mr-2">
                <i class="fas fa-file-excel mr-2"></i>Xuất Excel
            </button>
            <button id="create-pnk-tp-sx-btn" type="button" class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 mr-2">
                <i class="fas fa-industry mr-2"></i>Nhập từ SX
            </button>
            <button id="create-pnk-tp-ngoai-btn" type="button" class="inline-flex items-center justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700">
                <i class="fas fa-plus mr-2"></i>Tạo Phiếu Nhập Ngoài
            </button>
        </div>
    </div>

    <div class="mt-6 bg-white shadow rounded-lg p-4">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Bộ lọc tìm kiếm</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="filter-so-phieu" class="block text-sm font-medium text-gray-700">Số Phiếu Nhập Kho</label>
                <input type="text" id="filter-so-phieu" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="VD: PNK-2025-0001">
            </div>
            
            <div>
                <label for="filter-ycsx" class="block text-sm font-medium text-gray-700">Số YCSX</label>
                <input type="text" id="filter-ycsx" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="VD: YCSX-2025-001">
            </div>
            
            <div>
                <label for="filter-tu-ngay" class="block text-sm font-medium text-gray-700">Từ ngày</label>
                <input type="date" id="filter-tu-ngay" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>
            
            <div>
                <label for="filter-den-ngay" class="block text-sm font-medium text-gray-700">Đến ngày</label>
                <input type="date" id="filter-den-ngay" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>
        </div>
        
        <div class="mt-4 flex gap-2">
            <button id="apply-filter-btn" type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-search mr-2"></i>Tìm kiếm
            </button>
            <button id="reset-filter-btn" type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-redo mr-2"></i>Đặt lại
            </button>
        </div>
    </div>

    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">STT</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Số Phiếu</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Ngày Nhập</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">YCSX Gốc</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Người Tạo</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Lý Do</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-center">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody id="pnk-tp-list-body" class="divide-y divide-gray-200 bg-white">
                            <tr><td colspan="7" class="p-4 text-center">Đang tải dữ liệu...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="pagination-container" class="mt-6 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Hiển thị <span id="start-record" class="font-medium">0</span> đến <span id="end-record" class="font-medium">0</span> trong tổng số <span id="total-records" class="font-medium">0</span> kết quả
                </p>
            </div>
            <div>
                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                    <button id="prev-page" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                        <span class="sr-only">Previous</span>
                        <i class="fas fa-chevron-left h-5 w-5"></i>
                    </button>
                    <div id="page-numbers" class="inline-flex">
                        </div>
                    <button id="next-page" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                        <span class="sr-only">Next</span>
                        <i class="fas fa-chevron-right h-5 w-5"></i>
                    </button>
                </nav>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let currentPage = 1;
    let totalPages = 1;
    let itemsPerPage = 10;
    let filterParams = {};

    // Initialize page - Load data without filters first
    loadPNKTPList();

    // Load PNK TP list with filters and pagination
    function loadPNKTPList(page = 1) {
        currentPage = page;
        
        // Build filter parameters
        filterParams = {
            page: currentPage,
            limit: itemsPerPage,
            so_phieu: $('#filter-so-phieu').val(),
            so_ycsx: $('#filter-ycsx').val(),
            tu_ngay: $('#filter-tu-ngay').val(),
            den_ngay: $('#filter-den-ngay').val()
        };

        const listBody = $('#pnk-tp-list-body');
        listBody.html('<tr><td colspan="7" class="p-4 text-center">Đang tải dữ liệu...</td></tr>');

        $.ajax({
            url: 'api/get_list_pnk_tp_filtered.php',
            method: 'GET',
            data: filterParams,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayPNKTPList(response.data, response.pagination);
                } else {
                    // Nếu API mới không tồn tại, thử gọi API cũ
                    loadOldAPI();
                }
            },
            error: function() {
                // Fallback to old API
                loadOldAPI();
            }
        });
    }

    // Fallback to old API without filters
    function loadOldAPI() {
        $.getJSON('api/get_list_pnk_tp.php', function(response) {
            if (response.success && response.data.length > 0) {
                const listBody = $('#pnk-tp-list-body');
                listBody.empty();
                response.data.forEach(pnk => {
                    const ngayNhap = pnk.NgayNhap ? new Date(pnk.NgayNhap).toLocaleDateString('vi-VN') : 'N/A';
                    const row = `
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">${pnk.SoPhieuNhapKho}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${ngayNhap}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${pnk.SoYCSX || 'N/A'}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${pnk.TenNguoiTao || 'N/A'}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${pnk.LyDoNhap}</td>
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-center text-sm font-medium sm:pr-6">
                                <button class="view-pnk-tp-btn text-indigo-600 hover:text-indigo-900" data-pnk-id="${pnk.PhieuNhapKhoID}">
                                    <i class="fas fa-eye mr-1"></i>Xem
                                </button>
                            </td>
                        </tr>
                    `;
                    listBody.append(row);
                });
                // Hide pagination if using old API
                $('#pagination-container').hide();
            } else {
                $('#pnk-tp-list-body').html('<tr><td colspan="7" class="p-4 text-center">Chưa có phiếu nhập kho thành phẩm nào được tạo.</td></tr>');
            }
        });
    }

    // Display PNK TP list in table
    function displayPNKTPList(data, pagination) {
        const listBody = $('#pnk-tp-list-body');
        listBody.empty();

        if (data.length === 0) {
            listBody.html('<tr><td colspan="7" class="p-4 text-center">Không có dữ liệu phù hợp</td></tr>');
            updatePagination({current_page: 1, total_pages: 1, total_records: 0, start: 0, end: 0});
            return;
        }

        data.forEach((pnk, index) => {
            const stt = (currentPage - 1) * itemsPerPage + index + 1;
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
                </tr>
            `;
            listBody.append(row);
        });

        updatePagination(pagination);
    }

    // Update pagination controls
    function updatePagination(pagination) {
        totalPages = pagination.total_pages || 1;
        
        $('#start-record').text(pagination.start || 0);
        $('#end-record').text(pagination.end || 0);
        $('#total-records').text(pagination.total_records || 0);

        // Update page numbers
        const pageNumbers = $('#page-numbers');
        pageNumbers.empty();
        
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }

        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPage ? 'bg-blue-600 text-white' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50';
            pageNumbers.append(`
                <button class="page-number relative inline-flex items-center px-4 py-2 text-sm font-semibold ${activeClass} focus:z-20 focus:outline-offset-0" data-page="${i}">
                    ${i}
                </button>
            `);
        }

        $('#prev-page').prop('disabled', currentPage === 1);
        $('#next-page').prop('disabled', currentPage === totalPages);
    }

    // Event handlers
    $('#apply-filter-btn').on('click', function() {
        loadPNKTPList(1);
    });

    $('#reset-filter-btn').on('click', function() {
        $('#filter-so-phieu').val('');
        $('#filter-ycsx').val('');
        $('#filter-tu-ngay').val('');
        $('#filter-den-ngay').val('');
        loadPNKTPList(1);
    });

    // Pagination clicks
    $(document).on('click', '.page-number', function() {
        const page = $(this).data('page');
        loadPNKTPList(page);
    });

    $('#prev-page').on('click', function() {
        if (currentPage > 1) {
            loadPNKTPList(currentPage - 1);
        }
    });

    $('#next-page').on('click', function() {
        if (currentPage < totalPages) {
            loadPNKTPList(currentPage + 1);
        }
    });

    // Export to Excel
    $('#export-excel-btn').on('click', function() {
        const params = new URLSearchParams(filterParams);
        params.delete('page');
        params.delete('limit');
        const url = `api/export_list_pnk_tp_excel.php?${params.toString()}`;
        window.location.href = url;
    });

    // View PNK detail - GIỮ NGUYÊN NHƯ CŨ
    $(document).on('click', '.view-pnk-tp-btn', function() {
        const pnkId = $(this).data('pnk-id');
        const url = `?page=nhapkho_tp_create&pnk_id=${pnkId}`;
        history.pushState({ page: 'nhapkho_tp_create', pnk_id: pnkId }, '', url);
        window.App.handleRouting();
    });

    // Create buttons - GIỮ NGUYÊN NHƯ CŨ
    $(document).on('click', '#create-pnk-tp-ngoai-btn', function() {
        const pageName = 'nhapkho_tp_ngoai_create';
        history.pushState({ page: pageName }, '', `?page=${pageName}`);
        window.App.handleRouting();
    });

    $(document).on('click', '#create-pnk-tp-sx-btn', function() {
        const pageName = 'nhapkho_tp_list'; 
        history.pushState({ page: pageName }, '', `?page=${pageName}`);
        window.App.handleRouting();
    });
});
</script>