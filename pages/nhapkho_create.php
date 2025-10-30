<?php
// File: pages/nhapkho_create.php
session_start();
$pnk_id = $_GET['pnk_id'] ?? 0; // PhieuNhapKhoID for existing slip
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu Nhập Kho</title>
    <style>
    /* Các style này đảm bảo suggestion box hoạt động tốt */
    .suggestion-box {
        position: absolute;
        z-index: 1000;
        background: white;
        border: 1px solid #ccc;
        max-height: 250px;
        overflow-y: auto;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .suggestion-box ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .suggestion-box li {
        padding: 8px 12px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .suggestion-box li:hover,
    .suggestion-box li.highlighted {
        background-color: #f0f4f8;
    }

    .f-key-hint {
        font-size: 0.75rem;
        font-weight: bold;
        color: #fff;
        background-color: #2563eb;
        border-radius: 0.375rem;
        padding: 0.125rem 0.375rem;
        margin-left: 0.75rem;
    }
    </style>
</head>

<body>
    <div class="p-6 bg-gray-50 min-h-full print:bg-white">
        <div class="flex justify-between items-center mb-6 no-print">
            <h1 id="page-title" class="text-3xl font-bold text-gray-800">Phiếu Nhập Kho</h1>
            <div class="flex gap-x-3">
                <button id="back-to-list-btn" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i>Quay Lại
                </button>
                <button id="save-nhapkho-btn" data-pnk-id="<?= $pnk_id ?>"
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-check-circle mr-2"></i>Hoàn Tất Nhập Kho
                </button>
                <button onclick="window.print()"
                    class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    <i class="fas fa-print mr-2"></i>In Phiếu
                </button>
            </div>
        </div>

        <div id="printable-area" class="bg-white p-8 shadow-lg print:shadow-none">
            <header class="grid grid-cols-2">
                <div class="col-span-1">
                    <p class="font-bold">CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG</p>
                    <p class="font-bold">VẬT LIỆU XANH 3I</p>
                </div>
                <div class="col-span-1 text-right">
                    <p class="font-bold">Mẫu số 01 - VT</p>
                </div>
            </header>

            <div class="text-center my-6">
                <h2 class="text-2xl font-bold">PHIẾU NHẬP KHO</h2>
                <p id="info-ngaynhap" class="text-sm italic">Ngày ... tháng ... năm ...</p>
                <p id="info-sophieu" class="text-sm font-semibold">Số: . . . . . . . . .</p>
            </div>

            <div class="text-sm space-y-1 mb-4">
                <div class="grid grid-cols-[150px,1fr] relative">
                    <span class="font-semibold">Nhà cung cấp:</span>
                    <input type="text" id="input-nhacungcap" class="flex-grow border-b border-gray-300 px-1 py-0.5"
                        placeholder="Gõ để tìm nhà cung cấp..." autocomplete="off" data-ncc-id="">
                </div>
                <!-- Các trường thông tin khác -->
                <div class="grid grid-cols-[150px,1fr]"><span class="font-semibold">Người giao hàng:</span><input
                        type="text" id="input-nguoigiaohang" class="flex-grow border-b border-gray-300 px-1 py-0.5">
                </div>
                <div class="grid grid-cols-[150px,1fr]"><span class="font-semibold">Số hóa đơn:</span><input type="text"
                        id="input-sohoadon" class="flex-grow border-b border-gray-300 px-1 py-0.5"></div>
                <div class="grid grid-cols-[150px,1fr]"><span class="font-semibold">Lý do nhập kho:</span><input
                        type="text" id="input-lydonhap" class="flex-grow border-b border-gray-300 px-1 py-0.5"></div>
                <div class="grid grid-cols-[150px,1fr]"><span class="font-semibold">Ghi chú chung:</span><textarea
                        id="input-ghichu" class="flex-grow border-b border-gray-300 px-1 py-0.5"></textarea></div>
            </div>

            <table class="min-w-full text-sm border-collapse border border-black">
                <thead class="font-bold text-center">
                    <tr class="border border-black">
                        <th class="p-2 border border-black" rowspan="2">Stt.</th>
                        <th class="p-2 border border-black" rowspan="2">Mã hàng</th>
                        <th class="p-2 border border-black" rowspan="2">Tên sản phẩm</th>
                        <th class="p-2 border border-black" colspan="3">Kích thước</th>
                        <th class="p-2 border border-black" rowspan="2">Số lượng</th>
                        <th class="p-2 border border-black" rowspan="2">Đơn giá</th>
                        <th class="p-2 border border-black" rowspan="2">Thành tiền</th>
                        <th class="p-2 border border-black" rowspan="2">Ghi chú</th>
                        <th class="p-2 border border-black no-print" rowspan="2"></th>
                    </tr>
                    <tr class="border border-black">
                        <th class="p-2 border border-black">ID</th>
                        <th class="p-2 border border-black">Độ dày</th>
                        <th class="p-2 border border-black">Bản rộng</th>
                    </tr>
                </thead>
                <tbody id="nhapkho-items-body"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="8" class="p-2 text-right font-bold border-t border-black">Tổng tiền:</td>
                        <td id="tong-tien-pnk" class="p-2 text-right font-bold border-t border-black">0</td>
                        <td colspan="2" class="p-2 border-t border-black no-print"></td>
                    </tr>
                </tfoot>
            </table>
            <div class="mt-2 no-print">
                <button id="add-empty-row-btn"
                    class="px-3 py-1 text-xs bg-gray-600 text-white rounded-md hover:bg-gray-700 shadow-sm">
                    <i class="fas fa-plus-circle mr-2"></i>Thêm dòng
                </button>
            </div>

            <footer class="mt-12">
                <div class="grid grid-cols-4 gap-4 text-center font-semibold text-sm">
                    <div>
                        <p>Người lập phiếu</p>
                        <p class="italic text-xs">(Ký, họ tên)</p>
                        <div class="h-24"></div>
                        <p id="footer-nguoilap"></p>
                    </div>
                    <div>
                        <p>Kế toán trưởng</p>
                        <p class="italic text-xs">(Ký, họ tên)</p>
                        <div class="h-24"></div>
                    </div>
                    <div>
                        <p>Thủ kho</p>
                        <p class="italic text-xs">(Ký, họ tên)</p>
                        <div class="h-24"></div>
                    </div>
                    <div>
                        <p>Người giao hàng</p>
                        <p class="italic text-xs">(Ký, họ tên)</p>
                        <div class="h-24"></div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <div id="product-suggestion-box" class="suggestion-box hidden"></div>
    <div id="supplier-suggestion-box" class="suggestion-box hidden"></div>
</body>

</html>