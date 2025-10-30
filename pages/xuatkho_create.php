<?php
// pages/xuatkho_create.php (Đã cập nhật CSS)
$cbh_id = $_GET['id'] ?? 0;
$pxk_id = $_GET['pxk_id'] ?? 0;
?>
<style>
    /* CSS cho các ô input có thể chỉnh sửa */
    .editable-pxk-field {
        border: 1px solid transparent;
        background-color: transparent;
        width: 100%;
        padding: 2px 4px;
        transition: background-color 0.2s, border-color 0.2s;
    }
    .editable-pxk-field:hover {
        border-color: #e2e8f0;
    }
    .editable-pxk-field:focus {
        background-color: #ffffff;
        border-color: #a0aec0;
        outline: none;
        box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.5);
        border-radius: 3px;
    }
    textarea.editable-pxk-field {
        resize: vertical;
        min-height: 40px;
    }
    .signature-input {
        border: none;
        border-bottom: 1px dotted #666;
        border-radius: 0;
        padding-top: 4px;
        background-color: #f9f9f9;
    }
    .signature-input:focus {
        outline: none;
        background-color: #eef;
        border-bottom: 1px solid #337ab7;
    }
    
    /* [ADD] CSS để ngăn xuống hàng cho cột Nội dung và Mã hàng */
    .product-table {
        table-layout: fixed; /* Giúp kiểm soát chiều rộng cột tốt hơn */
        width: 100%;
    }
    .ten-san-pham, .ma-hang {
       white-space: nowrap; /* Ngăn văn bản xuống hàng */
       overflow: hidden; /* Ẩn phần văn bản thừa */
       text-overflow: ellipsis; /* Hiển thị '...' cho văn bản bị ẩn */
       padding-left: 0.75rem;
       padding-right: 0.75rem;
    }
    
    @media print {
        @page { size: auto; margin: 1cm; }
        .no-print { display: none !important; }
        body, .p-6 { padding: 0 !important; margin: 0 !important; }
        #printable-area { box-shadow: none !important; margin: 0 !important; border: none !important; }
        .editable-pxk-field, #printable-area input {
            border: none !important;
            background-color: transparent !important;
            box-shadow: none !important;
            padding: 0 2px !important;
            color: #000 !important;
        }
        /* [ADD] Áp dụng quy tắc không xuống hàng khi in */
        .product-table {
            table-layout: fixed !important;
        }
        .ten-san-pham, .ma-hang {
           white-space: nowrap !important;
           overflow: hidden !important;
           text-overflow: clip !important; /* Dùng 'clip' để cắt chữ khi in cho an toàn */
        }
    }
