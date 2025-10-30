<?php
// File: pages/nhapkho_tp_ngoai_create.php
?>
<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-gray-800">Tạo Phiếu Nhập Kho TP (Ngoài SX)</h2>
            <div>
                <button onclick="history.back()" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i> Quay Lại
                </button>
                <button id="save-nhapkho-tp-ngoai-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 ml-2">
                    <i class="fas fa-save mr-2"></i> Lưu Phiếu Nhập
                </button>
            </div>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="ngay-nhap-tp" class="block text-sm font-medium text-gray-700 mb-1">Ngày Nhập Kho <span class="text-red-500">*</span></label>
                    <input type="date" id="ngay-nhap-tp" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <label for="nguoi-giao-tp" class="block text-sm font-medium text-gray-700 mb-1">Người/Nguồn Giao Hàng</label>
                    <input type="text" id="nguoi-giao-tp" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="VD: Hàng mẫu, Hàng khuyến mãi...">
                </div>
                <div class="md:col-span-2">
                    <label for="ly-do-nhap-tp" class="block text-sm font-medium text-gray-700 mb-1">Lý do nhập / Ghi chú <span class="text-red-500">*</span></label>
                    <textarea id="ly-do-nhap-tp" rows="2" class="w-full p-2 border border-gray-300 rounded-md shadow-sm"></textarea>
                </div>
            </div>

            <div class="mb-4">
                <button id="add-product-tp-btn" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                    <i class="fas fa-plus mr-2"></i> Thêm Thành Phẩm
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse border border-gray-400">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 border">Mã Hàng</th>
                            <th class="p-2 border">Tên Thành Phẩm</th>
                            <th class="p-2 border w-40">Số Lượng Nhập</th>
                            <th class="p-2 border w-16">Xóa</th>
                        </tr>
                    </thead>
                    <tbody id="nhapkho-tp-ngoai-items-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="search-product-modal-tp" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="text-xl font-semibold">Tìm kiếm & Chọn Thành Phẩm</h3>
            <button id="close-search-modal-tp" class="text-gray-500 hover:text-gray-800">&times;</button>
        </div>
        <div class="p-4">
            <input type="text" id="product-search-input-tp" class="w-full p-2 border border-gray-300 rounded-md" placeholder="Nhập mã hoặc tên Thành Phẩm để tìm...">
        </div>
        <div id="product-search-results-tp" class="p-4 h-64 overflow-y-auto"></div>
    </div>
</div>