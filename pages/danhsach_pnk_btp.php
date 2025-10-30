<?php
// File: pages/danhsach_pnk_btp.php
?>
<div class="p-6 bg-white rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Lịch sử Nhập kho Bán Thành Phẩm</h2>
            <p class="text-gray-600">Danh sách các phiếu nhập kho BTP đã tạo.</p>
        </div>
        <div>
            <button id="create-pnk-btp-btn" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md shadow-lg transition duration-300 ease-in-out">
                <i class="fas fa-industry mr-2"></i>Nhập từ SX
            </button>
            <button id="create-pnk-btp-ngoai-btn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors ml-2">
                <i class="fas fa-truck mr-2"></i> Nhập Mua Ngoài
            </button>
        </div>
    </div>

    <!-- Vùng Bộ lọc -->
    <div id="filter-container" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4 p-4 bg-gray-50 rounded-lg">
        <div>
            <label for="filter-start-date" class="block text-sm font-medium text-gray-700">Từ Ngày</label>
            <input type="date" id="filter-start-date" name="filter-start-date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2">
        </div>
        <div>
            <label for="filter-end-date" class="block text-sm font-medium text-gray-700">Đến Ngày</label>
            <input type="date" id="filter-end-date" name="filter-end-date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2">
        </div>
        <div>
            <label for="filter-so-phieu" class="block text-sm font-medium text-gray-700">Số Phiếu</label>
            <input type="text" id="filter-so-phieu" name="filter-so-phieu" placeholder="Nhập số phiếu..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2">
        </div>
        <div>
            <label for="filter-so-lsx" class="block text-sm font-medium text-gray-700">Số Lệnh SX</label>
            <input type="text" id="filter-so-lsx" name="filter-so-lsx" placeholder="Nhập số lệnh SX..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2">
        </div>
         <div>
            <label for="filter-ghi-chu" class="block text-sm font-medium text-gray-700">Ghi Chú Chung</label>
            <input type="text" id="filter-ghi-chu" name="filter-ghi-chu" placeholder="Nhập ghi chú..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2">
        </div>
        <div class="col-span-full flex items-end justify-start space-x-2">
            <button id="filter-btn" class="bg-cyan-500 hover:bg-cyan-600 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
                <i class="fas fa-filter mr-2"></i>Lọc
            </button>
            <button id="reset-filter-btn" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
                <i class="fas fa-sync-alt mr-2"></i>Làm Mới
            </button>
            <button id="export-list-excel-btn" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
                <i class="fas fa-file-excel mr-2"></i>Xuất Excel
            </button>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Số Phiếu</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Ngày Nhập</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Lý Do Nhập</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Số Lệnh SX</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Ghi Chú Chung</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Người Lập</th>
                    <th class="p-3 text-center text-sm font-semibold text-gray-600 border-b">Hành Động</th>
                </tr>
            </thead>
            <tbody id="danhsach-pnk-btp-body">
                <!-- Dữ liệu sẽ được tải vào đây bằng JavaScript -->
            </tbody>
        </table>
    </div>
    
    <!-- Vùng Phân trang -->
    <div id="pagination-controls" class="flex justify-between items-center mt-4">
        <!-- Các nút phân trang sẽ được tạo ở đây -->
    </div>
</div>

<script>
// Thêm vào file js/main.js

// ==================================================================
// === KÍCH HOẠT NÚT TẠO PHIẾU NHẬP BTP MUA NGOÀI ===
// ==================================================================
$(document).on('click', '#create-pnk-btp-ngoai-btn', function() {
    const pageName = 'nhapkho_btp_ngoai_create';
    
    // Cập nhật URL trên thanh địa chỉ
    history.pushState({ page: pageName }, '', `?page=${pageName}`);
    
    // Gọi hàm định tuyến chính để tải trang mà không cần load lại
    window.App.handleRouting();
});
// ==================================================================
</script>

