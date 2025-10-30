<!-- File: pages/bbgh_list.php -->
<div class="bg-white p-6 rounded-lg shadow-lg">
    <div class="flex justify-between items-center mb-4">
        <h1 id="page-title" class="text-2xl font-bold text-gray-700">Danh sách Biên bản Giao hàng</h1>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Số BBGH</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Số YCSX</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Khách Hàng</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Ngày Tạo</th>
                    <th class="text-center py-3 px-4 uppercase font-semibold text-sm">Trạng Thái</th>
                    <th class="text-center py-3 px-4 uppercase font-semibold text-sm">Hành Động</th>
                </tr>
            </thead>
            <tbody id="bbgh-list-body" class="text-gray-700">
                <!-- Dữ liệu sẽ được tải vào đây bằng JavaScript -->
                <tr>
                    <td colspan="6" class="text-center p-4">Đang tải...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>


<!-- File: pages/bbgh_view.php -->
<div class="bg-white p-6 rounded-lg shadow-lg max-w-5xl mx-auto my-4 print-container text-xs">
    <!-- Header -->
    <div class="flex justify-between items-start mb-4">
        <div>
            <img src="logo.png" alt="Logo" class="h-12"
                onerror="this.onerror=null;this.src='https://placehold.co/100x50?text=Logo';">
        </div>
        <div class="text-right">
            <p class="font-bold">CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG VẬT LIỆU XANH 3I</p>
            <p>Địa chỉ: Số 14 Lô D31 – BT2 Tại Khu D, Khu Đô Thị Mới Hai Bên Đường L,</p>
            <p>Phường Dương Nội, TP Hà Nội, Việt Nam</p>
            <p>Hotline: 0988 844 863 - 0973 096 038 | MST: 0110886479</p>
        </div>
    </div>

    <!-- Title -->
    <div class="text-center my-6">
        <h1 class="text-xl font-bold uppercase">Biên bản Giao hàng</h1>
    </div>

    <!-- Info Grid -->
    <div class="grid grid-cols-12 gap-x-4 gap-y-1 mb-4 border-t border-b border-l border-r border-gray-400">
        <div class="col-span-12 p-1 border-b border-gray-400">
            <label class="font-bold">Gửi tới:</label>
            <span id="bbgh-customer-name"></span>
        </div>

        <div class="col-span-4 p-1 border-r border-gray-400">
            <label class="font-bold">Người nhận giao hàng:</label>
            <span id="bbgh-recipient-name"></span>
        </div>
        <div class="col-span-2 p-1 border-r border-gray-400">
            <label class="font-bold">Chức vụ:</label>
            <span id="bbgh-recipient-position"></span>
        </div>
        <div class="col-span-2 p-1 border-r border-gray-400">
            <label class="font-bold">Di động:</label>
            <span id="bbgh-recipient-phone"></span>
        </div>
        <div class="col-span-4 p-1">
            <label class="font-bold">Địa điểm giao hàng:</label>
            <span id="bbgh-delivery-address"></span>
        </div>

        <div class="col-span-8 p-1 border-t border-gray-400">
            <label class="font-bold">Dự án:</label>
            <span id="bbgh-project-name"></span>
        </div>
        <div class="col-span-4 p-1 border-t border-l border-gray-400">
            <label class="font-bold">Ngày:</label>
            <span id="bbgh-date"></span>
        </div>
    </div>


    <!-- Items Table -->
    <table class="w-full border-collapse text-xs mb-4">
        <thead class="bg-gray-200">
            <tr>
                <th class="border border-gray-500 p-1 font-bold" rowspan="2" style="width: 3%;">Stt.</th>
                <th class="border border-gray-500 p-1 font-bold" rowspan="2" style="width: 15%;">Mã hàng</th>
                <th class="border border-gray-500 p-1 font-bold" rowspan="2" style="width: 25%;">Tên sản phẩm</th>
                <th class="border border-gray-500 p-1 font-bold" colspan="3">Kích thước</th>
                <th class="border border-gray-500 p-1 font-bold" rowspan="2" style="width: 8%;">Số lượng (bộ)</th>
                <th class="border border-gray-500 p-1 font-bold" rowspan="2" style="width: 8%;">Số thùng</th>
                <th class="border border-gray-500 p-1 font-bold" rowspan="2">Ghi chú</th>
            </tr>
            <tr>
                <th class="border border-gray-500 p-1 font-bold" style="width: 7%;">ID</th>
                <th class="border border-gray-500 p-1 font-bold" style="width: 7%;">Độ dày</th>
                <th class="border border-gray-500 p-1 font-bold" style="width: 7%;">Bản rộng</th>
            </tr>
        </thead>
        <tbody id="bbgh-items-body">
            <!-- Dữ liệu sản phẩm sẽ được chèn vào đây bởi JavaScript -->
        </tbody>
    </table>

    <!-- Signatures -->
    <div class="flex justify-around mt-8 pt-4">
        <div class="text-center">
            <p class="font-bold">Đại diện bên bán hàng</p>
            <p class="mt-16">(Ký, họ tên)</p>
        </div>
        <div class="text-center">
            <p class="font-bold">Người nhận hàng</p>
            <p class="mt-16">(Ký, họ tên)</p>
        </div>
    </div>
</div>

<!-- Print Button -->
<div class="text-center mt-4 mb-8 no-print">
    <button id="print-bbgh-btn"
        class="bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700 transition duration-300">
        <i class="fas fa-print mr-2"></i>In Biên Bản
    </button>
</div>
<style>
@media print {
    body {
        margin: 0;
        background-color: white;
    }

    .no-print {
        display: none !important;
    }

    .print-container {
        box-shadow: none !important;
        margin: 0 !important;
        max-width: 100% !important;
        border-radius: 0 !important;
        font-size: 10px;
        /* Make font smaller for printing */
    }

    table {
        page-break-inside: auto;
    }

    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    thead {
        display: table-header-group;
    }

    tbody {
        display: table-row-group;
    }

    .group-header-row {
        page-break-after: avoid;
    }
}
</style>