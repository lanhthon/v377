<style>
    /* General Styling */
    .form-input-table, .form-select-table {
        padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 0.375rem;
        transition: all 0.2s; width: 100%; min-width: 100px; font-size: 0.8rem;
    }
    .form-input-table:focus {
        border-color: #3b82f6; outline: 0; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }
    /* Tab Styling */
    .congno-tab {
        padding: 8px 16px; font-size: 0.9rem; font-weight: 600;
        color: #4b5563; border-bottom: 3px solid transparent; cursor: pointer;
        transition: all 0.2s ease-in-out;
    }
    .congno-tab:hover { color: #1d4ed8; }
    .congno-tab.active-tab { color: #2563eb; border-bottom-color: #2563eb; }

    /* Badge Styling */
    .status-badge { display: inline-block; padding: 3px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
    .status-unpaid { background: #fef3c7; color: #92400e; }
    .status-partial { background: #dbeafe; color: #1e40af; }
    .status-paid { background: #d1fae5; color: #065f46; }

    .overdue-badge { padding: 3px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; }
    .overdue-warning { background: #fef9c3; color: #a16207; }
    .overdue-critical { background: #fee2e2; color: #b91c1c; }
    
    /* Pagination Styling */
    .pagination-link {
        transition: all 0.2s ease-in-out; border: 1px solid #d1d5db; color: #374151;
    }
    .pagination-link:hover { background-color: #eff6ff; border-color: #3b82f6; color: #3b82f6; }
    .pagination-link.active { background-color: #3b82f6; border-color: #3b82f6; color: white; cursor: default; }
    .pagination-link.disabled { color: #d1d5db; cursor: not-allowed; background-color: #f9fafb; }
</style>

<div id="congno-management-page" class="min-h-screen bg-gray-100 p-4 sm:p-6">
    <div class="max-w-full mx-auto">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">
            <i class="fas fa-chart-line text-blue-600 mr-3"></i>Bảng theo dõi Công nợ
        </h1>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">
            <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
                <div class="bg-blue-100 text-blue-600 p-3 rounded-full"><i class="fas fa-file-invoice-dollar text-xl"></i></div>
                <div>
                    <p class="text-sm text-gray-500">Tổng công nợ</p>
                    <p id="summary-total-debt" class="text-2xl font-bold text-gray-800">0 đ</p>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
                <div class="bg-yellow-100 text-yellow-600 p-3 rounded-full"><i class="fas fa-exclamation-triangle text-xl"></i></div>
                <div>
                    <p class="text-sm text-gray-500">Nợ quá hạn</p>
                    <p id="summary-overdue-debt" class="text-2xl font-bold text-gray-800">0 đ</p>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
                 <div class="bg-red-100 text-red-600 p-3 rounded-full"><i class="fas fa-calendar-times text-xl"></i></div>
                <div>
                    <p class="text-sm text-gray-500">Số đơn quá hạn</p>
                    <p id="summary-overdue-count" class="text-2xl font-bold text-gray-800">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm">
            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav id="congno-tabs" class="flex -mb-px px-4" aria-label="Tabs">
                    <a href="#" class="congno-tab active-tab" data-filter="all">Tất cả</a>
                    <a href="#" class="congno-tab" data-filter="overdue">
                        Quá hạn <span id="overdue-tab-count" class="ml-2 bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded-full">0</span>
                    </a>
                </nav>
            </div>
            
            <!-- Filter and Actions -->
            <div class="p-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <input type="text" id="congno-search-input" placeholder="Tìm YCSX, KH, dự án..." class="form-input-table" style="width: 250px;">
                        <select id="congno-status-filter" class="form-input-table" style="width: 180px;">
                            <option value="">Tất cả trạng thái</option>
                            <option value="Chưa thanh toán">Chưa thanh toán</option>
                            <option value="Thanh toán 1 phần">Thanh toán 1 phần</option>
                            <option value="Đã thanh toán">Đã thanh toán</option>
                        </select>
                        <input type="date" id="congno-start-date-filter" class="form-input-table text-gray-500" style="width: 150px;">
                        <input type="date" id="congno-end-date-filter" class="form-input-table text-gray-500" style="width: 150px;">
                        <button id="congno-filter-btn" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition"><i class="fas fa-search mr-1"></i>Lọc</button>
                        <button id="congno-clear-filter-btn" class="bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-semibold hover:bg-gray-300 transition" title="Xóa bộ lọc"><i class="fas fa-eraser"></i></button>
                    </div>
                    <div>
                        <button id="export-congno-detail-btn" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-green-700 transition">
                            <i class="fas fa-file-excel mr-2"></i>Xuất Excel Chi Tiết
                        </button>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold">
                        <tr>
                            <th class="p-3 text-left">Số YCSX</th>
                            <th class="p-3 text-left">Khách hàng</th>
                            <th class="p-3 text-left">Đơn Vị Trả</th>
                            <th class="p-3 text-left">Ngày GH</th>
                            <th class="p-3 text-center">Số Ngày Nợ</th>
                            <th class="p-3 text-left">Hạn TT</th>
                            <th class="p-3 text-left">Ngày xuất HĐ</th>
                            <th class="p-3 text-right">Tổng Giá Trị</th>
                            <th class="p-3 text-right">Đã TT/Tạm Ứng</th>
                            <th class="p-3 text-right">Còn Lại</th>
                            <th class="p-3 text-center">Trạng Thái</th>
                            <th class="p-3 text-center">Quá Hạn</th>
                            <th class="p-3 text-center">Hồ Sơ</th>
                            <th class="p-3 text-center">Lưu</th>
                        </tr>
                    </thead>
                    <tbody id="congno-list-body" class="divide-y divide-gray-200"></tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div id="congno-pagination-container" class="p-4 bg-white border-t flex flex-col sm:flex-row items-center justify-between"></div>
        </div>
    </div>
</div>

