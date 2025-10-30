<?php // pages/xuatkho_issued_list.php ?>
<div class="p-6 bg-gray-50 min-h-full">
    <div class="flex justify-between items-center mb-6">
        <h1 id="page-title" class="text-3xl font-bold text-gray-800">Danh Sách Phiếu Xuất Kho Đã Hoàn Tất</h1>
    </div>

    <div class="mb-6 p-4 bg-white rounded-lg shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label for="search-input" class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                <input type="text" id="search-input" placeholder="Số phiếu, ĐH gốc, Mã KH, Tên dự án, Người nhận..." class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái ĐH</label>
                <select id="status-filter" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Tất cả</option>
                    <option value="Đã xuất kho">Đã xuất kho</option>
                    <option value="Đã giao hàng">Đã giao hàng</option>
                    <option value="Đã hủy">Đã hủy</option>
                </select>
            </div>
            <div>
                <label for="start-date-filter" class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
                <input type="date" id="start-date-filter" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="end-date-filter" class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
                <input type="date" id="end-date-filter" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>
        <div class="mt-4 flex justify-end space-x-2">
            <button id="filter-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                <i class="fas fa-filter mr-2"></i>Lọc
            </button>
            <button id="clear-filter-btn" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-400">
                Xóa bộ lọc
            </button>
            <button id="export-list-excel-btn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                <i class="fas fa-file-excel mr-2"></i>Xuất Excel
            </button>
        </div>
    </div>

    <div class="bg-white overflow-x-auto rounded-lg shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left font-semibold">Số Phiếu Xuất</th>
                    <th class="p-3 text-left font-semibold">Đơn Hàng Gốc</th>
                    <th class="p-3 text-left font-semibold">Mã KH</th>
                    <th class="p-3 text-left font-semibold">Tên Dự Án</th>
                    <th class="p-3 text-left font-semibold">Người Nhận</th>
                    <th class="p-3 text-left font-semibold">Ngày Xuất</th>
                    <th class="p-3 text-center font-semibold">Trạng thái ĐH</th>
                    <th class="p-3 text-center font-semibold no-print">Chứng Từ</th>
                    <th class="p-3 text-center font-semibold no-print">Hành Động Cuối</th>
                </tr>
            </thead>
            <tbody id="issued-slips-list-body">
                </tbody>
        </table>
    </div>

    <div id="pagination-container" class="mt-6 flex justify-center">
        </div>
</div>
