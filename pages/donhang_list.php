<div class="p-6 bg-gray-50 min-h-full flex flex-col h-screen">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800">Danh Sách Đơn Hàng</h1>
        <div class="flex items-center space-x-3">
             <button id="export-donhang-list-excel-btn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 font-semibold shadow-sm">
                <i class="fas fa-file-excel mr-2"></i>Xuất Excel
            </button>
        </div>
    </div>

    <div class="mb-4">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <a href="#" class="filter-tab-dh border-indigo-500 text-indigo-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-filter="all">
                    Tất cả
                </a>
                <a href="#" class="filter-tab-dh border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-filter="overdue">
                    Quá hạn 
                    <span id="overdue-count-dh" class="hidden ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium bg-red-100 text-red-800"></span>
                </a>
            </nav>
        </div>
    </div>
    
    <div id="sticky-filter-bar-dh" class="bg-white p-4 rounded-lg shadow-sm mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <div>
                <label for="search-filter-dh" class="text-sm font-medium text-gray-700">Tìm kiếm:</label>
                <input type="text" id="search-filter-dh" placeholder="Số ĐH, Tên/Mã Công ty..." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
            </div>
            <div>
                <label for="status-filter-dh" class="text-sm font-medium text-gray-700">Trạng thái:</label>
                <select id="status-filter-dh" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">Tất cả</option>
                </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                 <div>
                    <label for="start-date-filter-dh" class="text-sm font-medium text-gray-700">Từ ngày:</label>
                    <input type="date" id="start-date-filter-dh" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                </div>
                <div>
                    <label for="end-date-filter-dh" class="text-sm font-medium text-gray-700">Đến ngày:</label>
                    <input type="date" id="end-date-filter-dh" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                </div>
            </div>
            <div class="flex justify-start space-x-2">
                <button id="filter-btn-dh" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-semibold">
                    <i class="fas fa-filter mr-1"></i> Lọc
                </button>
                <button id="reset-filter-btn-dh" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 text-sm font-semibold">
                    <i class="fas fa-sync-alt mr-1"></i> Xóa Lọc
                </button>
            </div>
        </div>
    </div>

    <div class="flex-grow bg-white overflow-x-auto rounded-lg shadow-sm">
        <table class="min-w-full text-sm divide-y divide-gray-200">
            <thead class="bg-gray-100 sticky top-0 z-10">
                <tr>
                    <th class="p-3 text-left text-xs font-bold text-gray-600 uppercase">STT</th>
                    <th class="p-3 text-left text-xs font-bold text-gray-600 uppercase">Số Đơn Hàng</th>
                    <th class="p-3 text-left text-xs font-bold text-gray-600 uppercase">Mã KH</th>
                    <th class="p-3 text-left text-xs font-bold text-gray-600 uppercase">Tên Dự Án</th>
                    <th class="p-3 text-left text-xs font-bold text-gray-600 uppercase">Người Báo Giá</th>
                    <th class="p-3 text-left text-xs font-bold text-gray-600 uppercase">Ngày Đặt</th>
                    <th class="p-3 text-left text-xs font-bold text-gray-600 uppercase">Ngày Giao Khách</th>
                    <th class="p-3 text-center text-xs font-bold text-gray-600 uppercase">Cảnh Báo</th>
                    <th class="p-3 text-left text-xs font-bold text-gray-600 uppercase">Tổng Tiền</th>
                    <th class="p-3 text-center text-xs font-bold text-gray-600 uppercase">Trạng Thái</th>
                    <th class="p-3 text-center text-xs font-bold text-gray-600 uppercase">Hành động</th>
                </tr>
            </thead>
            <tbody id="donhang-table-body" class="bg-white divide-y divide-gray-200"></tbody>
        </table>
    </div>
    
    <div id="sticky-pagination-container-dh" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-4 rounded-lg shadow-sm">
        <div class="flex-1 flex justify-between sm:hidden">
            </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p id="pagination-info-dh" class="text-sm text-gray-700">
                    Hiển thị <span class="font-medium">1</span> đến <span class="font-medium">10</span> của <span class="font-medium">97</span> mục
                </p>
            </div>
            <div class="flex items-center space-x-4">
                 <div class="flex items-center space-x-2 text-sm">
                     <label for="limit-per-page-dh">Hiển thị:</label>
                     <select id="limit-per-page-dh" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="200">200</option>
                        <option value="500">500</option>
                        <option value="1000">1000</option>
                    </select>
                 </div>
                <div id="pagination-controls-dh"></div>
            </div>
        </div>
    </div>
</div>

<div id="info-tooltip-dh" class="hidden absolute z-50 p-3 text-sm font-medium text-white bg-gray-800 rounded-lg shadow-sm max-w-xs break-words" style="pointer-events: none;">
    </div>