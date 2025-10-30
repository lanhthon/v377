<?php
// pages/xuatkho_list.php
// Version: 2.0
// Description: Cập nhật giao diện để hiển thị danh sách các phiếu chuẩn bị hàng chờ xuất kho.
?>
<div class="p-6 bg-gray-50 min-h-full">
    <div class="flex justify-between items-center mb-6">
        <h1 id="page-title" class="text-3xl font-bold text-gray-800">Danh Sách Chờ Xuất Kho (Theo Phiếu Chuẩn Bị Hàng)</h1>
    </div>

    <div class="bg-white overflow-x-auto rounded-lg shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left font-semibold">Số CBH</th>
                    <th class="p-3 text-left font-semibold">Số YCSX Gốc</th>
                    <th class="p-3 text-left font-semibold">Khách Hàng</th>
                    <th class="p-3 text-left font-semibold">Ngày Tạo</th>
                    <th class="p-3 text-left font-semibold">Ngày Giao</th>
                    <th class="p-3 text-center font-semibold">Hành Động</th>
                </tr>
            </thead>
            <tbody id="xuatkho-list-body">
                <!-- Dữ liệu sẽ được tải vào đây bằng JavaScript -->
            </tbody>
        </table>
    </div>
</div>
