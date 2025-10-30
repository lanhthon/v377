<style>
    /* CSS để ghim cố định bộ lọc khi cuộn trang */
    #sticky-filters-container {
        position: -webkit-sticky; /* Dành cho Safari */
        position: sticky;
        top: 0; 
        z-index: 40; /* Đảm bảo nó nằm trên bảng */
        background-color: #ffffff; /* Cùng màu với nền trang */
        padding-bottom: 1rem; /* Thêm khoảng cách bên dưới */
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); /* Thêm bóng mờ nhẹ */
    }
    /* CSS để ghim cố định phân trang ở cuối trang */
    #sticky-pagination-container {
        position: -webkit-sticky; /* Dành cho Safari */
        position: sticky;
        bottom: 0;
        z-index: 40;
        background-color: #f0fdf4; /* Màu xanh lá nhạt */
        padding: 1rem; /* Thêm padding */
        border-top: 1px solid #bbf7d0; /* Đường viền xanh lá */
        box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.05); /* Thêm bóng mờ nhẹ ở trên */
    }
</style>
<div class="container mx-auto p-6 bg-white rounded-lg shadow-md">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Danh sách Phiếu Chuẩn bị hàng</h1>

    <div class="mb-4 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="#" data-filter="all" class="filter-tab whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-indigo-500 text-indigo-600">
                Tất cả
            </a>
            <a href="#" data-filter="overdue" class="filter-tab whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                Quá hạn
                <span id="overdue-count" class="ml-2 hidden items-center justify-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800"></span>
            </a>
        </nav>
    </div>


    <div id="sticky-filters-container">
        <div class="flex flex-wrap items-center justify-between bg-gray-50 p-4 rounded-lg gap-4">
            <div class="flex flex-wrap items-center gap-4">
                <div>
                    <label for="filter-status" class="text-sm font-medium text-gray-700">Trạng thái:</label>
                    <select id="filter-status"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">Tất cả</option>
                    </select>
                </div>
                 <div>
                    <label for="filter-start-date" class="text-sm font-medium text-gray-700">Từ ngày:</label>
                    <input type="date" id="filter-start-date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="filter-end-date" class="text-sm font-medium text-gray-700">Đến ngày:</label>
                    <input type="date" id="filter-end-date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="filter-search" class="text-sm font-medium text-gray-700">Tìm kiếm:</label>
                    <input type="text" id="filter-search" placeholder="Nhập số YCSX, mã khách hàng..."
                        class="mt-1 block w-64 border-gray-300 rounded-md shadow-sm">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button id="apply-filters-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-search mr-2"></i>Áp dụng
                </button>
                <button id="export-excel-btn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                </button>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto shadow-md rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">STT</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Hành Động</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng Thái</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Cảnh Báo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số Đơn Gốc (YCSX)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã Khách Hàng</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên Dự Án</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ngày Tạo</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ngày Giao</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số Phiếu CBH</th>
                </tr>
            </thead>
            <tbody id="chuanbihang-list-body" class="bg-white divide-y divide-gray-200">
                </tbody>
        </table>
    </div>
    
    <div id="sticky-pagination-container" class="flex flex-col sm:flex-row items-center justify-between text-sm text-gray-700">
        <div id="pagination-info"></div>
        <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
                <span>Hiển thị</span>
                <select id="limit-per-page" class="border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="200">200</option>
                    <option value="500">500</option>
                    <option value="1000">1000</option>
                </select>
                <span>mục</span>
            </div>
            <div id="pagination-container" class="mt-4 sm:mt-0"></div>
        </div>
    </div>
</div>