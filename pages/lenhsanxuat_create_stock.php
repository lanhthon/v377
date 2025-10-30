<?php
// pages/lenhsanxuat_create_stock.php
?>
<div class="p-6 bg-gray-50 min-h-full">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Tạo Lệnh Sản Xuất Lưu Kho</h1>
        <div>
             <a href="?page=quanly_sanxuat_list" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i> Quay Lại Danh Sách(LSX)
            </a>
            <button id="create-stock-po-btn" class="bg-blue-600 text-white px-5 py-2 rounded-md hover:bg-blue-700 font-semibold shadow-sm ml-2">
                <i class="fas fa-check-circle mr-2"></i>Xác Nhận Tạo Lệnh
            </button>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
            <!-- Khu vực 1: Thêm sản phẩm tự động -->
            <div class="border border-gray-200 rounded-lg p-4 bg-slate-50">
                <h3 class="text-lg font-semibold mb-3 text-gray-700 border-b pb-2">Khu vực 1: Thêm sản phẩm tự động</h3>
                <p class="text-sm text-gray-500 mb-4">Sử dụng bộ lọc để tìm và thêm nhanh các sản phẩm cần sản xuất vào danh sách bên dưới.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="filter-group" class="block text-sm font-medium text-gray-700">Nhóm sản phẩm</label>
                        <select id="filter-group" class="filter-input mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Tất cả</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter-type" class="block text-sm font-medium text-gray-700">Loại sản phẩm</label>
                        <select id="filter-type" class="filter-input mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Tất cả</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter-thickness" class="block text-sm font-medium text-gray-700">Độ dày</label>
                        <select id="filter-thickness" class="filter-input mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Tất cả</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter-width" class="block text-sm font-medium text-gray-700">Bản rộng</label>
                        <select id="filter-width" class="filter-input mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Tất cả</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2 flex items-center pt-2">
                        <input id="filter-only-low-stock" type="checkbox" checked class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="filter-only-low-stock" class="ml-2 block text-sm text-gray-900">
                            Chỉ lấy sản phẩm dưới định mức tồn kho
                        </label>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button id="add-filtered-items-btn" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 font-semibold shadow-sm">
                        <i class="fas fa-filter mr-2"></i>Thêm theo bộ lọc
                    </button>
                     <button id="add-all-low-stock-btn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 font-semibold shadow-sm">
                        <i class="fas fa-layer-group mr-2"></i>Thêm tất cả (dưới định mức)
                    </button>
                </div>
            </div>

            <!-- Khu vực 2: Thêm sản phẩm thủ công -->
            <div class="border border-gray-200 rounded-lg p-4 bg-slate-50">
                <h3 class="text-lg font-semibold mb-3 text-gray-700 border-b pb-2">Khu vực 2: Thêm sản phẩm thủ công</h3>
                 <p class="text-sm text-gray-500 mb-4">Tìm kiếm và thêm từng sản phẩm riêng lẻ nếu không có trong bộ lọc tự động.</p>
                <div class="grid grid-cols-1 gap-4 items-end">
                     <div class="relative">
                        <label for="product-search" class="block text-sm font-medium text-gray-700">Tìm kiếm sản phẩm (Mã hoặc Tên)</label>
                        <input type="text" id="product-search" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Nhập ít nhất 2 ký tự...">
                        <div id="product-search-results" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md mt-1 shadow-lg max-h-60 overflow-y-auto hidden"></div>
                    </div>
                     <div>
                        <label for="product-quantity" class="block text-sm font-medium text-gray-700">Số lượng cần sản xuất</label>
                        <input type="number" id="product-quantity" min="1" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="0">
                    </div>
                     <div>
                        <button id="add-product-btn" class="w-full bg-teal-500 text-white px-4 py-2 rounded-md hover:bg-teal-600 font-semibold shadow-sm mt-2">
                            <i class="fas fa-plus mr-2"></i>Thêm thủ công
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

   <div class="bg-white overflow-x-auto rounded-lg shadow-sm">
    <div class="flex justify-between items-center p-4 border-b">
        <h2 class="text-xl font-semibold text-gray-800">Danh sách sản phẩm cần sản xuất</h2>
        <button id="remove-all-items-btn" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 font-semibold shadow-sm text-sm">
            <i class="fas fa-times-circle mr-2"></i>Xóa Tất Cả
        </button>
    </div>
 <table id="stock-po-table" class="min-w-full bg-white">
  <thead class="bg-gray-100">
    <tr>
        <th class="p-3 border-b text-center w-12">
            <input type="checkbox" id="select-all-checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
        </th>
        <th class="p-3 border-b text-center font-semibold text-gray-700 uppercase tracking-wider w-16">STT</th>
        <th class="p-3 border-b text-left font-semibold text-gray-700 uppercase tracking-wider w-40">Mã SP</th>
        <th class="p-3 border-b text-right font-semibold text-gray-700 uppercase tracking-wider w-48">SL Sản Xuất</th>
        <th class="p-3 border-b text-right font-semibold text-gray-700 uppercase tracking-wider w-40">Tồn Kho</th>
        <th class="p-3 border-b text-center font-semibold text-gray-700 uppercase tracking-wider w-24">Xóa</th>
        <th class="p-3 border-b text-left font-semibold text-gray-700 uppercase tracking-wider">Tên Sản Phẩm</th>
    </tr>
</thead>
    <tbody id="stock-po-items-body">
        </tbody>
</table>
    </div>
</div>

