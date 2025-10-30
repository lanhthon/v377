
<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 id="page-title" class="text-3xl font-bold text-gray-800">Tạo Phiếu Nhập Kho Thành Phẩm</h2>
            
            <div class="flex items-center space-x-2">
                <div id="export-buttons-container" class="flex items-center space-x-2" style="display: none;">
    <button id="export-pnk-tp-pdf-btn" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">
        <i class="fas fa-file-pdf mr-2"></i> Xuất PDF
    </button>
    <button id="export-pnk-tp-excel-btn" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
        <i class="fas fa-file-excel mr-2"></i> Xuất Excel
    </button>
</div>

                <button id="back-to-tp-list-btn" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i> Quay Lại
                </button>
                <button id="save-nhapkho-tp-btn" data-ycsx-id="<?php echo $ycsx_id; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i> Hoàn Tất Nhập Kho
                </button>
            </div>
        </div>

    

        </div>

        <div id="pnk-tp-form" class="bg-white p-8 rounded-lg shadow-lg">
            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold">PHIẾU NHẬP KHO THÀNH PHẨM</h3>
                <p id="info-ngaynhap-tp" class="text-gray-600">Ngày ... tháng ... năm ...</p>
                <p class="text-gray-600">Số: <span id="info-sophieu-tp" class="font-semibold">...</span></p>
            </div>

            <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6 text-sm">
                <p><strong>Lý do nhập:</strong> <span id="info-lydo-tp">Nhập kho thành phẩm từ sản xuất</span></p>
                <p><strong>Theo YCSX số:</strong> <span id="info-ycsx-tp" class="font-semibold">...</span></p>
                <p><strong>Người lập phiếu:</strong> <span id="info-nguoilap-tp">...</span></p>
                <p><strong>Nhập vào kho:</strong> <span id="info-kho-tp">Kho Thành Phẩm</span></p>
            </div>

            <table class="min-w-full border-collapse border border-gray-400">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 border">STT</th>
                        <th class="p-2 border">Mã Hàng</th>
                        <th class="p-2 border">Tên Thành Phẩm</th>
                        <th class="p-2 border">ĐVT</th>
                        <th class="p-2 border">SL Theo Đơn Hàng</th>
                        <th class="p-2 border">SL Thực Nhập</th>
                        <th class="p-2 border">Ghi Chú</th>
                    </tr>
                </thead>
                <tbody id="nhapkho-tp-items-body"></tbody>
            </table>
        </div>
    </div>
</div>
