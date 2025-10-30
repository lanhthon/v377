<?php
// pages/quanly_kho.php
?>
<!-- Include SheetJS library for Excel export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<style>
    /* Custom styles for active tab to override Tailwind defaults if necessary */
    .tab-btn.active {
        background-color: #16a34a; /* Tailwind green-600 */
        color: white;
        border-bottom-color: transparent !important;
    }
</style>

<div class="p-6 bg-gray-50 min-h-full">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
        <h1 id="page-title" class="text-3xl font-bold text-gray-800">Quản Lý Kho</h1>
        <div class="flex space-x-3 no-print">
            <button id="export-inventory-excel-btn"
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors duration-200 shadow-sm">
                <i class="fas fa-file-excel mr-2"></i>Xuất Tồn Kho (Excel)
            </button>
        </div>
    </div>

    <div class="mb-6 flex space-x-2 border-b border-gray-200 no-print">
        <button class="tab-btn active px-4 py-2 text-sm font-medium rounded-t-md" data-tab="inventory">
            <i class="fas fa-box-open mr-2"></i>Tồn Kho
        </button>
        <button class="tab-btn px-4 py-2 text-sm font-medium rounded-t-md" data-tab="history">
            <i class="fas fa-history mr-2"></i>Lịch Sử Nhập Xuất
        </button>
        <button class="tab-btn px-4 py-2 text-sm font-medium rounded-t-md" data-tab="report">
            <i class="fas fa-chart-bar mr-2"></i>Báo Cáo
        </button>
    </div>

    <!-- Inventory Tab -->
    <div id="tab-content-inventory" class="tab-pane">
        <div class="bg-white p-6 rounded-lg shadow-sm mb-6 no-print">
            <!-- MODIFIED: Updated grid layout for filters to 5 columns -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
                <div class="md:col-span-1">
                    <label for="inventory-search-input" class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                    <input type="text" id="inventory-search-input" placeholder="Tìm theo mã/tên..."
                           class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                 <div>
                    <label for="inventory-group-filter" class="block text-sm font-medium text-gray-700 mb-1">Nhóm SP</label>
                    <select id="inventory-group-filter"
                            class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">-- Tất cả --</option>
                    </select>
                </div>
                <div>
                    <label for="inventory-type-filter" class="block text-sm font-medium text-gray-700 mb-1">Loại SP</label>
                    <select id="inventory-type-filter" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">-- Tất cả --</option>
                    </select>
                </div>
                <div>
                     <label for="inventory-thickness-filter" class="block text-sm font-medium text-gray-700 mb-1">Độ dày</label>
                    <select id="inventory-thickness-filter"
                            class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">-- Tất cả --</option>
                    </select>
                </div>
                <!-- ADDED: Width filter dropdown -->
                <div>
                     <label for="inventory-width-filter" class="block text-sm font-medium text-gray-700 mb-1">Bản rộng</label>
                    <select id="inventory-width-filter"
                            class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">-- Tất cả --</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-x-auto rounded-lg shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left font-semibold">Mã SP</th>
                        <th class="p-3 text-left font-semibold">Tên SP</th>
                        <th class="p-3 text-left font-semibold">Nhóm SP</th>
                        <th class="p-3 text-left font-semibold">Loại SP</th>
                        <th class="p-3 text-center font-semibold">Thông Số</th>
                        <th class="p-3 text-center font-semibold">Độ Dày</th>
                        <th class="p-3 text-center font-semibold">Bản Rộng</th>
                        <th class="p-3 text-right font-semibold">Tồn Kho</th>
                        <th class="p-3 text-right font-semibold">Định Mức Tối Thiểu</th>
                        <th class="p-3 text-center font-semibold">Trạng Thái</th>
                        <th class="p-3 text-center font-semibold no-print">Hành Động</th>
                    </tr>
                </thead>
                <tbody id="inventory-list-body">
                     <tr><td colspan="11" class="text-center p-10"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i></td></tr>
                </tbody>
            </table>
        </div>
        <div id="inventory-pagination" class="flex justify-center mt-4 no-print"></div>
    </div>

    <!-- History Tab -->
    <div id="tab-content-history" class="tab-pane hidden">
        <div class="bg-white p-6 rounded-lg shadow-sm mb-6 no-print">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
                <div>
                    <label for="history-start-date" class="block text-sm font-medium text-gray-700">Từ ngày:</label>
                    <input type="date" id="history-start-date" class="p-2 border border-gray-300 rounded-md w-full">
                </div>
                <div>
                    <label for="history-end-date" class="block text-sm font-medium text-gray-700">Đến ngày:</label>
                    <input type="date" id="history-end-date" class="p-2 border border-gray-300 rounded-md w-full">
                </div>
                <div>
                    <label for="history-type-filter" class="block text-sm font-medium text-gray-700">Loại giao dịch:</label>
                    <select id="history-type-filter" class="p-2 border border-gray-300 rounded-md w-full">
                        <option value="">Tất cả</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button id="history-filter-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 w-full">
                        <i class="fas fa-filter mr-2"></i>Lọc
                    </button>
                </div>
                 <div class="flex items-end">
                    <button id="export-history-excel-btn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 w-full">
                        <i class="fas fa-file-excel mr-2"></i>Xuất Lịch Sử
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-x-auto rounded-lg shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left font-semibold">Thời Gian</th>
                        <th class="p-3 text-left font-semibold">Mã SP</th>
                        <th class="p-3 text-left font-semibold">Tên SP</th>
                        <th class="p-3 text-left font-semibold">Loại GD</th>
                        <th class="p-3 text-right font-semibold">SL Thay Đổi</th>
                        <th class="p-3 text-right font-semibold">SL Sau GD</th>
                        <th class="p-3 text-left font-semibold">Ghi Chú</th>
                        <th class="p-3 text-left font-semibold">Mã Tham Chiếu</th>
                    </tr>
                </thead>
                <tbody id="history-list-body"></tbody>
            </table>
        </div>
        <div id="history-pagination" class="flex justify-center mt-4 no-print"></div>
    </div>
    
    <!-- Report Tab -->
    <div id="tab-content-report" class="tab-pane hidden">
        <div class="bg-white p-6 rounded-lg shadow-sm mb-6 no-print">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Báo Cáo Xuất Nhập Tồn</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="report-start-date" class="block text-sm font-medium text-gray-700">Từ ngày:</label>
                    <input type="date" id="report-start-date" class="p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="report-end-date" class="block text-sm font-medium text-gray-700">Đến ngày:</label>
                    <input type="date" id="report-end-date" class="p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button id="generate-report-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 w-full">
                        <i class="fas fa-search mr-2"></i>Xem Báo Cáo
                    </button>
                </div>
                 <div class="flex items-end">
                    <button id="export-report-excel-btn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 w-full">
                        <i class="fas fa-file-excel mr-2"></i>Xuất Báo Cáo
                    </button>
                </div>
            </div>
        </div>
        <div class="bg-white overflow-x-auto rounded-lg shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left font-semibold">Mã SP</th>
                        <th class="p-3 text-left font-semibold">Tên SP</th>
                        <th class="p-3 text-right font-semibold">Tồn Đầu Kỳ</th>
                        <th class="p-3 text-right font-semibold text-green-600">Tổng Nhập</th>
                        <th class="p-3 text-right font-semibold text-red-600">Tổng Xuất</th>
                        <th class="p-3 text-right font-semibold">Tồn Cuối Kỳ</th>
                    </tr>
                </thead>
                <tbody id="inventory-report-body">
                    <tr><td colspan="6" class="text-center p-6 text-gray-500">Vui lòng chọn khoảng ngày để xem báo cáo.</td></tr>
                </tbody>
            </table>
        </div>
    </div>


    <!-- Modals (Adjust Stock, Edit Min Stock) -->
    <div id="adjust-stock-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
            <h2 class="text-2xl font-bold mb-4">Điều Chỉnh Tồn Kho</h2>
            <form id="adjust-stock-form">
                <input type="hidden" id="adjust-product-id">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Sản phẩm:</label>
                    <p id="adjust-product-name" class="text-lg font-semibold text-blue-700"></p>
                </div>
                <div class="mb-4">
                    <label for="adjust-current-stock" class="block text-gray-700 text-sm font-bold mb-2">Tồn kho hiện tại:</label>
                    <input type="text" id="adjust-current-stock" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100 cursor-not-allowed" readonly>
                </div>
                <div class="mb-4">
                    <label for="adjust-new-stock" class="block text-gray-700 text-sm font-bold mb-2">Số lượng tồn kho mới:</label>
                    <input type="number" id="adjust-new-stock" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label for="adjust-type" class="block text-gray-700 text-sm font-bold mb-2">Loại điều chỉnh:</label>
                    <select id="adjust-type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="KIEM_KE">Kiểm kê</option>
                        <option value="NHAP_KHAC">Nhập khác</option>
                        <option value="XUAT_KHAC">Xuất khác</option>
                    </select>
                </div>
                <div class="mb-6">
                    <label for="adjust-notes" class="block text-gray-700 text-sm font-bold mb-2">Ghi chú:</label>
                    <textarea id="adjust-notes" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                </div>
                <div class="flex items-center justify-end">
                    <button type="button" id="cancel-adjust-btn" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2">Hủy</button>
                    <button type="submit" id="confirm-adjust-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Xác nhận</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-min-stock-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
            <h2 class="text-2xl font-bold mb-4">Chỉnh Sửa Định Mức Tối Thiểu</h2>
            <form id="edit-min-stock-form">
                <input type="hidden" id="edit-min-stock-product-id">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Sản phẩm:</label>
                    <p id="edit-min-stock-product-name" class="text-lg font-semibold text-green-700"></p>
                </div>
                <div class="mb-4">
                    <label for="edit-min-stock-current" class="block text-gray-700 text-sm font-bold mb-2">Định mức hiện tại:</label>
                    <input type="text" id="edit-min-stock-current" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100 cursor-not-allowed" readonly>
                </div>
                <div class="mb-6">
                    <label for="edit-min-stock-new" class="block text-gray-700 text-sm font-bold mb-2">Định mức mới:</label>
                    <input type="number" id="edit-min-stock-new" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="flex items-center justify-end">
                    <button type="button" id="cancel-edit-min-stock-btn" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2">Hủy</button>
                    <button type="submit" id="confirm-edit-min-stock-btn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>



