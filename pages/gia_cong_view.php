<!--
    Trang chi tiết và nhập kho phiếu gia công mạ nhúng nóng
    File: pages/gia_cong_view.php
-->

<div id="gia-cong-view-container" class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-file-invoice text-orange-500 mr-2"></i>
                    Chi Tiết Phiếu Gia Công
                </h1>
                <p class="text-gray-600 mt-2">Xem thông tin và nhập kho sản phẩm sau gia công</p>
            </div>
            <div>
                <a href="?page=gia_cong_list" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Quay lại
                </a>
            </div>
        </div>
    </div>

    <!-- Loading state -->
    <div id="loading-section" class="bg-white rounded-lg shadow-md p-10 text-center">
        <i class="fas fa-spinner fa-spin text-4xl text-orange-500 mb-4"></i>
        <p class="text-gray-600">Đang tải thông tin phiếu...</p>
    </div>

    <!-- Main content (hidden initially) -->
    <div id="main-content" class="hidden">
        <!-- Thông tin phiếu -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Thông Tin Phiếu</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="phieu-info">
                <!-- Sẽ được fill bằng JavaScript -->
            </div>
        </div>

        <!-- Chi tiết sản phẩm -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Chi Tiết Sản Phẩm</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Sản phẩm xuất -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-box text-blue-500 mr-2"></i>
                        Sản Phẩm Xuất (Mạ Điện Phân)
                    </h3>
                    <div id="san-pham-xuat"></div>
                </div>

                <!-- Sản phẩm nhận -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-box-open text-orange-500 mr-2"></i>
                        Sản Phẩm Nhận (Mạ Nhúng Nóng)
                    </h3>
                    <div id="san-pham-nhan"></div>
                </div>
            </div>
        </div>

        <!-- Tiến độ gia công -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Tiến Độ Gia Công</h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-gray-700">Số lượng xuất:</span>
                    <span class="font-bold text-blue-600" id="sl-xuat">0</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-700">Đã nhập về:</span>
                    <span class="font-bold text-green-600" id="sl-nhap">0</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-700">Còn lại:</span>
                    <span class="font-bold text-red-600" id="sl-con-lai">0</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div id="progress-bar" class="bg-green-500 h-4 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <div class="text-center">
                    <span id="progress-text" class="text-sm font-medium text-gray-700">0%</span>
                </div>
            </div>
        </div>

        <!-- Form nhập kho -->
        <div id="form-nhap-kho-section" class="bg-gradient-to-r from-orange-50 to-orange-100 rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-warehouse mr-2"></i>
                Nhập Kho Sau Gia Công
            </h2>
            <div class="bg-white rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng nhập: <span class="text-red-500">*</span></label>
                        <input type="number" id="input-so-luong-nhap" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Nhập số lượng">
                        <p class="text-xs text-gray-500 mt-1">Tối đa: <span id="max-nhap">0</span></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ngày nhập:</label>
                        <input type="date" id="input-ngay-nhap" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú:</label>
                    <textarea id="input-ghi-chu" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Ghi chú về quá trình gia công, hao hụt (nếu có)..."></textarea>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button id="btn-nhap-kho" class="px-6 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <i class="fas fa-check mr-2"></i>
                        Xác Nhận Nhập Kho
                    </button>
                </div>
            </div>
        </div>

        <!-- Lịch sử gia công -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Lịch Sử Gia Công</h2>
            <div id="lich-su-container">
                <p class="text-gray-500 text-sm">Đang tải lịch sử...</p>
            </div>
        </div>
    </div>
</div>

<script>
// Load file JavaScript riêng cho trang này
$(document).ready(function() {
    if (typeof initGiaCongViewPage === 'function') {
        initGiaCongViewPage($('#gia-cong-view-container'));
    }
});
</script>
