<?php
// File: pages/nhapkho_btp_list.php
// Trang này hiển thị danh sách các Lệnh Sản Xuất đã hoàn thành và đang chờ nhập kho BTP.
?>
<div class="p-6 bg-white rounded-lg shadow-md">
    <h2 class="text-2xl font-bold mb-4 text-gray-800">Danh sách chờ nhập kho Bán Thành Phẩm</h2>
    <p class="text-gray-600 mb-6">Danh sách các Lệnh Sản Xuất đã hoàn thành và sẵn sàng để nhập kho.</p>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Số Lệnh SX</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Số YCSX Gốc</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Ngày Tạo Lệnh</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Ngày Hoàn Thành</th>
                    <th class="p-3 text-center text-sm font-semibold text-gray-600 border-b">Hành Động</th>
                </tr>
            </thead>
            <tbody id="nhapkho-btp-list-body">
                <!-- Dữ liệu sẽ được tải vào đây bằng JavaScript -->
                <tr>
                    <td colspan="5" class="p-4 text-center text-gray-500">Đang tải dữ liệu...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
