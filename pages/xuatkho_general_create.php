<?php
// pages/xuatkho_general_create.php
session_start(); // Start session to get user_id for NguoiTaoID
$pxk_id = $_GET['pxk_id'] ?? 0; // PhieuXuatKhoID if editing an existing general PXK
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Phiếu Xuất Kho Ngoài Đơn Hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
    /* Styles for suggestions box */
    #product-suggestion-box {
        position: absolute;
        z-index: 20;
        background-color: white;
        border: 1px solid #e2e8f0;
        /* gray-200 */
        border-radius: 0.375rem;
        /* rounded-md */
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        /* shadow-lg */
        overflow-y: auto;
        max-height: 240px;
        /* max-h-60 */
        font-size: 0.875rem;
        /* text-sm */
        display: none;
        /* hidden by default */
    }

    #product-suggestion-box ul {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    #product-suggestion-box li {
        padding: 0.5rem 0.75rem;
        /* p-2 */
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    #product-suggestion-box li:hover {
        background-color: #f0f4f8;
        /* blue-100 */
    }

    #product-suggestion-box li .f-key-hint {
        font-size: 0.75rem;
        /* text-xs */
        font-weight: bold;
        color: #fff;
        background-color: #2563eb;
        /* blue-600 */
        border-radius: 0.375rem;
        /* rounded-md */
        padding: 0.125rem 0.375rem;
        /* px-1.5 py-0.5 */
        margin-left: 0.75rem;
        /* ml-3 */
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        #printable-area {
            width: 210mm;
            /* A4 width */
            min-height: 297mm;
            /* A4 height */
            margin: 0 auto;
            padding: 10mm;
        }
    }
    </style>
</head>

