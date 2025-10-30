<?php
// File: pages/nhapkho_btp_create.php
// Trang này dùng để tạo hoặc xem phiếu nhập kho BTP.
// ID sẽ được lấy bởi JavaScript từ URL.
?>
<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 id="page-title" class="text-3xl font-bold text-gray-800">Tạo Phiếu Nhập Kho BTP</h2>
            <div>
                <button id="back-to-list-btn" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Quay Lại
                </button>
                <button id="export-pnk-btp-pdf-btn" class="action-btn bg-red-500 text-white" style="display: none;">
    <i class="fas fa-file-pdf mr-1"></i> Xuất PDF
</button>
<button id="export-pnk-btp-excel-btn" class="action-btn bg-green-600 text-white" style="display: none;">
    <i class="fas fa-file-excel mr-1"></i> Xuất Excel
</button>
                <button id="save-nhapkho-btp-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors ml-2">
                    <i class="fas fa-save mr-2"></i> Hoàn Tất Nhập Kho
                </button>
            </div>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-lg printable-area">
            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold">PHIẾU NHẬP KHO BÁN THÀNH PHẨM</h3>
                <p id="info-ngaynhap" class="text-gray-600">Ngày ... tháng ... năm ...</p>
                <p class="text-gray-600">Số: <span id="info-sophieu" class="font-semibold">...</span></p>
            </div>

            <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6 text-sm">
                <p><strong>Lý do nhập:</strong> <span id="info-lydo">...</span></p>
                <p><strong>Theo Lệnh SX số:</strong> <span id="info-lenhsx" class="font-semibold">...</span></p>
                <p><strong>Người lập phiếu:</strong> <span id="info-nguoilap">...</span></p>
                <p><strong>Người giao hàng:</strong> <span id="info-nguoigiao" class="font-semibold">...</span></p>
                <p class="col-span-2"><strong>Ghi chú chung:</strong> <span id="info-ghichu">...</span></p>
                <p><strong>Nhập vào kho:</strong> <span id="info-kho">Kho Bán Thành Phẩm</span></p>
                <p id="container-tongtien" class="col-span-2" style="display: none;"><strong>Tổng tiền:</strong> <span id="info-tongtien" class="font-semibold text-red-600">...</span></p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse border border-gray-400">
                    <thead id="nhapkho-btp-items-header" class="bg-gray-100">
                        <tr>
                            <th class="p-2 border border-gray-300">STT</th>
                            <th class="p-2 border border-gray-300">Mã BTP</th>
                            <th class="p-2 border border-gray-300">Tên Bán Thành Phẩm</th>
                            <th class="p-2 border border-gray-300">ĐVT</th>
                            <th class="p-2 border border-gray-300">SL Theo Lệnh SX</th>
                            <th class="p-2 border border-gray-300">SL Thực Nhập</th>
                            <th class="p-2 border border-gray-300">Ghi Chú</th>
                        </tr>
                    </thead>
                    <tbody id="nhapkho-btp-items-body">
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

