<?php
// File: pages/nhapkho_tp_list.php
?>
<div class="p-6 bg-white rounded-lg shadow-md">
    <h2 class="text-2xl font-bold mb-4 text-gray-800">Danh sách chờ nhập kho Thành Phẩm</h2>
    <p class="text-gray-600 mb-6">Danh sách các Đơn hàng (YCSX) đã sản xuất xong và sẵn sàng để nhập kho thành phẩm.</p>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Số YCSX</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Tên Công Ty</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Tên Dự Án</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Ngày Giao Dự Kiến</th>
                    <th class="p-3 text-center text-sm font-semibold text-gray-600 border-b">Hành Động</th>
                </tr>
            </thead>
            <tbody id="nhapkho-tp-list-body">
                <tr>
                    <td colspan="5" class="p-4 text-center text-gray-500">Đang tải dữ liệu...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
