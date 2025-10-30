<?php
// File: pages/xuatkho_btp_view.php
// Trang xem chi tiết một phiếu xuất kho BTP.
$pxk_id = isset($_GET['pxk_id']) ? intval($_GET['pxk_id']) : 0;
?>
<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-gray-800">Xem Phiếu Xuất Kho BTP</h2>
            <button id="back-to-btp-list-btn" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i> Quay Lại Danh Sách
            </button>
        </div>

        <div id="action-buttons-container" class="mb-4 flex justify-end">
            </div>

        <div class="bg-white p-8 rounded-lg shadow-lg">
            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold">PHIẾU XUẤT KHO BÁN THÀNH PHẨM</h3>
                <p class="text-gray-600">Ngày <span id="info-ngayxuat">...</span></p>
                <p class="text-gray-600">Số: <span id="info-sophieu" class="font-semibold">...</span></p>
            </div>

            <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6 text-sm">
                <p><strong>Người nhận:</strong> <span id="info-nguoinhan">Bộ phận cắt</span></p>
                <p><strong>Theo YCSX số:</strong> <span id="info-ycsx" class="font-semibold">...</span></p>
                <p><strong>Người lập phiếu:</strong> <span id="info-nguoilap">...</span></p>
                <p><strong>Lý do xuất:</strong> <span id="info-lydo">Xuất BTP để cắt thành phẩm</span></p>
            </div>

            <table class="min-w-full border-collapse border border-gray-400">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 border">STT</th>
                        <th class="p-2 border">Mã BTP</th>
                        <th class="p-2 border">Tên Bán Thành Phẩm</th>
                        <th class="p-2 border">ĐVT</th>
                        <th class="p-2 border">Số Lượng Xuất</th>
                    </tr>
                </thead>
                <tbody id="xuatkho-btp-items-body">
                    </tbody>
            </table>
        </div>
    </div>
</div>