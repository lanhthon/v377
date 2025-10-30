<?php
// Đây là một tệp PHP để bao gồm header, footer và JavaScript.
// Nội dung HTML bên dưới sẽ là phần chính của trang.
?>

<div id="donhang-view-container" class="container mx-auto p-4 md:p-6 bg-gray-50">
    <div class="bg-white p-6 rounded-2xl shadow-lg">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6">
            <div>
                
                <h1 id="page-title" class="text-3xl font-bold text-gray-800">Chi tiết Đơn hàng</h1>
                 <button onclick="goBackAndReload()" class="flex items-center justify-center w-10 h-10 text-gray-600 bg-gray-200 rounded-full hover:bg-gray-300 transition-colors shadow-sm">
                <i class="fas fa-arrow-left"></i>
            </button>
                <p id="order-number-subtitle" class="text-xl text-gray-600 mt-2"></p>
            </div>
            <div id="action-buttons-container" class="flex gap-3 mt-4 sm:mt-0">
                </div>
        </div>

        <hr class="my-6 border-gray-200">

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

            <div class="p-4 rounded-lg" style="background-color: #E2F0D9;">
                <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                    <i class="fas fa-building text-green-700 w-4 text-center"></i>
                    <span>Tên công ty khách hàng</span>
                </div>
                <p id="info-customer-name" class="mt-1 text-lg font-semibold text-gray-900 truncate"></p>
            </div>

            <div class="p-4 rounded-lg" style="background-color: #F1F8EC;">
                <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                    <i class="fas fa-project-diagram text-green-700 w-4 text-center"></i>
                    <span>Tên dự án</span>
                </div>
                <p id="info-project-name" class="mt-1 text-lg font-semibold text-gray-900 truncate"></p>
            </div>
            
            <div class="p-4 rounded-lg" style="background-color: #E2F0D9;">
                <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                    <i class="fas fa-calendar-alt text-green-700 w-4 text-center"></i>
                    <span>Ngày đặt hàng</span>
                </div>
                <p id="info-order-date" class="mt-1 text-lg font-semibold text-gray-900"></p>
            </div>

            <div class="p-4 rounded-lg" style="background-color: #F1F8EC;">
                <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                    <i class="fas fa-shipping-fast text-green-700 w-4 text-center"></i>
                    <span>Ngày giao dự kiến</span>
                </div>
                <p id="info-delivery-date" class="mt-1 text-lg font-semibold text-gray-900"></p>
            </div>
            
            <div class="p-4 rounded-lg" style="background-color: #E2F0D9;">
                <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                    <i class="fas fa-dollar-sign text-green-700 w-4 text-center"></i>
                    <span>Tổng giá trị</span>
                </div>
                <p id="info-total-value" class="mt-1 text-lg font-bold text-blue-600"></p>
            </div>

            <div class="p-4 rounded-lg" style="background-color: #F1F8EC;">
                <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                    <i class="fas fa-info-circle text-green-700 w-4 text-center"></i>
                    <span>Trạng thái</span>
                </div>
                <div id="info-status" class="mt-1">
                     <span class="status-badge px-3 py-1 text-sm font-semibold rounded-full bg-gray-200 text-gray-800"></span>
                </div>
            </div>
        </div>
        
        <!-- === KHU VỰC THAY ĐỔI === -->
        <div class="mt-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Kế hoạch Giao hàng</h3>
                <div class="flex items-center gap-2">
                    <!-- Nút mới được thêm vào đây -->
                    <button id="check-progress-btn" class="px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 shadow-sm transition-colors flex items-center gap-2">
                        <i class="fas fa-tasks"></i> Kiểm tra Tiến độ
                    </button>
                    <!-- Nút cũ -->
                    <a href="#" id="create-delivery-plan-btn" class="px-4 py-2 bg-teal-500 text-white rounded-md hover:bg-teal-600 shadow-sm transition-colors flex items-center gap-2">
                        <i class="fas fa-shipping-fast"></i> Tạo Đợt Giao Hàng
                    </a>
                </div>
            </div>
            <div id="delivery-shipments-list" class="space-y-4">
                </div>
        </div>
        <!-- === KẾT THÚC KHU VỰC THAY ĐỔI === -->

        <div id="delivery-plans-section" class="mt-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Các Đợt Giao Hàng Đã Lên Kế Hoạch</h3>
            <div id="delivery-plans-list" class="space-y-4">
                <p class="text-gray-500">Đang tải danh sách các đợt giao hàng...</p>
            </div>
        </div>
        <hr class="my-6 border-gray-200">

    </div>
</div>
