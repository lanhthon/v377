<?php
// pages/report_low_stock.php
?>
<div class="p-6 bg-gray-50 min-h-full">
    <div class="flex justify-between items-center mb-6">
        <h1 id="page-title" class="text-3xl font-bold text-gray-800">Báo Cáo Tồn Kho Tối Thiểu</h1>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-4 items-end">
            <div>
                <label for="filter-group" class="block text-sm font-medium text-gray-700">Nhóm sản phẩm</label>
                <select id="filter-group" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="">Tất cả nhóm</option>
                </select>
            </div>
            <div>
                <label for="filter-type" class="block text-sm font-medium text-gray-700">Loại sản phẩm</label>
                <select id="filter-type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="">Tất cả loại</option>
                </select>
            </div>
            <div>
                <label for="filter-thickness" class="block text-sm font-medium text-gray-700">Độ dày</label>
                <select id="filter-thickness" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="">Tất cả độ dày</option>
                </select>
            </div>
             <div>
                <label for="filter-width" class="block text-sm font-medium text-gray-700">Bản rộng</label>
                <select id="filter-width" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="">Tất cả bản rộng</option>
                </select>
            </div>
            <div class="sm:col-span-2 md:col-span-2 flex justify-end items-center space-x-3">
                <button id="apply-filters-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm">
                    <i class="fas fa-filter mr-2"></i>Áp dụng lọc
                </button>
                <button id="export-excel-btn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm">
                    <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                </button>
                 <button id="create-po-btn" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 text-sm">
                    <i class="fas fa-plus-circle mr-2"></i>Tạo Lệnh Sản Xuất
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-x-auto rounded-lg shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left font-semibold">Mã Hàng</th>
                    <th class="p-3 text-left font-semibold">Tên Sản Phẩm</th>
                    <th class="p-3 text-left font-semibold">Nhóm SP</th>
                    <th class="p-3 text-left font-semibold">Loại SP</th>
                    <th class="p-3 text-left font-semibold">Độ Dày</th>
                    <th class="p-3 text-left font-semibold">Bản Rộng</th>
                    <th class="p-3 text-right font-semibold">Tồn Kho</th>
                    <th class="p-3 text-right font-semibold">Mức Tối Thiểu</th>
                    <th class="p-3 text-center font-semibold">Tình Trạng</th>
                </tr>
            </thead>
            <tbody id="low-stock-report-body">
                </tbody>
        </table>
    </div>
</div>
