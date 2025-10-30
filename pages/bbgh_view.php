<?php
// pages/bbgh_view.php
$bbgh_id = $_GET['id'] ?? 0;
?>
<style>
    #printable-area {
        font-size: 9.5pt;
    }
    .party-block {
        background-color: #f3f4f6;
        height: 100%;
        vertical-align: top;
        padding: 8px;
        font-size: 9pt;
    }
    #printable-area h1.text-2xl { font-size: 16pt; }
    #info-sobbgh { font-size: 11pt; }
    #ngay-giao-hang { font-size: 9pt; }
    .party-block .space-y-2 > :not([hidden]) ~ :not([hidden]) {
        --tw-space-y-reverse: 0;
        margin-top: calc(0.25rem * (1 - var(--tw-space-y-reverse)));
        margin-bottom: calc(0.25rem * var(--tw-space-y-reverse));
    }
    .party-block table { line-height: 1.4; }
    .items-table { font-size: 9pt; }
    .items-table th, .items-table td { padding: 4px 6px; }

    /* --- CSS CHO CÁC Ô INPUT CÓ THỂ CHỈNH SỬA --- */
    .editable-field {
        border: 1px solid transparent;
        background-color: transparent;
        width: 100%;
        padding: 2px 4px;
        transition: background-color 0.2s, border-color 0.2s;
        font-size: inherit;
        font-weight: inherit;
        color: inherit;
        line-height: inherit;
    }
    .editable-field:hover {
        border-color: #e2e8f0; /* gray-200 */
    }
    .editable-field:focus {
        background-color: #ffffff;
        border-color: #a0aec0; /* gray-400 */
        outline: none;
        box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.5);
        border-radius: 3px;
    }
    textarea.editable-field {
        resize: vertical;
        min-height: 40px;
    }

    /* CSS cho date input */
    .editable-date {
        border: 1px solid transparent;
        background-color: transparent;
        padding: 2px 4px;
        transition: background-color 0.2s, border-color 0.2s;
        font-size: inherit;
        color: inherit;
        width: auto;
        min-width: 120px;
    }
    .editable-date:hover {
        border-color: #e2e8f0;
    }
    .editable-date:focus {
        background-color: #ffffff;
        border-color: #a0aec0;
        outline: none;
        box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.5);
        border-radius: 3px;
    }

    #bbgh-items-body td:nth-child(2),
    #bbgh-items-body td:nth-child(3) {
        white-space: nowrap;
    }
    footer .text-xs { font-size: 8pt; }

    /* CSS cho labels in đậm */
    .label-bold {
        font-weight: bold;
    }

    @media print {
        @page { size: auto; margin: 0.5cm; }
        .no-print { display: none !important; }
        body, .p-6 { padding: 0 !important; margin: 0 !important; }
        #printable-area { box-shadow: none !important; margin: 0 !important; max-width: 100% !important; border: none !important; font-size: 9.5pt !important; }
        .party-block { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; background-color: #f3f4f6 !important; padding: 8px !important; }
        thead tr[style*="#92D050"] { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; background-color: #92D050 !important; }
        .editable-field, .editable-date, #bbgh-items-body input { border: none !important; background-color: transparent !important; padding: 2px 4px !important; color: #000 !important; }
    }
</style>
<div class="p-6 bg-gray-50 min-h-full print:bg-white">
    <div class="flex justify-between items-center mb-6 no-print">
        <h1 id="page-title" class="text-3xl font-bold text-gray-800">Biên Bản Giao Hàng</h1>
        <div class="flex gap-x-3">
            <button id="print-btn" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                <i class="fas fa-print mr-2"></i>In
            </button>
            <button id="export-excel-btn-bbgh" data-bbgh-id="<?php echo $bbgh_id; ?>" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                <i class="fas fa-file-excel mr-2"></i>Xuất Excel
            </button>
            <button id="export-pdf-btn-bbgh" data-bbgh-id="<?php echo $bbgh_id; ?>" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                <i class="fas fa-file-pdf mr-2"></i>Xuất PDF
            </button>
            <button id="save-bbgh-btn" data-bbgh-id="<?php echo $bbgh_id; ?>" class="bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700">
                <i class="fas fa-save mr-2"></i>Lưu thay đổi
            </button>
            <button id="back-to-issued-list-btn" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>Quay Lại
            </button>
        </div>
    </div>

    <div id="printable-area" class="bg-white p-8 shadow-lg print:shadow-none">
        <table class="w-full" style="border-bottom: 1px solid #ccc; padding-bottom: 8px; margin-bottom: 20px;">
            <tr>
                <td style="width: 47%; vertical-align: middle;">
                    <h1 class="text-2xl font-bold uppercase mb-2">BIÊN BẢN GIAO HÀNG</h1>
                    <p id="info-sobbgh" class="text-lg font-semibold mb-1">Số: ...</p>
                    <div class="text-sm italic text-gray-600 flex items-center">
                        <span class="mr-2">Ngày</span>
                        <input type="date" id="bbgh-ngay-giao" class="editable-date" data-display-format="dd/mm/yyyy">
                    </div>
                </td>
                <td style="width: 6%;"></td>
                <td style="width: 47%; text-align: center; vertical-align: middle;">
                    <img src="logo.png" alt="3i-FIX Logo" class="inline-block" style="max-height: 120px;">
                </td>
            </tr>
        </table>

        <table class="w-full">
             <tr>
                <td style="width: 49%;" class="party-block">
                    <h3 class="font-bold text-left mb-3 uppercase">BÊN NHẬN HÀNG (BÊN B):</h3>
                    <div class="space-y-2">
                        <input type="text" id="bbgh-tencongty-khach" class="editable-field font-semibold" placeholder="Tên công ty...">
                        <table class="w-full">
                            <tr>
                                <td style="width: 60px; vertical-align: top;" class="label-bold">Địa chỉ:</td>
                                <td><textarea id="bbgh-diachi-khach" class="editable-field" placeholder="Địa chỉ công ty..."></textarea></td>
                            </tr>
                        </table>
                         <table class="w-full">
                            <tr>
                                <td style="width: 60px;" class="label-bold">Đại diện:</td>
                                <td><input type="text" id="bbgh-nguoi-nhan" class="editable-field font-semibold" placeholder="Người nhận..."></td>
                                <td style="width: 70px;" class="label-bold">Điện Thoại:</td>
                                <td><input type="text" id="bbgh-sdt-nguoi-nhan" class="editable-field font-semibold" placeholder="SĐT người nhận..."></td>
                            </tr>
                        </table>
                         <table class="w-full">
                            <tr>
                                <td style="width: 70px; vertical-align: top;" class="font-medium label-bold">Tên dự án:</td>
                                <td><input type="text" id="bbgh-duan" class="editable-field font-medium" placeholder="Tên dự án..."></td>
                            </tr>
                            <tr>
                                <td class="font-medium label-bold" style="vertical-align: top;">Địa điểm giao hàng:</td>
                                <td><textarea id="bbgh-diachi-giaohang" class="editable-field" placeholder="Địa điểm giao hàng..."></textarea></td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style="width: 2%;"></td>
                <td style="width: 49%;" class="party-block">
                    <h3 class="font-bold text-left mb-3 uppercase">BÊN GIAO HÀNG (BÊN A):</h3>
                     <div class="space-y-2">
                        <p class="font-semibold">CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG VẬT LIỆU XANH 3I</p>
                        <table class="w-full">
                            <tr>
                                <td style="width: 60px;" class="label-bold">Địa chỉ:</td>
                                <td>Số 14 Lô D31 – BT2 Tại Khu D, Khu Đô Thị Mới Hai Bên Đường Lê Trọng Tấn, Phường Dương Nội, TP Hà Nội, Việt Nam</td>
                            </tr>
                        </table>
                         <table class="w-full">
                            <tr>
                                <td style="width: 60px;" class="label-bold">Đại diện:</td>
                                <td><input type="text" id="bbgh-nguoi-lap-phieu" class="editable-field font-semibold" placeholder="Người giao..."></td>
                                <td style="width: 70px;" class="label-bold">Điện thoại:</td>
                                <td><input type="text" id="bbgh-sdt-nguoi-lap" class="editable-field font-semibold" placeholder="SĐT người giao..."></td>
                            </tr>
                        </table>
                         <table class="w-full">
                            <tr>
                                <td style="width: 80px;" class="font-medium label-bold">Sản phẩm:</td>
                                <td><input type="text" id="bbgh-sanpham" class="editable-field font-medium" value="Gối đỡ PU Foam và Cùm Ula 3i-Fix" placeholder="Sản phẩm..."></td>
                            </tr>
                            <tr>
                                <td class="font-medium label-bold">Số YCSX gốc:</td>
                                <td id="bbgh-soycsx">...</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
        
        <div class="mb-4 mt-4">
            <p>Bên A tiến hành giao cho Bên B các loại hàng hóa có tên và số lượng chi tiết như sau:</p>
        </div>

        <div class="mb-6">
            <table class="w-full border-collapse border-2 border-black items-table">
                <thead>
                    <tr style="background-color: #92D050;">
                        <th class="border border-black font-bold text-center text-black">Stt.</th>
                        <th class="border border-black font-bold text-center text-black">Mã hàng</th>
                        <th class="border border-black font-bold text-center text-black">Tên sản phẩm</th>
                        <th class="border border-black font-bold text-center text-black">ĐVT</th>
                        <th class="border border-black font-bold text-center text-black">Số lượng</th>
                        <th class="border border-black font-bold text-center text-black">Số thùng/tải</th>
                        <th class="border border-black font-bold text-center text-black">Ghi chú</th>
                    </tr>
                </thead>
                <tbody id="bbgh-items-body"></tbody>
            </table>
        </div>

        <div class="mb-8 space-y-2">
            <p>Hai bên cùng xác nhận hàng hóa được giao đúng số lượng và chất lượng. Biên bản được lập thành 02 bản, mỗi bên giữ 01 bản và có giá trị pháp lý như nhau.</p>
        </div>

        <footer class="mt-12">
            <table class="w-full">
                <tr class="text-center">
                    <td style="width: 50%;">
                        <p class="font-bold uppercase mb-1">ĐẠI DIỆN BÊN GIAO</p>
                        <p class="text-xs italic mb-16">(Ký, ghi rõ họ tên)</p>
                        <input type="text" id="bbgh-footer-nguoigiao" class="editable-field font-semibold text-center" placeholder="Tên đại diện bên giao...">
                    </td>
                    <td style="width: 50%;">
                        <p class="font-bold uppercase mb-1">ĐẠI DIỆN BÊN NHẬN</p>
                        <p class="text-xs italic mb-16">(Ký, họ tên)</p>
                        <input type="text" id="bbgh-footer-nguoinhan" class="editable-field font-semibold text-center" placeholder="Tên đại diện bên nhận...">
                    </td>
                </tr>
            </table>
        </footer>
    </div>
</div>