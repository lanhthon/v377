<?php
// This file provides the HTML structure for the editing page.
// The content is now identical to quote_create.php to ensure consistent functionality.
// The dynamic parts (title, button text, and data) are handled by JavaScript.
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh Sửa Báo Giá</title>
    <!-- CSS and Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Assuming a shared style.css -->
</head>

<body class="bg-gray-100 p-6">
    <!-- Hidden input to store the quote ID for updates -->
    <input type="hidden" id="quote-id-input" value="">

    <div class="flex justify-between items-center mb-4 no-print">
        <h2 class="text-2xl font-bold text-gray-800">Chỉnh Sửa Báo Giá</h2>
        <div class="flex items-center gap-4">
            <div>
                <label for="price-schema" class="font-bold text-sm">Cơ chế giá:</label>
                <select id="price-schema"
                    class="bg-white border border-gray-300 rounded-md py-1 px-2 text-sm font-bold">
                    <option value="p0">P0</option>
                    <option value="p1">P1</option>
                    <option value="p2">P2</option>
                    <option value="p3">P3</option>
                </select>
            </div>
            <button id="save-quote-btn"
                class="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700 shadow-sm">
                <i class="fas fa-save mr-2"></i>Lưu Chỉnh Sửa
            </button>
            <button id="export-excel-btn"
                class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 shadow-sm">
                <i class="fas fa-file-excel mr-2"></i>Xuất Excel
            </button>
            <button id="export-pdf-btn"
                class="px-4 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 shadow-sm">
                <i class="fas fa-file-pdf mr-2"></i>Xuất PDF
            </button>
        </div>
    </div>

    <!-- The rest of the HTML is identical to quote_create.php -->
    <div id="printable-quote-area" class="bg-white p-6 rounded-md shadow-sm">
        <!-- Header -->
        <div class="flex justify-between items-start mb-4">
            <div><img src="../assets/images/logo.png" alt="Logo" class="h-12"
                    onerror="this.onerror=null;this.src='https://placehold.co/100x50?text=Logo';"></div>
            <div class="text-right text-xs">
                <p class="font-bold">CÔNG TY CỔ PHẦN DỊCH VỤ VÀ CÔNG NGHỆ 3I</p>
                <p>Office: Số 45, ngõ 70, phố Văn Trì, P. Minh Khai, Q. Bắc Từ Liêm, Hà Nội</p>
                <p>Tel: +84 (24)37858452; Fax: +84 (24) 37858453</p>
                <p>MST: 0105 779 721</p>
            </div>
        </div>

        <h3 class="text-center text-xl font-bold uppercase mb-4 title-green">Báo giá kiêm xác nhận đặt hàng</h3>

        <!-- Customer Info Section -->
        <div class="container mx-auto p-4 text-xs font-sans">
            <div class="grid grid-cols-5 border border-gray-400 divide-x divide-y divide-gray-400 mb-6">
                <!-- Customer info rows... same as create page -->
                <div class="col-span-3 p-2 bg-gray-100">
                    <label for="customer-name-input" class="font-bold mr-2">Gửi tới:</label>
                    <input type="text" id="customer-name-input" placeholder="Gõ để tìm công ty..." autocomplete="off"
                        class="inline-block w-2/3 bg-transparent focus:outline-none">
                </div>
                <div class="col-span-1 p-2 flex items-center justify-between">
                    <label for="customer-tel-input" class="font-bold mr-1">Tel:</label>
                    <input type="text" id="customer-tel-input"
                        class="w-2/3 bg-transparent focus:outline-none text-right">
                </div>
                <div class="col-span-1 p-2 flex items-center justify-between">
                    <label for="customer-fax-input" class="font-bold mr-1">Fax:</label>
                    <input type="text" id="customer-fax-input"
                        class="w-2/3 bg-transparent focus:outline-none text-right">
                </div>
                <!-- ... other info rows ... -->
                <div class="col-span-1 bg-green-200 p-2 text-center font-bold">Số:</div>
                <div class="col-span-2 bg-green-200 p-2 text-center font-bold">Ngày:</div>

                <div class="col-span-1 p-2"> <input type="text" id="quote-number-input" placeholder="3i/G1-..."
                        class="w-full bg-transparent focus:outline-none text-blue-600">
                </div>
                <div class="col-span-2 p-2"> <input type="date" id="quote_date"
                        class="w-full bg-transparent focus:outline-none">
                </div>
            </div>
            <!-- Certificate Section... same as create page -->
        </div>

        <!-- Product Table -->
        <div class="mb-4">
            <table class="excel-table" id="main-product-table">
                <colgroup>
                    <col style="width: 4%;">
                    <col style="width: 25%;">
                    <col style="width: 8%;">
                    <col style="width: 8%;">
                    <col style="width: 8%;">
                    <col style="width: 10%;">
                    <col style="width: 11%;">
                    <col style="width: 11%;">
                    <col style="width: auto;">
                    <col style="width: 4%;" class="no-print">
                </colgroup>
                <thead>
                    <tr class="header-green">
                        <th rowspan="2">Stt.</th>
                        <th rowspan="2">Mã hàng</th>
                        <th colspan="3">Kích thước (mm)</th>
                        <th rowspan="2">Số lượng (bộ)</th>
                        <th rowspan="2">Đơn giá VNĐ</th>
                        <th rowspan="2">Thành tiền VNĐ</th>
                        <th rowspan="2">Ghi chú</th>
                        <th rowspan="2" class="no-print"></th>
                    </tr>
                    <tr class="header-green">
                        <th>ID</th>
                        <th>Độ dày</th>
                        <th>Bản rộng</th>
                    </tr>
                </thead>
                <tbody id="quote-items-bom">
                    <!-- Rows will be populated by JS -->
                </tbody>
            </table>
            <div class="mt-2 no-print">
                <button class="px-3 py-1 text-xs bg-gray-600 text-white rounded-md hover:bg-gray-700 shadow-sm"
                    id="add-empty-row-btn">
                    <i class="fas fa-plus-circle mr-2"></i>Thêm dòng trống
                </button>
            </div>
        </div>

        <!-- Totals and Notes Section -->
        <div class="flex justify-between items-start mb-4 text-xs">
            <!-- Notes... same as create page -->
            <div class="w-2/3 pr-4">
                <!-- ... -->
            </div>
            <!-- Totals Table -->
            <div class="w-1/3">
                <table class="w-full">
                    <tbody>
                        <tr>
                            <td class="pr-2 font-semibold">Cộng cộng trước thuế:</td>
                            <td id="subtotal" class="text-right font-semibold">0</td>
                        </tr>
                        <tr>
                            <td class="pr-2">VAT (10%):</td>
                            <td id="vat" class="text-right">0</td>
                        </tr>
                        <tr class="font-bold border-t border-gray-400">
                            <td class="pr-2">Thành tiền:</td>
                            <td id="total" class="text-right">0</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Images & Bank Info... same as create page -->
        <div class="mt-4 text-xs border-t-2 border-gray-400 pt-4">
            <div class="flex">
                <div class="w-7/12 pr-4">
                    <!-- ... -->
                    <div class="flex mb-4">
                        <div class="w-1/2 pr-1 text-center">
                            <input type="file" id="image1-upload-input" class="hidden" accept="image/*">
                            <img id="image1-preview" src="https://placehold.co/200x150?text=Ảnh+1" alt="Xem trước ảnh 1"
                                class="mx-auto border max-w-full h-auto cursor-pointer" title="Nhấn để đổi ảnh">
                            <input type="hidden" id="image1-path" value="">
                        </div>
                        <div class="w-1/2 pl-1 text-center">
                            <input type="file" id="image2-upload-input" class="hidden" accept="image/*">
                            <img id="image2-preview" src="https://placehold.co/200x150?text=Ảnh+2" alt="Xem trước ảnh 2"
                                class="mx-auto border max-w-full h-auto cursor-pointer" title="Nhấn để đổi ảnh">
                            <input type="hidden" id="image2-path" value="">
                        </div>
                    </div>
                    <!-- ... -->
                </div>
                <div class="w-5/12 pl-4">
                    <!-- Total summary again... -->
                </div>
            </div>
        </div>

        <!-- Signature Section -->
        <div class="flex justify-around mt-16 text-xs">
            <div class="text-center">
                <p class="font-bold">Đại diện bên bán hàng</p>
            </div>
            <div class="text-center">
                <p class="font-bold">Đại diện bên mua hàng</p>
            </div>
        </div>
    </div>
</body>

</html>