<?php
// File: pages/nhapkho_vattu_create.php
// Trang tạo và chỉnh sửa phiếu nhập kho vật tư.
$pnk_id = isset($_GET['pnk_id']) ? intval($_GET['pnk_id']) : 0;
?>
<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 id="page-title-vattu" class="text-3xl font-bold text-gray-800">Tạo Phiếu Nhập Kho Vật Tư</h2>
            <div>
                <button id="back-to-vattu-list-btn" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Quay Lại
                </button>
                <button id="save-nhapkho-vattu-btn" data-pnk-id="<?php echo $pnk_id; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors ml-2">
                    <i class="fas fa-save mr-2"></i> Lưu Phiếu Nhập
                </button>
            </div>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-lg">
            <!-- Header của phiếu nhập -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 border-b pb-6">
                <div>
                    <label for="nha-cung-cap" class="block text-sm font-medium text-gray-700 mb-1">Nhà Cung Cấp</label>
                    <select id="nha-cung-cap" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Chọn nhà cung cấp</option>
                    </select>
                </div>
                <div>
                    <label for="ngay-nhap" class="block text-sm font-medium text-gray-700 mb-1">Ngày Nhập</label>
                    <input type="date" id="ngay-nhap" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="so-hoa-don" class="block text-sm font-medium text-gray-700 mb-1">Số Hóa Đơn NCC</label>
                    <input type="text" id="so-hoa-don" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="VD: HD00123">
                </div>
                 <div>
                    <label for="nguoi-giao-hang" class="block text-sm font-medium text-gray-700 mb-1">Người Giao Hàng</label>
                    <input type="text" id="nguoi-giao-hang" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="Tên người giao...">
                </div>
                <div class="col-span-2">
                    <label for="ly-do-nhap" class="block text-sm font-medium text-gray-700 mb-1">Lý Do Nhập</label>
                    <input type="text" id="ly-do-nhap" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="Nhập hàng theo đơn đặt hàng...">
                </div>
            </div>

            <!-- Bảng chi tiết sản phẩm -->
            <h3 class="text-xl font-semibold mb-4 text-gray-700">Chi Tiết Vật Tư Nhập Kho</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 border text-left">Mã Vật Tư</th>
                            <th class="p-2 border text-left">Tên Vật Tư</th>
                            <th class="p-2 border" style="width: 120px;">Số Lượng</th>
                            <th class="p-2 border" style="width: 150px;">Đơn Giá</th>
                            <th class="p-2 border" style="width: 180px;">Thành Tiền</th>
                            <th class="p-2 border" style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="nhapkho-vattu-items-body">
                        <!-- Các dòng sản phẩm sẽ được thêm vào đây -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6">
                                <button id="add-product-vattu-btn" class="mt-4 bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                                    <i class="fas fa-plus mr-2"></i>Thêm Vật Tư
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-right font-bold p-3 border-t-2">TỔNG CỘNG:</td>
                            <td id="total-amount-vattu" class="text-right font-bold text-xl p-3 border-t-2">0</td>
                            <td class="border-t-2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal tìm kiếm sản phẩm -->
<div id="search-product-modal-vattu" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
        <div class="p-4 border-b">
            <h3 class="text-lg font-semibold">Tìm kiếm Vật tư</h3>
        </div>
        <div class="p-4">
            <input type="text" id="product-search-input-vattu" class="w-full border-gray-300 rounded-md p-2" placeholder="Nhập mã hoặc tên vật tư để tìm...">
            <div id="product-search-results-vattu" class="mt-4 max-h-60 overflow-y-auto"></div>
        </div>
        <div class="p-4 border-t text-right">
            <button id="close-search-modal-vattu" class="bg-gray-300 px-4 py-2 rounded-md hover:bg-gray-400">Đóng</button>
        </div>
    </div>
</div>
