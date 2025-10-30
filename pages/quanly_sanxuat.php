<style>
    .tab-btn {
        transition: all 0.2s ease-in-out;
        border-bottom: 3px solid transparent;
        padding-bottom: 8px;
    }

    .tab-btn.active {
        border-color: #3b82f6;
        color: #1d4ed8;
        font-weight: 600;
    }

    .order-accordion.open .accordion-icon {
        transform: rotate(90deg);
    }

    .accordion-header {
        transition: background-color 0.2s ease;
    }
</style>

<div class="p-4 sm:p-6 bg-gray-100 min-h-screen">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-6 no-print">
        <h1 id="page-title" class="text-2xl font-bold text-gray-800">Quản lý Sản xuất</h1>
        <div id="action-buttons-container" class="flex items-center gap-x-3">
        </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-sm mb-4 no-print">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="start-date-filter" class="block text-sm font-medium text-gray-700">Từ ngày</label>
                <input type="date" id="start-date-filter" name="start-date-filter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <label for="end-date-filter" class="block text-sm font-medium text-gray-700">Đến ngày</label>
                <input type="date" id="end-date-filter" name="end-date-filter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div class="flex space-x-2 col-span-1 sm:col-span-2 md:col-span-2">
                <button id="apply-filter-btn" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-filter mr-2"></i> Lọc
                </button>
                <button id="clear-filter-btn" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-times mr-2"></i> Xóa
                </button>
            </div>
        </div>
    </div>


    <div class="border-b border-gray-200 mb-6 no-print bg-white rounded-t-lg px-4 pt-2 shadow-sm">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <button class="tab-btn py-3 px-1 text-base text-gray-600 hover:text-gray-800 active" data-tab="in-progress">
                <i class="fas fa-tasks mr-2"></i>Đang xử lý
                <span class="tab-count-bubble ml-2 bg-gray-200 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full" style="display: none;"></span>
            </button>
            <button class="tab-btn py-3 px-1 text-base text-gray-500 hover:text-gray-700" data-tab="overdue">
                <i class="fas fa-exclamation-triangle mr-2"></i>Quá hạn
                <span class="tab-count-bubble ml-2 bg-red-200 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full" style="display: none;"></span>
            </button>
            <button class="tab-btn py-3 px-1 text-base text-gray-500 hover:text-gray-700" data-tab="completed">
                <i class="fas fa-check-circle mr-2"></i>Đã hoàn thành
                <span class="tab-count-bubble ml-2 bg-gray-200 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full" style="display: none;"></span>
            </button>
        </nav>
    </div>

    <div id="production-content-wrapper">
        <div id="tab-content-in-progress" class="tab-pane">
            <div id="orders-container-inprogress" class="space-y-4"></div>
            <div id="pagination-container-inprogress" class="mt-6 flex justify-center"></div>
        </div>
        
        <div id="tab-content-overdue" class="tab-pane hidden">
            <div id="orders-container-overdue" class="space-y-4"></div>
            <div id="pagination-container-overdue" class="mt-6 flex justify-center"></div>
        </div>

        <div id="tab-content-completed" class="tab-pane hidden">
            <div id="orders-container-completed" class="space-y-4"></div>
            <div id="pagination-container-completed" class="mt-6 flex justify-center"></div>
        </div>
    </div>
</div>

