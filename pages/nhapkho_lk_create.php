<?php
// pages/nhapkho_lk_create.php
?>
<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 id="page-title" class="text-3xl font-bold text-gray-800">Tạo Phiếu Nhập Kho (LK)</h2>
            
            <div class="flex items-center space-x-2">
                <button id="back-to-lk-list-btn" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i> Quay Lại
                </button>
                <button id="save-nhapkho-lk-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i> Hoàn Tất Nhập Kho
                </button>
            </div>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-lg">
            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold">PHIẾU NHẬP KHO</h3>
                <p id="info-ngaynhap-lk" class="text-gray-600">Ngày ... tháng ... năm ...</p>
                <p class="text-gray-600">Số: <span id="info-sophieu-lk" class="font-semibold">...</span></p>
            </div>

            <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6 text-sm">
                <p><strong>Lý do nhập:</strong> <span id="info-lydo-lk">Nhập kho sản phẩm từ Lệnh Sản Xuất (LK)</span></p>
                <p><strong>Theo Lệnh SX số:</strong> <span id="info-lsx-lk" class="font-semibold">...</span></p>
                <p><strong>Người lập phiếu:</strong> <span id="info-nguoilap-lk">...</span></p>
                <p><strong>Nhập vào kho:</strong> <span id="info-kho-lk">Kho Thành Phẩm</span></p>
            </div>

            <table class="min-w-full border-collapse border border-gray-400">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 border">STT</th>
                        <th class="p-2 border">Mã Hàng</th>
                        <th class="p-2 border">Tên Sản Phẩm</th>
                        <th class="p-2 border">ĐVT</th>
                        <th class="p-2 border">SL Cần Nhập</th>
                        <th class="p-2 border">SL Thực Nhập</th>
                        <th class="p-2 border">Ghi Chú</th>
                    </tr>
                </thead>
                <tbody id="nhapkho-lk-items-body">
                    <!-- Dữ liệu sẽ được tải vào đây -->
                </tbody>
            </table>
        </div>
    </div>
</div>
