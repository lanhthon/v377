<div class="container mx-auto p-6 bg-white rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Quản lý Nhãn Đa Ngôn Ngữ</h1>
    </div>

    <div class="overflow-x-auto shadow-md rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">STT</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Key</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tiếng Việt (vi)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tiếng Trung (zh)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tiếng Anh (en)</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Sửa</th>
                </tr>
            </thead>
            <tbody id="labels-table-body" class="bg-white divide-y divide-gray-200">
                </tbody>
        </table>
    </div>
</div>

<div id="label-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 id="modal-title" class="text-lg leading-6 font-medium text-gray-900 mb-4">Chỉnh sửa Nhãn</h3>
        <form id="label-form">
            <input type="hidden" id="label-id">
            <div class="space-y-4">
                <div>
                    <label for="label-key" class="block text-sm font-medium text-gray-700">Label Key</label>
                    <input type="text" id="label-key" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed" readonly>
                </div>
                <div>
                    <label for="label-vi" class="block text-sm font-medium text-gray-700">Tiếng Việt (vi)</label>
                    <textarea id="label-vi" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                </div>
                <div>
                    <label for="label-zh" class="block text-sm font-medium text-gray-700">Tiếng Trung (zh)</label>
                    <textarea id="label-zh" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                </div>
                <div>
                    <label for="label-en" class="block text-sm font-medium text-gray-700">Tiếng Anh (en)</label>
                    <textarea id="label-en" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                </div>
            </div>
            <div class="items-center px-4 py-3 mt-4 text-right space-x-4">
                <button id="modal-cancel-btn" type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Hủy</button>
                <button id="modal-save-btn" type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Lưu</button>
            </div>
        </form>
    </div>
</div>