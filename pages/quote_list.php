

<?php
// File: quote_list.php
// Mô tả: Giao diện người dùng cho trang danh sách báo giá với layout cố định.
?>

<style>
    #quote-list-body .quote-row:hover {
        background-color: #f0fdf4; /* Tông màu green-50 của Tailwind */
        cursor: pointer;
    }
</style>

<div id="quote-list-layout" class="h-screen flex flex-col bg-gray-50">

    <div class="flex-shrink-0 bg-white p-4 border-b">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-4">
                <div class="flex items-center space-x-2">
                    <label for="filter-start-date" class="font-medium text-gray-700 text-sm whitespace-nowrap">Từ ngày:</label>
                    <input type="date" id="filter-start-date" class="form-input rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                </div>
                <div class="flex items-center space-x-2">
                    <label for="filter-end-date" class="font-medium text-gray-700 text-sm whitespace-nowrap">Đến ngày:</label>
                    <input type="date" id="filter-end-date" class="form-input rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                </div>
                 <div class="flex items-center space-x-2">
                    <label for="filter-search-term" class="font-medium text-gray-700 text-sm">Tìm kiếm:</label>
                    <input type="text" id="filter-search-term" placeholder="Mã BG, Mã/Tên KH..." class="form-input rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                </div>
                <div class="flex items-center space-x-2">
                    <label for="filter-status" class="font-medium text-gray-700 text-sm">Trạng thái:</label>
                    <select id="filter-status" class="form-select rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                        <option value="">-- Tất cả --</option>
                        <option value="Mới tạo">Mới tạo</option>
                        <option value="Đấu thầu">Đấu thầu</option>
                        <option value="Đàm phán">Đàm phán</option>
                        <option value="Chốt">Chốt</option>
                        <option value="Tạch">Tạch</option>
                        <option value="Đã tạo đơn hàng">Đã tạo đơn hàng</option>
                    </select>
                </div>
                <div class="flex items-center space-x-2">
                    <label for="filter-creator" class="font-medium text-gray-700 text-sm">Người BG:</label>
                    <select id="filter-creator" class="form-select rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                        <option value="">-- Tất cả --</option>
                        </select>
                </div>
            </div>

            <div class="flex items-center space-x-2 flex-shrink-0">
                <button id="apply-filter-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 text-sm transition-colors">
                    <i class="fas fa-filter mr-1"></i> Lọc
                </button>
                <button id="export-list-excel-btn" class="px-4 py-2 bg-green-700 text-white rounded-md hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50 text-sm transition-colors">
                    <i class="fas fa-file-excel mr-1"></i> Xuất Excel
                </button>
            </div>
        </div>
    </div>
    <div class="flex-grow overflow-y-auto p-4">
        <div class="bg-white rounded-lg shadow-md overflow-x-auto">
            <table class="w-full text-left table-auto">
                <thead class="sticky top-0 bg-gray-100 text-gray-600 uppercase text-sm leading-normal z-10">
                    <tr>
                        <th class="py-3 px-4 font-semibold text-left">Số Báo Giá</th>
                        <th class="py-3 px-4 font-semibold text-left">Mã KH</th>
                        <th class="py-3 px-4 font-semibold text-left">Tên Dự Án</th>
                        <th class="py-3 px-4 font-semibold text-left">Ngày Tạo</th>
                        <th class="py-3 px-4 font-semibold text-right">Tổng Tiền</th>
                        <th class="py-3 px-4 font-semibold text-center">Trạng Thái</th>
                        <th class="py-3 px-4 font-semibold text-center">Hành Động</th>
                    </tr>
                </thead>
                <tbody id="quote-list-body" class="text-gray-700 text-sm">
                    </tbody>
            </table>
        </div>
    </div>
    <div id="pagination-controls" class="flex-shrink-0 w-full flex justify-center items-center space-x-1 sm:space-x-2 bg-white p-4 border-t">
        </div>
    </div>

<div id="info-tooltip" class="hidden absolute z-50 p-3 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-sm max-w-xs break-words" style="pointer-events: none;">
    </div>
