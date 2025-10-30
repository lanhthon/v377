<?php
// File: pages/customers.php
// Trang giao diện để quản lý khách hàng (Thêm, Sửa, Xóa).
?>
<div class="p-4 sm:p-6 bg-white rounded-lg shadow-lg">
    <h1 class="text-2xl font-bold text-gray-700 mb-6">Quản lý Khách hàng</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cột Form Thêm/Sửa Khách hàng -->
        <div class="lg:col-span-1">
            <div class="p-6 border rounded-lg bg-gray-50 h-full">
                <h2 id="customer-form-title" class="text-xl font-semibold mb-4 text-gray-800">Thêm khách hàng mới</h2>
                <form id="customer-form" class="space-y-4">
                    <input type="hidden" id="customer-id" name="KhachHangID">

                    <div>
                        <label for="customer-company-name" class="block text-sm font-medium text-gray-700">Tên công
                            ty</label>
                        <input type="text" id="customer-company-name" name="TenCongTy"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>

                    <div>
                        <label for="customer-contact-person" class="block text-sm font-medium text-gray-700">Người liên
                            hệ</label>
                        <input type="text" id="customer-contact-person" name="NguoiLienHe"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>

                    <div>
                        <label for="customer-tax-code" class="block text-sm font-medium text-gray-700">Mã số
                            thuế</label>
                        <input type="text" id="customer-tax-code" name="MaSoThue"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>

                    <div>
                        <label for="customer-email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="customer-email" name="Email"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="customer-phone" class="block text-sm font-medium text-gray-700">Số điện
                                thoại</label>
                            <input type="tel" id="customer-phone" name="SoDienThoai"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label for="customer-mobile" class="block text-sm font-medium text-gray-700">Số di
                                động</label>
                            <input type="tel" id="customer-mobile" name="SoDiDong"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
                    <div>
                        <label for="customer-fax" class="block text-sm font-medium text-gray-700">Số Fax</label>
                        <input type="tel" id="customer-fax" name="SoFax"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>

                    <div>
                        <label for="customer-address" class="block text-sm font-medium text-gray-700">Địa chỉ</label>
                        <textarea id="customer-address" name="DiaChi" rows="3"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>

                    <div>
                        <label for="customer-price-schema" class="block text-sm font-medium text-gray-700">Cơ chế giá
                            mặc định</label>
                        <select id="customer-price-schema" name="CoCheGiaID"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <!-- Cơ chế giá sẽ được tải vào đây -->
                        </select>
                    </div>

                    <!-- Nút bấm -->
                    <div class="pt-4 flex items-center justify-between">
                        <button type="submit" id="save-customer-btn"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i>Thêm mới
                        </button>
                        <button type="button" id="clear-customer-form-btn"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50">
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cột Danh sách Khách hàng -->
        <div class="lg:col-span-2">
            <div class="overflow-x-auto border rounded-lg">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Tên công ty</th>
                            <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Người liên hệ</th>
                            <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Email</th>
                            <th class="text-center py-3 px-4 uppercase font-semibold text-sm">Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="customer-list-body" class="text-gray-700">
                        <!-- Danh sách khách hàng sẽ được tải vào đây -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Tải tệp JS dành riêng cho trang này -->
<script src="assets/js/customers.js"></script>