<?php
// File: pages/xuatkho_btp_list.php
// Trang hiển thị danh sách các phiếu xuất kho BTP đã được tạo.
?>
<div class="p-6 bg-white rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">DS Phiếu Xuất Kho BTP</h2>
            <p class="text-gray-600">Các phiếu xuất kho BTP để cắt thành phẩm hoặc xuất ngoài.</p>
        </div>
        <button id="create-pxk-btp-ngoai-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
            <i class="fas fa-plus mr-2"></i> Tạo Phiếu Xuất Ngoài
        </button>
    </div>

    <!-- Filter Section -->
    <div id="filter-container-pxk" class="p-4 mb-4 bg-gray-50 rounded-lg border">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <input type="text" id="filter-so-phieu-pxk" placeholder="Số phiếu..." class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            <input type="text" id="filter-so-ycsx-pxk" placeholder="Số YCSX..." class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            <input type="date" id="filter-start-date-pxk" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" title="Từ ngày">
            <input type="date" id="filter-end-date-pxk" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" title="Đến ngày">
            <input type="text" id="filter-ghi-chu-pxk" placeholder="Ghi chú..." class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div class="flex items-center justify-end mt-4 space-x-2">
            <button id="filter-btn-pxk" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-md shadow-lg"><i class="fas fa-filter mr-2"></i>Lọc</button>
            <button id="reset-filter-btn-pxk" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-md shadow-lg"><i class="fas fa-sync-alt mr-2"></i>Làm Mới</button>
            <button id="export-list-pxk-excel-btn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md shadow-lg"><i class="fas fa-file-excel mr-2"></i>Xuất Excel</button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Số Phiếu Xuất</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Số YCSX Gốc</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Ngày Xuất</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Người Tạo</th>
                    <th class="p-3 text-left text-sm font-semibold text-gray-600 border-b">Ghi Chú</th>
                    <th class="p-3 text-center text-sm font-semibold text-gray-600 border-b">Hành Động</th>
                </tr>
            </thead>
            <tbody id="xuatkho-btp-list-body">
                <!-- Data will be loaded by JavaScript -->
            </tbody>
        </table>
    </div>
    <!-- Pagination Controls -->
    <div id="pagination-controls-pxk" class="flex justify-end mt-4"></div>
</div>
<script>
$(document).ready(function() {
    // Event listener cho nút tạo phiếu xuất ngoài
    $(document).on('click', '#create-pxk-btp-ngoai-btn', function() {
        const pageName = 'xuatkho_btp_ngoai_create';
        history.pushState({ page: pageName }, '', `?page=${pageName}`);
        if (window.App && typeof window.App.handleRouting === 'function') {
            window.App.handleRouting();
        } else {
            // Fallback
            window.location.href = `?page=${pageName}`;
        }
    });
});
</script>
