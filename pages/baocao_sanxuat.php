<?php
// pages/baocao_sanxuat.php
?>
<div class="p-4 sm:p-6 lg:p-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Báo cáo Sản lượng Sản xuất Hàng ngày</h1>
            <p class="mt-2 text-sm text-gray-700">Xem lại chi tiết sản lượng đã được báo cáo theo từng ngày.</p>
        </div>
    </div>

    <div class="mt-4 p-4 bg-white rounded-lg shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="report-start-date" class="block text-sm font-medium text-gray-700">Từ ngày</label>
                <input type="date" id="report-start-date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <label for="report-end-date" class="block text-sm font-medium text-gray-700">Đến ngày</label>
                <input type="date" id="report-end-date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div class="md:col-span-1">
                 <button id="filter-report-btn" class="w-full justify-center inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-search mr-2"></i> Xem Báo cáo
                </button>
            </div>
             <div class="md:col-span-1">
                 <button id="export-report-excel-btn" class="w-full justify-center inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-file-excel mr-2"></i> Xuất Excel
                </button>
            </div>
        </div>
    </div>

    <div id="report-container" class="mt-6">
        </div>
</div>