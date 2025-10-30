<?php
// File: pages/reports.php
// Giao diện mới cho chức năng báo cáo, tổng hợp dữ liệu.
?>



<div class="p-4 sm:p-6 bg-gray-50 min-h-screen">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
        <h1 class="text-3xl font-bold text-green-800">Trang Báo Cáo & Thống Kê</h1>
        <button id="manage-revenue-plan-btn" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
            <i class="fas fa-cog mr-2"></i>Quản lý Kế hoạch Doanh thu
        </button>
    </div>

    <!-- Revenue Plan Management Modal -->
    <div id="revenue-plan-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-screen overflow-y-auto">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-800">Quản lý Kế hoạch Doanh thu</h2>
                        <button id="close-revenue-plan-modal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                
                <div class="p-6">
                    <!-- Control Panel -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-4">
                                <label for="yearSelect" class="font-medium text-gray-700">Năm:</label>
                                <select id="yearSelect" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <!-- Will be populated by JavaScript -->
                                </select>
                            </div>
                            <div class="space-x-2">
                                <button id="loadPlanBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                    <i class="fas fa-search mr-2"></i>Tải kế hoạch
                                </button>
                                <button id="savePlanBtn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                    <i class="fas fa-save mr-2"></i>Lưu kế hoạch
                                </button>
                                <button id="autoDistributeBtn" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                                    <i class="fas fa-magic mr-2"></i>Phân bổ tự động
                                </button>
                            </div>
                        </div>
                    </div>

                    <form id="revenuePlanForm">
                        <!-- Annual Target -->
                        <div class="mb-6">
                            <label for="annualTarget" class="block text-sm font-medium text-gray-700 mb-2">
                                Mục tiêu doanh thu năm <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" id="annualTarget" name="MucTieuDoanhthu" 
                                       class="w-full px-4 py-3 text-lg border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" 
                                       placeholder="Nhập mục tiêu doanh thu năm" min="0" step="1000000">
                                <span class="absolute right-3 top-3 text-gray-500">VND</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Mục tiêu tổng doanh thu cho cả năm</p>
                        </div>

                        <!-- Monthly Targets -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Phân bổ mục tiêu theo tháng</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <!-- Q1 -->
                                <div class="space-y-3">
                                    <h4 class="font-medium text-blue-700 text-center border-b border-blue-200 pb-2">Quý 1</h4>
                                    <div>
                                        <label for="month1" class="block text-sm text-gray-600">Tháng 1</label>
                                        <input type="number" id="month1" name="MucTieuThang1" class="monthly-target w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" min="0" step="1000000">
                                    </div>
                                    <div>
                                        <label for="month2" class="block text-sm text-gray-600">Tháng 2</label>
                                        <input type="number" id="month2" name="MucTieuThang2" class="monthly-target w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" min="0" step="1000000">
                                    </div>
                                    <div>
                                        <label for="month3" class="block text-sm text-gray-600">Tháng 3</label>
                                        <input type="number" id="month3" name="MucTieuThang3" class="monthly-target w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" min="0" step="1000000">
                                    </div>
                                </div>

                                <!-- Q2 -->
                                <div class="space-y-3">
                                    <h4 class="font-medium text-green-700 text-center border-b border-green-200 pb-2">Quý 2</h4>
                                    <div>
                                        <label for="month4" class="block text-sm text-gray-600">Tháng 4</label>
                                        <input type="number" id="month4" name="MucTieuThang4" class="monthly-target w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" min="0" step="1000000">
                                    </div>
                                    <div>
                                        <label for="month5" class="block text-sm text-gray-600">Tháng 5</label>
                                        <input type="number" id="month5" name="MucTieuThang5" class="monthly-target w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" min="0" step="1000000">
                                    </div>
                                    <div>
                                        <label for="month6" class="block text-sm text-gray-600">Tháng 6</label>
                                        <input type="number" id="month6" name="MucTieuThang6" class="monthly-target w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" min="0" step="1000000">
                                    </div>
                                </div>

                                <!-- Q3 -->
                                <div class="space-y-3">
                                    <h4 class="font-medium text-yellow-700 text-center border-b border-yellow-200 pb-2">Quý 3</h4>
                                    <div>
                                        <label for="month7" class="block text-sm text-gray-600">Tháng 7</label>
                                        <input type="number" id="month7" name="MucTieuThang7" class="monthly-target w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" min="0" step="1000000">
                                    </div>
                                    <div>
                                        <label for="month8" class="block text-sm text-gray-600">Tháng 8</label>
                                        <input type="number" id="month8" name="MucTieuThang8" class="monthly-target w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" min="0" step="1000000">
                                    </div>
                                    <div>
                                        <label for="month9" class="block text-sm text-gray-600">Tháng 9</label>
                                        <input type="number" id="month9" name="MucTieuThang9" class="monthly-target w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" min="0" step="1000000">
                                    </div>
                                </div>

                                <!-- Q4 -->
                                <div class="space-y-3">
                                    <h4 class="font-medium text-red-700 text-center border-b border-red-200 pb-2">Quý 4</h4>
                                    <div>
                                        <label for="month10" class="block text-sm text-gray-600">Tháng 10</label>
                                        <input type="number" id="month10" name="MucTieuThang10" class="monthly-target w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" min="0" step="1000000">
                                    </div>
                                    <div>
                                        <label for="month11" class="block text-sm text-gray-600">Tháng 11</label>
                                        <input type="number" id="month11" name="MucTieuThang11" class="monthly-target w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" min="0" step="1000000">
                                    </div>
                                    <div>
                                        <label for="month12" class="block text-sm text-gray-600">Tháng 12</label>
                                        <input type="number" id="month12" name="MucTieuThang12" class="monthly-target w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" min="0" step="1000000">
                                    </div>
                                </div>
                            </div>

                            <!-- Monthly Summary -->
                            <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <span class="font-medium text-blue-800">Tổng mục tiêu từ các tháng:</span>
                                    <span id="monthlyTotal" class="font-bold text-blue-900 text-lg">0 VND</span>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-sm text-blue-700">Chênh lệch với mục tiêu năm:</span>
                                    <span id="difference" class="font-medium text-sm">0 VND</span>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-6">
                            <label for="planNotes" class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                            <textarea id="planNotes" name="GhiChu" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" 
                                      placeholder="Nhập ghi chú về kế hoạch doanh thu..."></textarea>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Tổng Quan Nhanh</h2>
        <div id="dashboard-stats-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="p-5 bg-white rounded-xl shadow-md text-center animate-pulse"><p class="text-gray-500">Đang tải...</p></div>
            <div class="p-5 bg-white rounded-xl shadow-md text-center animate-pulse"><p class="text-gray-500">Đang tải...</p></div>
            <div class="p-5 bg-white rounded-xl shadow-md text-center animate-pulse"><p class="text-gray-500">Đang tải...</p></div>
            <div class="p-5 bg-white rounded-xl shadow-md text-center animate-pulse"><p class="text-gray-500">Đang tải...</p></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-md p-6 h-full">
                <div class="text-center mb-4">
                    <h3 class="font-bold text-gray-800 text-lg">🎯 Mục Tiêu Doanh Thu 2025</h3>
                    <p class="text-sm text-gray-600">Tích lũy từ đầu năm</p>
                </div>
                
                <div class="relative mx-auto mb-4" style="width: 280px; height: 280px;">
                    <canvas id="annualTargetChart"></canvas>
                </div>
                
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Tiến độ</span>
                        <span id="progress-percentage">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div id="progress-bar" class="bg-blue-500 h-3 rounded-full transition-all duration-500" style="width: 0%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span>0₫</span>
                        <span id="progress-target-value">10 tỷ</span>
                    </div>
                </div>
                
                <div id="annual-target-info" class="mt-4">
                    <div class="text-center">
                        <div class="text-xs text-gray-500">Đang tải dữ liệu...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md p-6 h-full">
                <h3 class="text-lg font-bold text-gray-800 mb-4 text-center">
                    📊 Doanh Thu Theo Tháng - PUR & ULA
                </h3>
                <div class="relative" style="height: 400px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-8 p-6 bg-white rounded-xl shadow-lg">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">🔍 Phân Tích Báo Giá Chi Tiết</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 items-end gap-4 mb-6 p-4 bg-gray-50 rounded-lg border">
            <div class="md:col-span-2">
                <label for="report-customer-select" class="block text-sm font-medium text-gray-600">Khách hàng</label>
                <select id="report-customer-select" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"></select>
            </div>
            <div>
                <label for="report-start-date" class="block text-sm font-medium text-gray-600">Từ ngày</label>
                <input type="date" id="report-start-date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label for="report-end-date" class="block text-sm font-medium text-gray-600">Đến ngày</label>
                <input type="date" id="report-end-date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
            </div>
        </div>

        <div class="flex flex-wrap justify-center items-center gap-4 mb-6">
            <button id="view-quote-report-btn" class="w-full sm:w-auto flex-grow px-8 py-3 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 transition-colors">
                <i class="fas fa-chart-line mr-2"></i>Xem Báo Cáo
            </button>
            <button id="export-excel-btn" class="w-full sm:w-auto flex-grow px-8 py-3 bg-blue-700 text-white font-semibold rounded-lg shadow-md hover:bg-blue-800 transition-colors">
                <i class="fas fa-file-excel mr-2"></i>Xuất Excel
            </button>
        </div>

        <div id="quote-report-results-container" class="hidden mt-6 border-t border-gray-200 pt-6"></div>
    </div>
    
    <div class="mb-8">
         <div class="p-6 bg-white rounded-xl shadow-md">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">🏆 Top Khách Hàng</h2>
            <div id="customer-report-container">
                <p class="text-center p-4 text-gray-500">Đang tải...</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="p-6 bg-white rounded-xl shadow-md">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">🏭 Tình Hình Sản Xuất</h2>
            <div id="production-report-container">
                <p class="text-center p-4 text-gray-500">Đang tải...</p>
            </div>
        </div>

        <div class="space-y-6">
            <div class="p-6 bg-white rounded-xl shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">⚠️ Cảnh Báo Tồn Kho</h2>
                <div id="inventory-report-container">
                    <p class="text-center p-4 text-gray-500">Đang tải...</p>
                </div>
            </div>
            
            <div class="p-6 bg-white rounded-xl shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">📈 Top Sản Phẩm</h2>
                <div id="top-products-report-container">
                    <p class="text-center p-4 text-gray-500">Đang tải...</p>
                </div>
            </div>
        </div>
    </div>

    <div id="chart-placeholder" class="hidden"></div>
    <div id="chart-canvas-wrapper" class="hidden"></div>
</div>