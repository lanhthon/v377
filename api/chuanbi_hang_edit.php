<style>
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.details-table input {
    width: 100%;
    padding: 4px 8px;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
}

.details-table input:focus {
    outline: 2px solid #3b82f6;
    border-color: transparent;
}
</style>

<div class="p-4 md:p-6 bg-gray-50 min-h-screen">
    <div class="flex justify-between items-center mb-6 bg-white p-4 rounded-lg shadow-sm">
        <div>
            <h1 id="page-title" class="text-2xl font-bold text-gray-800">Phiếu Chuẩn Bị Hàng</h1>
            <p id="order-id-display" class="text-gray-500"></p>
        </div>
        <div>
            <button id="save-chuanbi-btn"
                class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75">
                <i class="fas fa-save mr-2"></i>Lưu và Hoàn tất
            </button>
        </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
        <h2 class="text-lg font-semibold mb-3 border-b pb-2">Thông tin chung</h2>
        <div id="info-form" class="info-grid">
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm details-table">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left font-semibold">STT</th>
                    <th class="p-3 text-left font-semibold">Mã Hàng - Tên Sản Phẩm</th>
                    <th class="p-3 text-center font-semibold">SL Yêu Cầu</th>
                    <th class="p-3 text-center font-semibold">Số Thùng</th>
                    <th class="p-3 text-center font-semibold">Tồn Kho</th>
                    <th class="p-3 text-center font-semibold">Cây Cắt</th>
                    <th class="p-3 text-center font-semibold">Đóng Gói</th>
                    <th class="p-3 text-center font-semibold">Đặt Thêm</th>
                    <th class="p-3 text-center font-semibold">Số Kg</th>
                    <th class="p-3 text-left font-semibold">Ghi Chú</th>
                </tr>
            </thead>
            <tbody id="details-body">
            </tbody>
        </table>
    </div>
</div>