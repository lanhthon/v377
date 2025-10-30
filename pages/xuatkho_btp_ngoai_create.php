<?php
// File: pages/xuatkho_btp_ngoai_create.php
?>
<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-gray-800">Tạo Phiếu Xuất Kho BTP (Ngoài SX)</h2>
            <div>
                <button onclick="history.back()" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i> Quay Lại
                </button>
                <button id="save-xuatkho-btp-ngoai-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 ml-2">
                    <i class="fas fa-save mr-2"></i> Lưu Phiếu Xuất
                </button>
            </div>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="ngay-xuat" class="block text-sm font-medium text-gray-700 mb-1">Ngày Xuất Kho <span class="text-red-500">*</span></label>
                    <input type="date" id="ngay-xuat" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <label for="nguoi-nhan" class="block text-sm font-medium text-gray-700 mb-1">Người/Bộ phận nhận <span class="text-red-500">*</span></label>
                    <input type="text" id="nguoi-nhan" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="VD: Gửi mẫu, Chuyển kho...">
                </div>
                <div class="md:col-span-2">
                    <label for="ly-do-xuat" class="block text-sm font-medium text-gray-700 mb-1">Lý do xuất / Ghi chú</label>
                    <textarea id="ly-do-xuat" rows="2" class="w-full p-2 border border-gray-300 rounded-md shadow-sm"></textarea>
                </div>
            </div>

            <div class="mb-4">
                <button id="add-product-xuat-btp-btn" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                    <i class="fas fa-plus mr-2"></i> Thêm BTP
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse border border-gray-400">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 border">Mã BTP</th>
                            <th class="p-2 border">Tên Bán Thành Phẩm</th>
                            <th class="p-2 border w-40">Số Lượng Xuất</th>
                            <th class="p-2 border w-16">Xóa</th>
                        </tr>
                    </thead>
                    <tbody id="xuatkho-btp-ngoai-items-body"></tbody>
                     <tfoot class="bg-gray-50 font-semibold">
                        <tr>
                            <td colspan="2" class="p-2 text-right border">Tổng số lượng:</td>
                            <td id="total-quantity-xuat-btp" colspan="2" class="p-2 text-center border font-mono">0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="search-product-modal-xuat-btp" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="text-xl font-semibold">Tìm kiếm & Chọn Bán Thành Phẩm</h3>
            <button id="close-search-modal-xuat-btp" class="text-gray-500 hover:text-gray-800">&times;</button>
        </div>
        <div class="p-4">
            <input type="text" id="product-search-input-xuat-btp" class="w-full p-2 border border-gray-300 rounded-md" placeholder="Nhập mã hoặc tên BTP để tìm...">
        </div>
        <div id="product-search-results-xuat-btp" class="p-4 h-64 overflow-y-auto"></div>
    </div>
</div>