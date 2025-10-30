<style>
    /* Bỏ CSS cũ của .info-table đi và thay bằng CSS này */
    .form-panel {
        background-color: #F8FAFC; /* Màu nền xám rất nhạt cho các cột */
        border: 1px solid #E5E7EB;
        border-radius: 0.75rem; /* Bo góc lớn hơn */
        padding: 1.25rem;
    }
    .field-group label {
        display: block;
        font-size: 0.875rem; /* 14px */
        font-weight: 500;
        color: #4B5563; /* text-gray-600 */
        margin-bottom: 0.25rem;
    }
    /* Áp dụng chung cho tất cả input trong form */
    .form-input {
        display: block;
        width: 100%;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        color: #1F2937;
        background-color: #FFFFFF;
        border: 1px solid #D1D5DB; /* border-gray-300 */
        border-radius: 0.375rem; /* rounded-md */
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    .form-input:focus {
        border-color: #3B82F6; /* focus:border-blue-500 */
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
    }
    .form-input:read-only {
        background-color: #F3F4F6; /* bg-gray-100 */
        cursor: not-allowed;
        color: #6B7280;
    }
</style>

<div class="container mx-auto p-4 md:p-6 bg-gray-50">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-center gap-4 border-b pb-4 mb-6">
   <a id="back-to-order-btn" href="#" class="flex items-center justify-center w-10 h-10 text-gray-600 bg-gray-200 rounded-full hover:bg-gray-300 transition-colors shadow-sm">
    <i class="fas fa-arrow-left"></i>
</a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Chi Tiết Đợt Giao Hàng</h1>
                <p id="plan-number" class="text-lg text-gray-600">Đang tải...</p>
            </div>
        </div>

        <div id="cbh-creation-section">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Tạo Phiếu Chuẩn Bị Hàng cho Đợt này</h2>
            <main class="mt-4">
                <div class="flex flex-col lg:flex-row gap-6">

                    <div class="flex-1 space-y-4 p-5 rounded-xl" style="background-color: #F1F8EC;">
                        <div class="field-group">
                            <label for="info-bophan">Bộ phận</label>
                            <input type="text" id="info-bophan" class="form-input required-field">
                        </div>
                        <div class="field-group">
                            <label for="info-phutrach">Phụ trách</label>
                            <input type="text" id="info-phutrach" class="form-input required-field">
                        </div>
                        <div class="field-group">
                            <label for="info-sodon">Số đơn YCSX</label>
                            <input type="text" id="info-sodon" class="form-input" readonly>
                        </div>
                        <div class="field-group">
                            <label for="info-madon">Mã đơn</label>
                            <input type="text" id="info-madon" class="form-input" readonly>
                        </div>
                    </div>

                    <div class="flex-1 space-y-4 p-5 rounded-xl" style="background-color: #E2F0D9;">
                        <div class="field-group">
                            <label for="info-ngaygui">Ngày gửi YCSX</label>
                            <input type="date" id="info-ngaygui" class="form-input required-field">
                        </div>
                         <div class="flex flex-col sm:flex-row gap-4">
                            <div class="field-group flex-1">
                                <label for="info-nguoinhan">Người nhận hàng</label>
                                <input type="text" id="info-nguoinhan" class="form-input required-field">
                            </div>
                            <div class="field-group flex-1">
                                <label for="info-sdtnguoinhan">Số điện thoại người nhận</label>
                                <input type="text" id="info-sdtnguoinhan" class="form-input">
                            </div>
                        </div>
                        <div class="field-group">
                            <label for="info-diadiem">Địa điểm giao hàng</label>
                            <input type="text" id="info-diadiem" class="form-input">
                        </div>
                        <div class="field-group">
                            <label for="info-quycachthung">Quy cách thùng</label>
                            <input type="text" id="info-quycachthung" class="form-input">
                        </div>
                    </div>

                    <div class="flex-1 space-y-4 p-5 rounded-xl" style="background-color: #F1F8EC;">
                        <div class="flex gap-4">
                            <div class="field-group flex-1">
                                <label for="info-ngaygiao">Ngày giao</label>
                                <input type="date" id="info-ngaygiao" class="form-input" readonly>
                            </div>
                            <div class="field-group flex-1">
                                <label for="info-dangkicongtruong">Đăng kí công trường</label>
                                <input type="text" id="info-dangkicongtruong" class="form-input">
                            </div>
                        </div>

                        <div class="p-3 border rounded-md bg-white space-y-3">
                            <p class="font-medium text-gray-800">Loại xe:</p>
                            <div class="flex items-center gap-2">
                                <label for="info-xegrap" class="w-24 text-sm text-gray-600">Xe Grap:</label>
                                <input type="text" id="info-xegrap" class="form-input flex-1">
                            </div>
                            <div class="flex items-center gap-2">
                                <label for="info-xetai" class="w-24 text-sm text-gray-600">Xe tải (tấn):</label>
                                <input type="text" id="info-xetai" class="form-input flex-1">
                            </div>
                        </div>

                        <div class="field-group">
                            <label for="info-solaixe">Số tài xế</label>
                            <input type="text" id="info-solaixe" class="form-input">
                        </div>
                        <div class="field-group">
                            <label for="info-congtrinh">Công trình/Dự án</label>
                            <input type="text" id="info-congtrinh" class="form-input" readonly>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-6 border-t mt-8">
                    <button id="save-and-create-cbh-btn" disabled class="px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-base font-semibold shadow-md transition-all duration-200 ease-in-out transform hover:scale-105">
                        <i class="fas fa-save mr-2"></i> Tạo Phiếu
                    </button>
                </div>
            </main>
        </div>

        <div id="cbh-created-section" class="hidden text-center p-8 bg-green-50 border border-green-200 rounded-lg">
            <i class="fas fa-check-circle text-5xl text-green-500"></i>
            <h2 class="mt-4 text-2xl font-bold text-green-800">Đã tạo Phiếu Chuẩn Bị Hàng cho đợt giao này!</h2>
            <p class="text-gray-600 mt-2">Hệ thống đang chuyển bạn đến trang quản lý phiếu...</p>
        </div>
        <div id="cbh-review-section" class="hidden mt-6">
            <div class="p-6 border-2 border-green-500 bg-green-50 rounded-lg shadow-lg">
                <div class="flex flex-wrap justify-between items-center mb-4 border-b pb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Xem lại Phiếu Chuẩn Bị Hàng</h2>
                        <p id="review-socbh" class="text-lg text-green-700 font-semibold"></p>
                    </div>
                    <div id="review-action-buttons" class="flex gap-3 mt-3 sm:mt-0">
                    </div>
                </div>

                <div id="review-details-container" class="text-sm">
                </div>

                <h3 class="text-lg font-semibold text-gray-700 mt-6 mb-2">Danh sách sản phẩm</h3>
                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="py-2 px-3 text-left">Mã Hàng</th>
                                <th class="py-2 px-3 text-left">Tên Sản Phẩm</th>
                                <th class="py-2 px-3 text-center">Số Lượng</th>
                            </tr>
                        </thead>
                        <tbody id="review-items-body" class="bg-white divide-y"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <h3 class="text-xl font-semibold text-gray-700 mt-8 mb-2">Các sản phẩm trong đợt giao này</h3>
        <div class="overflow-x-auto border rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-3 text-left">Sản phẩm</th>
                        <th class="py-2 px-3 text-center">Số lượng giao</th>
                    </tr>
                </thead>
                <tbody id="plan-items-body" class="bg-white divide-y"></tbody>
            </table>
        </div>
    </div>
</div>
