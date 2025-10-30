<?php
// File: pages/nhapkho_vattu_list.php
// Trang hiển thị danh sách các phiếu nhập kho vật tư đã tạo.
?>
<div class="p-6 bg-white rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Danh sách Nhập kho Vật tư</h2>
            <p class="text-gray-600">Quản lý các phiếu nhập vật tư từ nhà cung cấp.</p>
        </div>
        <button id="create-new-pnk-vattu-btn" class="bg-blue-600 text-white px-5 py-2 rounded-md hover:bg-blue-700 transition-colors">
            <i class="fas fa-plus-circle mr-2"></i> Tạo Phiếu Nhập Mới
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Số Phiếu</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Ngày Nhập</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Nhà Cung Cấp</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Lý Do Nhập</th>
                    <th class="p-3 text-right text-sm font-semibold text-gray-600 border-b">Tổng Tiền</th>
                    <th class="p-3 text-center text-sm font-semibold text-gray-600 border-b">Hành Động</th>
                </tr>
            </thead>
            <tbody id="nhapkho-vattu-list-body">
                <!-- Dữ liệu sẽ được tải vào đây bằng JavaScript -->
                <tr>
                    <td colspan="6" class="p-4 text-center text-gray-500">Đang tải dữ liệu...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
