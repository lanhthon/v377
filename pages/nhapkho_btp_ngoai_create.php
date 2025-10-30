<?php
// pages/nhapkho_btp_ngoai_create.php
// Giao diện tạo phiếu nhập kho BTP từ NCC, có pop-up tìm kiếm sản phẩm.
?>
<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-gray-800">Tạo Phiếu Nhập Kho BTP (Từ NCC)</h2>
            <div>
                <button onclick="history.back()" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i> Quay Lại
                </button>
                <button id="save-nhapkho-btp-ngoai-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 ml-2">
                    <i class="fas fa-save mr-2"></i> Lưu Phiếu Nhập
                </button>
            </div>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="nha-cung-cap" class="block text-sm font-medium text-gray-700 mb-1">Nhà Cung Cấp <span class="text-red-500">*</span></label>
                    <select id="nha-cung-cap" class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                        <option value="">Chọn nhà cung cấp...</option>
                    </select>
                </div>
                <div>
                    <label for="ngay-nhap" class="block text-sm font-medium text-gray-700 mb-1">Ngày Nhập Kho <span class="text-red-500">*</span></label>
                    <input type="date" id="ngay-nhap" class="w-full p-2 border border-gray-300 rounded-md shadow-sm" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <label for="nguoi-giao-hang" class="block text-sm font-medium text-gray-700 mb-1">Người Giao Hàng</label>
                    <input type="text" id="nguoi-giao-hang" class="w-full p-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                 <div>
                    <label for="ghi-chu-chung" class="block text-sm font-medium text-gray-700 mb-1">Ghi Chú Chung</label>
                    <textarea id="ghi-chu-chung" rows="1" class="w-full p-2 border border-gray-300 rounded-md shadow-sm"></textarea>
                </div>
            </div>

            <div class="mb-4">
                <button id="add-product-btp-btn" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                    <i class="fas fa-plus mr-2"></i> Thêm BTP
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse border border-gray-400">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 border">Mã BTP</th>
                            <th class="p-2 border">Tên Bán Thành Phẩm</th>
                            <th class="p-2 border w-32">Số Lượng</th>
                            <th class="p-2 border w-40">Đơn Giá</th>
                            <th class="p-2 border w-40">Thành Tiền</th>
                            <th class="p-2 border w-16">Xóa</th>
                        </tr>
                    </thead>
                    <tbody id="nhapkho-btp-ngoai-items-body">
                        </tbody>
                     <tfoot class="bg-gray-50 font-semibold">
                        <tr>
                            <td colspan="4" class="p-2 text-right border">Tổng cộng:</td>
                            <td id="tong-tien-nhap" colspan="2" class="p-2 text-right border font-mono">0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="search-product-modal-btp" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="text-xl font-semibold">Tìm kiếm & Chọn Bán Thành Phẩm</h3>
            <button id="close-search-modal-btp" class="text-gray-500 hover:text-gray-800">&times;</button>
        </div>
        <div class="p-4">
            <input type="text" id="product-search-input-btp" class="w-full p-2 border border-gray-300 rounded-md" placeholder="Nhập mã hoặc tên BTP để tìm...">
        </div>
        <div id="product-search-results-btp" class="p-4 h-64 overflow-y-auto">
            </div>
    </div>
</div>