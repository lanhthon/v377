<div class="p-6 bg-gray-50 min-h-full">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800">Quản Lý Lệnh Sản Xuất</h1>
        <div class="flex items-center space-x-3">
            <button id="export-excel-btn-lsx" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 font-semibold shadow-sm">
                <i class="fas fa-file-excel mr-2"></i>Xuất Excel
            </button>
            <a href="?page=lenhsanxuat_create_stock" class="bg-blue-600 text-white px-5 py-2 rounded-md hover:bg-blue-700 font-semibold shadow-sm">
                <i class="fas fa-plus-circle mr-2"></i>Tạo Lệnh Lưu Kho
            </a>
        </div>
    </div>
    
    <!-- Tab Filters -->
    <div class="mb-4">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <a href="#" data-filter="all" class="filter-tab-lsx whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-indigo-500 text-indigo-600">
                    Tất cả
                </a>
                <a href="#" data-filter="overdue" class="filter-tab-lsx whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Quá hạn
                    <span id="overdue-count-lsx" class="hidden ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium md:inline-block bg-red-100 text-red-800">0</span>
                </a>
            </nav>
        </div>
    </div>

    <!-- Sticky Filter Bar -->
    <style>
        #sticky-filter-bar-lsx {
            position: sticky;
            top: 0;
            z-index: 10;
        }
    </style>
    <div id="sticky-filter-bar-lsx" class="bg-white p-4 rounded-lg shadow-sm mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <div class="lg:col-span-2">
                <label for="search-filter" class="text-sm font-medium text-gray-700">Tìm kiếm:</label>
                <input type="text" id="search-filter" placeholder="Số LSX, Số YCSX..." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
            </div>
            <div>
                <label for="status-filter" class="text-sm font-medium text-gray-700">Trạng thái:</label>
                <select id="status-filter" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">Tất cả</option>
                    <!-- Options will be loaded by JS -->
                </select>
            </div>
            <div>
                <label for="type-filter" class="text-sm font-medium text-gray-700">Loại LSX:</label>
                <select id="type-filter" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">Tất cả</option>
                    <option value="BTP">BTP</option>
                    <option value="ULA">ULA</option>
                    <option value="LK">Lưu Kho (LK)</option>
                </select>
            </div>
            <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                 <div>
                    <label for="start-date-filter" class="text-sm font-medium text-gray-700">Từ ngày:</label>
                    <input type="date" id="start-date-filter" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                </div>
                <div>
                    <label for="end-date-filter" class="text-sm font-medium text-gray-700">Đến ngày:</label>
                    <input type="date" id="end-date-filter" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                </div>
            </div>
            <div class="lg:col-span-5 flex justify-start space-x-2">
                <button id="filter-btn" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-semibold">
                    <i class="fas fa-filter mr-1"></i> Lọc
                </button>
                <button id="reset-filter-btn" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 text-sm font-semibold">
                    <i class="fas fa-sync-alt mr-1"></i> Xóa Lọc
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-x-auto rounded-lg shadow-sm">
        <table class="min-w-full text-sm divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th scope="col" class="p-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Số Lệnh SX</th>
                    <th scope="col" class="p-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Loại LSX</th>
                    <th scope="col" class="p-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Số YCSX / Mục đích</th>
                    <th scope="col" class="p-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Ngày tạo</th>
                    <th scope="col" class="p-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Ngày Hoàn Thành (DK)</th>
                    <th scope="col" class="p-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Ngày SX Xong</th>
                    <th scope="col" class="p-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Người yêu cầu</th>
                    <th scope="col" class="p-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Cảnh Báo</th>
                    <th scope="col" class="p-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Trạng thái</th>
                    <th scope="col" class="p-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Hành động</th>
                </tr>
            </thead>
            <tbody id="production-orders-list-body" class="bg-white divide-y divide-gray-200">
            </tbody>
        </table>
    </div>

    <!-- Sticky Pagination -->
    <style>
        #sticky-pagination-container-lsx {
            position: sticky;
            bottom: 0;
            z-index: 10;
        }
    </style>
    <div id="sticky-pagination-container-lsx" class="flex justify-between items-center mt-4 bg-green-50 p-3 rounded-md border-t-2 border-green-200">
        <div class="flex items-center space-x-2">
             <span class="text-sm font-medium text-gray-700">Hiển thị</span>
             <select id="limit-per-page-lsx" class="border-gray-300 rounded-md shadow-sm text-sm">
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="200" selected>200</option>
             </select>
             <span id="pagination-info-lsx" class="text-sm text-gray-600"></span>
        </div>
        <div id="pagination-controls" class="flex items-center">
            <!-- Pagination buttons will be rendered here by JS -->
        </div>
    </div>
</div>