</style>
<div class="p-6 bg-gray-50 min-h-full print:bg-white">
    <div class="flex justify-between items-center mb-6 no-print">
        <h1 id="page-title" class="text-3xl font-bold text-gray-800">Phiếu Xuất Kho</h1>
        <div class="flex gap-x-3">
            <button id="print-pxk-btn" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                <i class="fas fa-print mr-2"></i>In
            </button>
            <div id="export-buttons-container-pxk"></div>
            <button id="back-to-list-btn" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>Quay Lại
            </button>
            <button id="save-xuatkho-btn" data-cbh-id="<?= $cbh_id ?>"
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                <i class="fas fa-check-circle mr-2"></i>Hoàn Tất Xuất Kho
            </button>
        </div>
    </div>

    <div id="printable-area" class="bg-white p-8 shadow-lg print:shadow-none">
        <header class="grid grid-cols-2">
            <div>
                <p class="font-bold">CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG</p>
                <p class="font-bold">VẬT LIỆU XANH 3I</p>
                <p class="text-xs">Địa chỉ: Số 14 Lô D31 – BT2 Tại Khu D, KĐT Mới Hai Bên Đường Lê Trọng Tấn,</p>
                <p class="text-xs">Phường Dương Nội, TP Hà Nội, Việt Nam</p>
            </div>
            <div class="text-right">
                <p class="font-bold">Mẫu số 02 - VT</p>
                <p class="text-xs">(Ban hành theo TT số 133/2016/TT-BTC)</p>
            </div>
        </header>

        <div class="text-center my-6">
            <h2 class="text-2xl font-bold">PHIẾU XUẤT KHO</h2>
            <div class="text-sm italic">
                <input type="date" id="pxk-ngayxuat" class="editable-pxk-field text-center italic">
            </div>
            <p id="info-sophieu" class="text-sm font-semibold mt-1">Số: . . .</p>
        </div>

        <div class="text-sm space-y-1">
            <div class="grid grid-cols-[150px,1fr]">
                <span class="font-semibold">Tên công ty:</span>
                <input type="text" id="pxk-tencongty" class="editable-pxk-field" placeholder="Tên công ty...">
            </div>
            <div class="grid grid-cols-[150px,1fr]">
                <span class="font-semibold">Địa chỉ:</span>
                <textarea id="pxk-diachi" rows="2" class="editable-pxk-field" placeholder="Địa chỉ công ty..."></textarea>
            </div>
            <div class="grid grid-cols-[150px,1fr]">
                <span class="font-semibold">Người nhận hàng:</span>
                <input type="text" id="pxk-nguoinhan" class="editable-pxk-field" placeholder="Tên người nhận...">
            </div>
            <div class="grid grid-cols-[150px,1fr]">
                <span class="font-semibold">Địa chỉ giao hàng:</span>
                 <textarea id="pxk-diachigiao" rows="2" class="editable-pxk-field" placeholder="Địa chỉ giao hàng..."></textarea>
            </div>
            <div class="grid grid-cols-[150px,1fr]">
                <span class="font-semibold">Lý do xuất kho:</span>
                <input type="text" id="pxk-lydoxuat" class="editable-pxk-field" placeholder="Lý do xuất kho...">
            </div>
        </div>

        <div class="mt-4">
            <!-- [ADD] Thêm class 'product-table' vào bảng -->
            <table class="min-w-full text-sm border-collapse border border-black product-table">
                <thead class="font-bold text-center">
                    <tr class="border border-black">
                        <th class="p-2 border border-black w-12">Stt.</th>
                        <th class="p-2 border border-black">Nội dung</th>
                        <th class="p-2 border border-black w-40">Mã hàng</th>
                        <th class="p-2 border border-black w-24">Khối lượng (bộ)</th>
                        <th class="p-2 border border-black w-24">Thùng/Tải số</th>
                        <th class="p-2 border border-black w-32">Ghi chú</th>
                    </tr>
                </thead>
                <tbody id="xuatkho-items-body"></tbody>
            </table>
        </div>

        <footer class="mt-12">
            <div class="grid grid-cols-4 gap-4 text-center font-semibold text-sm">
                <div>
                    <p>Người lập phiếu</p>
                    <p class="italic text-xs">(Ký, họ tên)</p>
                    <div class="h-24"></div>
                    <input type="text" id="input-nguoilap" class="w-full text-center font-semibold signature-input editable-pxk-field" placeholder="Nhập họ tên">
                </div>
                <div>
                    <p>Thủ kho</p>
                    <p class="italic text-xs">(Ký, họ tên)</p>
                    <div class="h-24"></div>
                    <input type="text" id="input-thukho" class="w-full text-center font-semibold signature-input editable-pxk-field" placeholder="Nhập họ tên">
                </div>
                <div>
                    <p>Người giao hàng</p>
                    <p class="italic text-xs">(Ký, họ tên)</p>
                    <div class="h-24"></div>
                    <input type="text" id="input-nguoigiao" class="w-full text-center font-semibold signature-input editable-pxk-field" placeholder="Nhập họ tên">
                </div>
                <div>
                    <p>Người nhận hàng</p>
                    <p class="italic text-xs">(Ký, họ tên)</p>
                    <div class="h-24"></div>
                    <input type="text" id="input-nguoinhanhang" class="w-full text-center font-semibold signature-input editable-pxk-field" placeholder="Nhập họ tên">
                </div>
            </div>
        </footer>
    </div>
</div>
