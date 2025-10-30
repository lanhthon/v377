<!-- File: pages/ycsx_list.php -->
<div class="bg-white p-6 rounded-lg shadow-lg">
    <div class="flex justify-between items-center mb-4">
        <h1 id="page-title" class="text-2xl font-bold text-gray-700">Danh sách Yêu cầu sản xuất</h1>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Số YCSX</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Khách Hàng</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Ngày Tạo</th>
                    <th class="text-center py-3 px-4 uppercase font-semibold text-sm">Trạng Thái</th>
                    <th class="text-center py-3 px-4 uppercase font-semibold text-sm">Hành Động</th>
                </tr>
            </thead>
            <tbody id="ycsx-list-body" class="text-gray-700">
                <!-- Dữ liệu sẽ được tải vào đây bằng JavaScript -->
                <tr>
                    <td colspan="5" class="text-center p-4">Đang tải...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>