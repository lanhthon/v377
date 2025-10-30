<?php
// File: pages/ycsx_view.php
// Giao diện chi tiết Yêu cầu sản xuất
?>
<div class="bg-white p-4" id="ycsx-printable-area">
    <div class="flex justify-between items-start mb-4 no-print">
        <h1 class="text-2xl font-bold text-gray-800">Chi tiết Yêu Cầu Sản Xuất</h1>
        <button id="print-ycsx-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 shadow-sm">
            <i class="fas fa-print mr-2"></i>In Phiếu
        </button>
    </div>

    <div class="flex justify-between items-start mb-4">
        <div>
            <img src="logo.png" alt="Logo" class="h-16"
                onerror="this.onerror=null;this.src='https://placehold.co/150x60/cccccc/333333?text=Logo';">
        </div>
        <div class="text-right text-xs">
            <p class="font-bold">CÔNG TY CỔ PHẦN DỊCH VỤ VÀ CÔNG NGHỆ 3I</p>
            <p>Office: Số 45, ngõ 70, phố Văn Trì, P. Minh Khai, Q. Bắc Từ Liêm, Hà Nội</p>
            <p>Tel: +84 (24)37858452; Fax: +84 (24) 37858453</p>
            <p>MST: 0105 779 721</p>
        </div>
    </div>

    <h2 class="text-center text-2xl font-bold uppercase text-red-600 mb-4">Yêu Cầu Sản Xuất</h2>

    <div class="grid grid-cols-2 border border-black mb-4">
        <div class="col-span-1 border-r border-black p-2">
            <div class="grid grid-cols-3">
                <div class="col-span-1 font-bold">Dự án:</div>
                <div class="col-span-2" id="ycsx-project-name"></div>
                <div class="col-span-1 font-bold">Hạng mục:</div>
                <div class="col-span-2" id="ycsx-category"></div>
            </div>
        </div>
        <div class="col-span-1 p-2">
            <div class="grid grid-cols-3">
                <div class="col-span-1 font-bold">Số:</div>
                <div class="col-span-2" id="ycsx-number"></div>
                <div class="col-span-1 font-bold">Ngày:</div>
                <div class="col-span-2" id="ycsx-date"></div>
            </div>
        </div>
    </div>

    <div class="border border-black p-2 mb-4 text-sm">
        <div class="grid grid-cols-12 gap-x-4">
            <strong class="col-span-2">Khách hàng:</strong>
            <p class="col-span-10" id="ycsx-customer-name"></p>

            <strong class="col-span-2">Người nhận:</strong>
            <p class="col-span-4" id="ycsx-recipient-name"></p>
            <strong class="col-span-1">Email:</strong>
            <p class="col-span-2" id="ycsx-email"></p>
            <strong class="col-span-1">HP:</strong>
            <p class="col-span-2" id="ycsx-mobile"></p>

            <strong class="col-span-2">Địa chỉ giao hàng:</strong>
            <p class="col-span-10" id="ycsx-delivery-address"></p>

            <strong class="col-span-2">Thời gian giao hàng:</strong>
            <p class="col-span-10" id="ycsx-delivery-time"></p>
        </div>
    </div>

    <table class="w-full border-collapse border border-black text-sm">
        <thead>
            <tr class="bg-gray-200">
                <th class="border border-black p-1">STT</th>
                <th class="border border-black p-1">Mã hàng</th>
                <th class="border border-black p-1">Tên hàng</th>
                <th class="border border-black p-1">ID (mm)</th>
                <th class="border border-black p-1">Độ dày (mm)</th>
                <th class="border border-black p-1">Bản rộng (mm)</th>
                <th class="border border-black p-1">Số lượng (Bộ)</th>
                <th class="border border-black p-1">Ghi chú</th>
            </tr>
        </thead>
        <tbody id="ycsx-items-body">
        </tbody>
    </table>

    <div class="flex justify-around mt-16 mb-4">
        <div class="text-center">
            <p class="font-bold">NGƯỜI LẬP PHIẾU</p>
        </div>
        <div class="text-center">
            <p class="font-bold">QUẢN LÝ SẢN XUẤT</p>
        </div>
        <div class="text-center">
            <p class="font-bold">GIÁM ĐỐC</p>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print {
        display: none !important;
    }

    body,
    #ycsx-printable-area {
        margin: 0;
        padding: 0;
    }
}
</style>