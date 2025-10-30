<?php
// File: pages/inventory_sales_report.php
// Giao diện cho trang báo cáo tồn kho và bán hàng mới
?>
<div class="p-4 sm:p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-green-800 flex items-center gap-2">
            <i class="fas fa-chart-bar"></i>
            Báo Cáo Bán Hàng
        </h1>
        <p class="text-gray-600 mt-2">Thống kê các báo giá và theo dõi tiến độ giao hàng của các đơn đã chốt.</p>
    </div>

    <!-- Khu vực bộ lọc -->
    <div class="p-6 bg-white rounded-xl shadow-lg mb-6">
        <div class="flex items-center gap-2 mb-4">
            <i class="fas fa-filter text-green-600"></i>
            <h2 class="text-xl font-semibold text-gray-800">Bộ Lọc</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Lọc theo ngày -->
            <div>
                <label for="report-start-date" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-calendar text-xs mr-1"></i>
                    Từ ngày
                </label>
                <input type="date" id="report-start-date" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
            </div>

            <div>
                <label for="report-end-date" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-calendar text-xs mr-1"></i>
                    Đến ngày
                </label>
                <input type="date" id="report-end-date" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
            </div>

            <!-- Lọc theo khách hàng -->
            <div>
                <label for="report-customer-select" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-building text-xs mr-1"></i>
                    Khách hàng
                </label>
                <select id="report-customer-select" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    <option value="">-- Tất cả khách hàng --</option>
                </select>
            </div>

            <!-- Lọc theo mã hàng -->
            <div>
                <label for="report-product-code" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-barcode text-xs mr-1"></i>
                    Mã hàng
                </label>
                <input type="text" id="report-product-code" placeholder="Tìm theo mã hàng..." 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
            </div>

            <!-- Lọc theo dự án -->
            <div>
                <label for="report-project-name" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-project-diagram text-xs mr-1"></i>
                    Tên dự án
                </label>
                <input type="text" id="report-project-name" placeholder="Tìm theo dự án..." 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
            </div>

            <!-- Lọc theo trạng thái báo giá -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-file-signature text-xs mr-1"></i>
                    Trạng thái báo giá
                </label>
                <div class="flex items-center gap-4 pt-1">
                    <label class="inline-flex items-center">
                        <input type="radio" name="quote_status" value="all" class="form-radio text-green-600" checked>
                        <span class="ml-2">Tất cả</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="quote_status" value="chua_chot" class="form-radio text-green-600">
                        <span class="ml-2">Chưa chốt</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="quote_status" value="da_chot" class="form-radio text-green-600">
                        <span class="ml-2">Đã chốt</span>
                    </label>
                </div>
            </div>

            <!-- Lọc theo trạng thái giao hàng-->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-truck text-xs mr-1"></i>
                    Trạng thái giao hàng (chỉ áp dụng cho đơn đã chốt)
                </label>
                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="delivery_status" value="all" class="form-radio text-green-600" checked>
                        <span class="ml-2">Tất cả</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="delivery_status" value="completed" class="form-radio text-green-600">
                        <span class="ml-2">Hoàn thành (100%)</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="delivery_status" value="partial" class="form-radio text-green-600">
                        <span class="ml-2">Giao 1 phần</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="delivery_status" value="pending" class="form-radio text-green-600">
                        <span class="ml-2">Chưa giao</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Các nút hành động -->
        <div class="flex flex-wrap gap-3 mt-6 pt-6 border-t">
            <button id="view-report-btn" class="px-6 py-2.5 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                <i class="fas fa-chart-line"></i>
                Xem Báo Cáo
            </button>
            <button id="export-excel-btn" class="px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <i class="fas fa-file-excel"></i>
                Xuất Excel (Theo mục đã chọn)
            </button>
            <button id="reset-filters-btn" class="px-6 py-2.5 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-redo mr-1"></i>
                Đặt Lại
            </button>
        </div>
    </div>
    
    <!-- Khu vực tổng hợp -->
    <div id="report-summary-container" class="mb-6"></div>

    <!-- Khu vực hiển thị kết quả báo cáo -->
    <div id="report-results-container">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <p class="text-center text-gray-500 py-8">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                Vui lòng chọn bộ lọc và nhấn "Xem Báo Cáo" để bắt đầu.
            </p>
        </div>
    </div>
</div>