<body class="bg-gray-100">
    <div class="p-6 bg-gray-50 min-h-full print:bg-white">
        <div class="flex justify-between items-center mb-6 no-print">
            <h1 id="page-title" class="text-3xl font-bold text-gray-800">Tạo Phiếu Xuất Kho Ngoài đơn hàng</h1>
            <div class="flex gap-x-3">

                <button id="save-xuatkho-btn" data-pxk-id="<?= $pxk_id ?>"
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-check-circle mr-2"></i>Hoàn Tất Xuất Kho
                </button>

            </div>
        </div>

        <div id="printable-area" class="bg-white p-8 shadow-lg print:shadow-none">
            <header class="grid grid-cols-2">
                <div class="col-span-1">
                    <p class="font-bold">CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG</p>
                    <p class="font-bold">VẬT LIỆU XANH 3I</p>
                    <p class="text-xs">Địa chỉ: Số 14 Lô D31 - BT12 tại khu D, KĐT Mới Hai Bên Đường Lê Trọng Tấn,
                        Phường
                        Dương Nội, Quận Hà Đông, TP. Hà Nội</p>
                </div>
                <div class="col-span-1 text-right">
                    <p class="font-bold">Mẫu số 02 - VT</p>
                    <p class="text-xs">(Ban hành theo Thông tư số 133/2016/</p>
                    <p class="text-xs">TT-BTC ngày 26/8/2016 của Bộ Tài chính)</p>
                </div>
            </header>

            <div class="text-center my-6">
                <h2 class="text-2xl font-bold">PHIẾU XUẤT KHO</h2>
                <p id="info-ngayxuat" class="text-sm italic">Ngày ... tháng ... năm ...</p>
                <p id="info-sophieu" class="text-sm font-semibold">Số: . . . . . . . . .</p>
            </div>

            <div class="text-sm space-y-1">
                <div class="grid grid-cols-[120px,1fr]">
                    <span class="font-semibold">Người nhận hàng:</span>
                    <input type="text" id="input-nguoinhan" class="flex-grow border-b border-gray-300 px-1 py-0.5"
                        placeholder="Nhập tên người/đơn vị nhận hàng">
                </div>
                <div class="grid grid-cols-[120px,1fr]">
                    <span class="font-semibold">Địa chỉ:</span>
                    <input type="text" id="input-diachi" class="flex-grow border-b border-gray-300 px-1 py-0.5"
                        placeholder="Nhập địa chỉ nhận hàng">
                </div>
                <div class="grid grid-cols-[120px,1fr]">
                    <span class="font-semibold">Lý do xuất kho:</span>
                    <input type="text" id="input-lydoxuat" class="flex-grow border-b border-gray-300 px-1 py-0.5"
                        placeholder="Nhập lý do xuất kho">
                </div>
            </div>

            <div class="mt-4">
                <table class="min-w-full text-sm border-collapse border border-black">
                    <thead class="font-bold text-center">
                        <tr class="border border-black">
                            <th class="p-2 border border-black" rowspan="2">Stt.</th>
                            <th class="p-2 border border-black" rowspan="2">Mã hàng</th>
                            <th class="p-2 border border-black" rowspan="2">Tên sản phẩm</th>
                            <th class="p-2 border border-black" colspan="3">Kích thước</th>
                            <th class="p-2 border border-black" rowspan="2">Số lượng (bộ)</th>
                            <th class="p-2 border border-black" rowspan="2">Số lượng thực xuất</th>
                            <th class="p-2 border border-black" rowspan="2">Tải số</th>
                            <th class="p-2 border border-black" rowspan="2">Ghi chú</th>
                            <th class="p-2 border border-black no-print" rowspan="2"></th>
                        </tr>
                        <tr class="border border-black">
                            <th class="p-2 border border-black">ID</th>
                            <th class="p-2 border border-black">Độ dày</th>
                            <th class="p-2 border border-black">Bản rộng</th>
                        </tr>
                    </thead>
                    <tbody id="xuatkho-items-body">
                        <tr class="product-row border border-black">
                            <td class="p-2 border border-black text-center stt">1</td>
                            <td class="p-2 border border-black">
                                <input type="text" class="w-full border p-1 rounded text-center product-code"
                                    placeholder="Gõ tìm sản phẩm..." data-field="maHang">
                                <input type="hidden" data-field="sanPhamID">
                            </td>
                            <td class="p-2 border border-black">
                                <input type="text" class="w-full border-none p-0 m-0 bg-transparent" readonly
                                    data-field="tenSanPham">
                            </td>
                            <td class="p-2 border border-black">
                                <input type="text" class="w-full border-none p-0 m-0 bg-transparent text-center"
                                    readonly data-field="ID_ThongSo">
                            </td>
                            <td class="p-2 border border-black">
                                <input type="text" class="w-full border-none p-0 m-0 bg-transparent text-center"
                                    readonly data-field="DoDay">
                            </td>
                            <td class="p-2 border border-black">
                                <input type="text" class="w-full border-none p-0 m-0 bg-transparent text-center"
                                    readonly data-field="BanRong">
                            </td>
                            <td class="p-2 border border-black">
                                <input type="number" class="w-full border p-1 rounded text-center" value="0"
                                    data-field="soLuongYeuCau">
                            </td>
                            <td class="p-2 border border-black">
                                <input type="number" class="w-full border p-1 rounded text-center" value="0"
                                    data-field="soLuongThucXuat">
                            </td>
                            <td class="p-2 border border-black">
                                <input type="text" class="w-full border p-1 rounded" data-field="taiSo">
                            </td>
                            <td class="p-2 border border-black">
                                <input type="text" class="w-full border p-1 rounded" data-field="ghiChu">
                            </td>
                            <td class="p-2 border border-black no-print text-center">
                                <button class="delete-row-btn text-red-500 hover:text-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-2 no-print">
                    <button id="add-empty-row-btn"
                        class="px-3 py-1 text-xs bg-gray-600 text-white rounded-md hover:bg-gray-700 shadow-sm">
                        <i class="fas fa-plus-circle mr-2"></i>Thêm dòng sản phẩm
                    </button>
                </div>
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
                        <p>Thủ kho</p>
                        <p class="italic text-xs">(Ký, họ tên)</p>
                    </div>
                    <div>
                        <p>Người giao hàng</p>
                        <p class="italic text-xs">(Ký, họ tên)</p>
                    </div>
                    <div>
                        <p>Người nhận hàng</p>
                        <p class="italic text-xs">(Ký, họ tên)</p>
                    </div>
                </div>

            </footer>
        </div>
    </div>
    <div id="product-suggestion-box" class="hidden"></div>
</body>

</html>