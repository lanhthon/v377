<?php
// File: pages/prices.php
// Trang giao diện để quản lý cơ chế giá.
?>
<div class="p-4 sm:p-6 bg-white rounded-lg shadow-lg">
    <h1 class="text-2xl font-bold text-gray-700 mb-6">Quản lý Cơ Chế Giá</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cột Form Thêm/Sửa -->
        <div class="lg:col-span-1">
            <div class="p-6 border rounded-lg bg-gray-50 h-full">
                <h2 id="price-form-title" class="text-xl font-semibold mb-4 text-gray-800">Thêm cơ chế giá mới</h2>
                <form id="price-form" class="space-y-4">
                    <input type="hidden" id="price-id" name="CoCheGiaID">

                    <div>
                        <label for="price-code" class="block text-sm font-medium text-gray-700">Mã Cơ Chế (vd: P0,
                            P1)</label>
                        <input type="text" id="price-code" name="MaCoChe"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            required>
                    </div>

                    <div>
                        <label for="price-name" class="block text-sm font-medium text-gray-700">Tên Cơ Chế Giá</label>
                        <input type="text" id="price-name" name="TenCoChe"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            required>
                    </div>

                    <div>
                        <label for="price-adjustment" class="block text-sm font-medium text-gray-700">Phần trăm điều
                            chỉnh (%)</label>
                        <input type="number" step="0.01" id="price-adjustment" name="PhanTramDieuChinh"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="-5.5" required>
                    </div>

                    <div class="pt-4 flex items-center justify-between">
                        <button type="submit" id="save-price-btn"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-2"></i>Thêm mới
                        </button>
                        <button type="button" id="clear-price-form-btn"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cột Danh sách -->
        <div class="lg:col-span-2">
            <div class="overflow-x-auto border rounded-lg">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Mã</th>
                            <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Tên Cơ Chế</th>
                            <th class="text-right py-3 px-4 uppercase font-semibold text-sm">Điều chỉnh</th>
                            <th class="text-center py-3 px-4 uppercase font-semibold text-sm">Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="price-list-body" class="text-gray-700">
                        <!-- Dữ liệu sẽ được tải vào đây -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Tải tệp JS dành riêng cho trang này -->
<script src="assets/js/prices.js"></script>