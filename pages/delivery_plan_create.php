<?php
// File: pages/delivery_plan_create.php
// Giao diện để tạo một Kế hoạch Giao hàng (Đợt giao hàng) mới.
?>

<div class="container mx-auto p-4 md:p-6 bg-gray-50 min-h-screen">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 id="page-title" class="text-3xl font-bold text-gray-800">Lập Kế Hoạch Giao Hàng Mới</h1>
            <p id="order-number-subtitle" class="text-lg text-gray-500">
                Cho đơn hàng: <span class="font-semibold">Đang tải...</span>
            </p>
        </div>
        <div>
            <button onclick="window.history.back()" class="inline-flex items-center px-4 py-2 mb-6 text-sm font-medium text-gray-600 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors shadow-sm">
    <i class="fas fa-arrow-left mr-2"></i>
    Quay lại
</button>
            <button id="save-delivery-plan-btn" class="mt-4 sm:mt-0 px-6 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 shadow-md transition-colors flex items-center gap-2">
                <i class="fas fa-save"></i> Lưu Kế Hoạch
            </button>
            </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 mb-6">
        <h2 class="text-xl font-bold text-gray-700 mb-4">Thông tin chung của đợt giao hàng</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="delivery-date" class="block text-sm font-medium text-gray-700">Ngày giao dự kiến</label>
                <input type="date" id="delivery-date" name="delivery_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <label for="delivery-status" class="block text-sm font-medium text-gray-700">Trạng thái</label>
                <select id="delivery-status" name="delivery_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                   
                    <option value="Chờ xử lý">Chờ xử lý</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label for="delivery-notes" class="block text-sm font-medium text-gray-700">Ghi chú</label>
                <textarea id="delivery-notes" name="delivery_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Thêm ghi chú cho đợt giao hàng..."></textarea>
            </div>
        </div>
    </div>
<p class="text-green-700 bg-green-100 border-l-4 border-green-500 p-4 mb-6 rounded-md shadow-sm font-medium">
    <i class="fas fa-info-circle mr-2"></i>
    **Lưu ý:** Kế hoạch giao hàng này đã bao gồm vật tư đi kèm tương ứng với số lượng sản phẩm.
</p>

    <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
         <h2 class="text-xl font-bold text-gray-700 mb-4">Chi tiết sản phẩm cho đợt giao này</h2>
         <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    </thead>
                <tbody id="delivery-items-body" class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td colspan="6" class="text-center py-10 text-gray-500">
                            <i class="fas fa-spinner fa-spin text-2xl"></i>
                            <p class="mt-2">Đang tải dữ liệu sản phẩm của đơn hàng...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

