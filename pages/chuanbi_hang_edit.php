<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3i-Fix - Phiếu Chuẩn Bị Hàng</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css">
    <style>
    body {
        font-family: sans-serif;
        background-color: #f0f2f5;
    }
    .table-subheader {
        background-color: #e0f2fe;
        color: #0c4a6e;
        font-weight: bold;
    }
    th, td {
        padding: 6px 4px;
        border: 1px solid #d1d5db;
        text-align: left;
        font-size: 11px;
        vertical-align: middle;
    }
    th {
        font-weight: bold;
        background-color: #f3f4f6;
    }
    .table-input {
        width: 100%;
        padding: 4px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        font-size: 11px;
    }
    .table-input:focus {
        outline: 1px solid #3b82f6;
        background-color: #eff6ff;
    }
    .info-table td {
        border: 1px solid #d1d5db;
        padding: 2px 5px;
        font-size: 11px;
        vertical-align: middle;
    }
    .info-table .label {
        font-weight: bold;
        background-color: #f9fafb;
    }
    .info-table input {
        width: 100%;
        padding: 2px 4px;
        border: 1px solid transparent;
    }
    .info-table input:focus {
        outline: 1px solid #3b82f6;
        background-color: #eff6ff;
    }
    input[type="date"]::-webkit-calendar-picker-indicator {
        display: none;
        -webkit-appearance: none;
    }
    input[name="canSanXuatCay"] {
        background-color: #fef3c7; 
        border: 2px solid #f59e0b; 
        font-weight: bold;
        text-align: center;
        border-radius: 4px;
        transition: all 0.2s ease;
    }
    input[name="canSanXuatCay"]:focus {
        background-color: #fbbf24; 
        border-color: #d97706; 
        box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.3);
        outline: none;
    }
    input[name="canSanXuatCay"]:hover {
        background-color: #fde68a;
        border-color: #f59e0b;
    }
    input[name="canSanXuatCay"]::placeholder {
        color: #92400e;
        font-style: italic;
    }
    input[name="canSanXuatCay"]:disabled {
        background-color: #f3f4f6;
        border-color: #d1d5db;
        color: #9ca3af;
        cursor: not-allowed;
    }
    input[name="canSanXuatCay"].changed {
        animation: highlight 0.5s ease-in-out;
    }
    @keyframes highlight {
        0% { background-color: #fef3c7; }
        50% { background-color: #fcd34d; }
        100% { background-color: #fef3c7; }
    }
    .auto-note-btn {
        position: relative;
        overflow: hidden;
    }
    .auto-note-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }
    .auto-note-btn:hover::before {
        width: 300px;
        height: 300px;
    }
    .action-btn {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        border-radius: 0.375rem;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .action-btn:active {
        transform: translateY(0);
    }
    .action-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    .change-indicator {
        animation: pulse 1.5s ease-in-out infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1;
    }
    tbody tr:hover {
        background-color: #f9fafb;
    }
    input:focus, select:focus, textarea:focus {
        transition: all 0.2s;
    }
    @media print {
        @page { 
            size: A4; 
            margin: 1cm; 
        }
        body { 
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important; 
            background-color: white; 
        }
        .no-print { 
            display: none !important; 
        }
        .container { 
            box-shadow: none !important; 
            border: none !important; 
            padding: 0 !important; 
            margin: 0 !important; 
            width: 100% !important; 
            max-width: 100% !important; 
        }
        .info-table input, .table-input { 
            border: none !important; 
            background-color: transparent !important; 
        }
        .bg-gray-200 { background-color: #e5e7eb !important; }
        .bg-green-100 { background-color: #dcfce7 !important; }
        .bg-red-100 { background-color: #fee2e2 !important; }
        .bg-blue-100 { background-color: #dbeafe !important; }
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        h3 { page-break-after: avoid; }
    }
    @media screen and (max-width: 768px) {
        th, td {
            font-size: 9px;
            padding: 4px 2px;
        }
        .action-btn {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
        }
    }
    </style>
</head>
<body class="p-4">
    <div id="app" class="container mx-auto bg-white p-6 rounded-lg shadow-lg">
        <div class="mb-4 no-print flex justify-between items-center">
            <div class="flex items-center gap-4">
                 <button onclick="goBackAndReload()" class="flex items-center justify-center w-10 h-10 text-gray-600 bg-gray-200 rounded-full hover:bg-gray-300 transition-colors shadow-sm">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <h2 id="main-form-title" class="text-2xl font-bold text-gray-900">PHIẾU CHUẨN BỊ HÀNG</h2>
            </div>
            <div class="flex justify-end space-x-2 items-center">
                 <button id="print-btn" type="button" class="px-6 py-3 bg-green-600 text-white rounded-md shadow-md hover:bg-green-700 transition-colors flex items-center">
                    <i class="fas fa-print mr-2"></i>In Phiếu
                </button>
                 <button id="save-chuanbi-btn" type="button" class="px-6 py-3 bg-blue-600 text-white rounded-md shadow-md hover:bg-blue-700 transition-colors flex items-center">
                    <i class="fas fa-save mr-2"></i>Lưu Phiếu
                </button>
            </div>
        </div>
       <div id="slip-info-header" class="no-print grid grid-cols-1 md:grid-cols-3 gap-6 mb-4 border-t border-b py-4">
            <div class="md:col-span-1 space-y-2 text-sm text-gray-700">
                <div id="slip-status-container"></div>
                <div id="pur-status-container"></div>
                <div id="ula-status-container"></div>
                <div id="dai-treo-status-container"></div>
                <div id="ecu-status-container"></div>
            </div>
            <div class="md:col-span-1 space-y-2 text-sm text-gray-700 border-l pl-4">
                <h4 class="font-bold text-gray-800 -ml-4 mb-2">Quy trình Kho & Sản Xuất</h4>
                <div id="nhap-btp-status-container"></div>
                <div id="xuat-btp-status-container"></div>
                <div id="nhap-tp-pur-status-container"></div>
                <div id="nhap-tp-ula-status-container"></div>
            </div>
            <div id="additional-action-buttons" class="md:col-span-1 flex flex-col items-start justify-center border-l pl-4">
            </div>
        </div>
        <header class="border-b-2 border-black pb-4">
            <div class="flex justify-between items-start">
                <div class="w-40"><img src="logo.png" alt="Logo 3i-Fix" class="w-full h-auto"></div>
                <div class="text-right text-xs">
                    <p class="font-bold text-sm">CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG VẬT LIỆU 3i</p>
                    <p>Office: Số 14 Lô D31 – BT2 Tại Khu D, Khu Đô Thị Mới Hai Bên Đường L,</p>
                    <p>Phường Dương Nội, TP Hà Nội, Việt Nam</p>
                    <p class="mt-2">Hotline: 0973098338</p>
                    <p>MST: 0110886479</p>
                </div>
            </div>
            <h2 class="text-center text-2xl font-bold mt-4">YCSX - PHIẾU CHUẨN BỊ HÀNG</h2>
        </header>
        <main class="mt-4 space-y-6">
            <div id="gia-cong-section">
            <table class="w-full info-table">
                <tr>
                    <td class="label w-1/6">Bộ phận</td>
                    <td class="w-2/6"><input type="text" id="info-bophan" placeholder="Kho - Logistic"></td>
                    <td class="label w-1/6">Ngày gửi YCSX</td>
                    <td class="w-2/6"><input type="date" id="info-ngaygui"></td>
                </tr>
                 <tr>
                    <td class="label">Người phụ trách</td>
                    <td><input type="text" id="info-phutrach" placeholder="Tên người phụ trách"></td>
                    <td class="label">Ngày giao hàng</td>
                    <td><input type="date" id="info-ngaygiao"></td>
                </tr>
                 <tr>
                    <td class="label">Người nhận hàng</td>
                    <td><input type="text" id="info-nguoinhan" placeholder="Tên người nhận"></td>
                    <td class="label">SĐT Người nhận</td>
                    <td><input type="text" id="info-sdtnguoinhan" placeholder="Số điện thoại"></td>
                </tr>
                <tr>
                    <td class="label">Địa điểm giao hàng</td>
                    <td colspan="3"><input type="text" id="info-diadiem" placeholder="Địa chỉ chi tiết"></td>
                </tr>
                <tr>
                    <td class="label">Số đơn YCSX</td>
                    <td><input type="text" id="info-sodon" placeholder="Số đơn"></td>
                    <td class="label">Đăng ký công trường</td>
                    <td><input type="text" id="info-congtrinh" placeholder="Tên công trình"></td>
                </tr>
                 <tr>
                    <td class="label">Mã đơn</td>
                    <td><input type="text" id="info-madon" readonly class="bg-gray-100"></td>
                    <td class="label">Quy cách thùng</td>
                    <td><input type="text" id="info-quycachthung" placeholder="Quy cách đóng gói"></td>
                </tr>
                <tr>
                    <td class="label">Xe Grap</td>
                    <td><input type="text" id="info-xegrap" placeholder="Loại xe Grap"></td>
                     <td class="label">Số tài xế</td>
                    <td><input type="text" id="info-solaixe" placeholder="SĐT tài xế"></td>
                </tr>
                <tr>
                     <td class="label">Xe tải</td>
                    <td><input type="text" id="info-xetai" placeholder="Loại xe tải"></td>
                     <td class="label">Ngày tạo phiếu</td>
                    <td><span id="slip-created-at" class="px-2 font-medium"></span></td>
                </tr>
                <tr>
                    <td class="label">Ghi chú ĐH</td>
                    <td colspan="3"><input type="text" id="info-ghichudonhang" placeholder="Ghi chú từ kế hoạch giao hàng" readonly class="bg-gray-100"></td>
                </tr>
                 <tr>
                    <td class="label">Cập nhật lần cuối</td>
                    <td colspan="3"><span id="slip-updated-at" class="px-2 font-medium"></span></td>
                </tr>
            </table>
            <div id="pur-section" style="display:none;">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 flex items-center">
                    <i class="fas fa-box-open text-blue-600 mr-2"></i>
                    Sản phẩm PUR (Gối đỡ)
                </h3>
                <table id="pur-table" class="w-full border-collapse">
                    <thead class="bg-gray-200 text-xs uppercase text-center">
                        <tr>
                            <th class="p-2 border align-middle" rowspan="2">STT</th>
                            <th class="p-2 border align-middle" rowspan="2">Mã Hàng</th>
                            <th class="p-2 border align-middle" rowspan="2">ID</th>
                            <th class="p-2 border align-middle" rowspan="2">Dày</th>
                            <th class="p-2 border align-middle" rowspan="2">Rộng</th>
                            <th class="p-2 border align-middle" rowspan="2">SL Yêu Cầu</th>
                            <th class="p-2 border align-middle" rowspan="2">Tồn Kho (Bộ)</th>
                            <th class="p-2 border align-middle" rowspan="2">Đã Gán (Bộ)</th>
                            <th class="p-2 border text-red-600 align-middle" rowspan="2">Cần SX (Bộ)</th>
                            <th class="p-2 border text-blue-600 align-middle" rowspan="2">Số Cây Cắt</th>
                            <th class="p-2 border" colspan="2">Tồn Kho / Đã Gán / Khả Dụng (Cây)</th>
                            <th class="p-2 border text-red-600 align-middle" rowspan="2">Cần SX (Cây)</th>
                            <th class="p-2 border align-middle" rowspan="2">Số Thùng</th>
                            <th class="p-2 border align-middle" rowspan="2">Ghi Chú</th>
                        </tr>
                        <tr>
                            <th class="p-2 border font-semibold" title="Tồn kho / Đã Gán / Khả dụng Cây Vuông">CV</th>
                            <th class="p-2 border font-semibold" title="Tồn kho / Đã Gán / Khả dụng Cây Tròn">CT</th>
                        </tr>
                    </thead>
                    <tbody id="pur-table-body"></tbody>
                </table>
            </div>
            <div id="ula-section" style="display:none;">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 flex items-center">
                    <i class="fas fa-cubes text-green-600 mr-2"></i>
                    Hàng Chuẩn Bị (Cùm ULA)
                </h3>
                <table id="ula-table" class="w-full border-collapse">
                    <thead class="table-subheader">
                        <tr><th colspan="12">HÀNG CHUẨN BỊ (CÙM ULA)</th></tr>
                        <tr>
                            <th rowspan="2">Stt.</th>
                            <th rowspan="2">Mã hàng</th>
                            <th colspan="3">Kích thước (mm)</th>
                            <th rowspan="2">SL YC (Bộ)</th>
                            <th rowspan="2">Tồn kho (Bộ)</th>
                            <th rowspan="2">Đã Gán (Bộ)</th>
                            <th rowspan="2" class="text-red-600">Cần đặt thêm (Bộ)</th>
                            <th rowspan="2">Đóng tải</th>
                            <th rowspan="2">Ghi chú</th>
                            <th rowspan="2">Thao tác</th>
                        </tr>
                        <tr><th>ID</th><th>Đ.Dày</th><th>B.Rộng</th></tr>
                    </thead>
                    <tbody id="ula-table-body"></tbody>
                </table>
            </div>
            <div id="deo-treo-section" style="display:none;">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 flex items-center">
                    <i class="fas fa-layer-group text-purple-600 mr-2"></i>
                    Hàng Chuẩn Bị (Đai Treo)
                </h3>
                <table id="deo-treo-table" class="w-full border-collapse">
                    <thead class="table-subheader">
                        <tr><th colspan="11">HÀNG CHUẨN BỊ (ĐAI TREO)</th></tr>
                        <tr>
                            <th rowspan="2">Stt.</th>
                            <th rowspan="2">Mã hàng</th>
                            <th colspan="3">Kích thước (mm)</th>
                            <th rowspan="2">SL YC (Bộ)</th>
                            <th rowspan="2">Tồn kho (Bộ)</th>
                            <th rowspan="2">Đã Gán (Bộ)</th>
                            <th rowspan="2" class="text-red-600">Cần đặt thêm (Bộ)</th>
                            <th rowspan="2">Đóng tải</th>
                            <th rowspan="2">Ghi chú</th>
                        </tr>
                        <tr><th>ID</th><th>Đ.Dày</th><th>B.Rộng</th></tr>
                    </thead>
                    <tbody id="deo-treo-table-body"></tbody>
                </table>
            </div>
            <div id="ecu-section" style="display:none;">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 flex items-center">
                    <i class="fas fa-tools text-orange-600 mr-2"></i>
                    Vật tư kèm theo (ECU)
                </h3>
                <table id="ecu-table" class="w-full border-collapse">
                    <thead class="table-subheader">
                        <tr><th colspan="10">Vật tư kèm theo (ECU cho cùm ULA)</th></tr>
                        <tr>
                            <th>Stt</th>
                            <th class="w-1/4">Tên vật tư</th>
                            <th>Số Lượng (cái)</th>
                            <th class="text-blue-600">Lấy Từ Kho (P. Này)</th>
                            <th>Tồn Kho</th>
                            <th>Gán</th>
                            <th class="text-red-600">Cần Mua Thêm</th>
                            <th>Số Kg</th>
                            <th>Đóng tải</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody id="ecu-table-body"></tbody>
                </table>
            </div>
            <div id="btp-section" style="display:none;">
                <h3 class="text-lg font-semibold text-gray-800 mt-4 mb-2 flex items-center">
                    <i class="fas fa-industry text-red-600 mr-2"></i>
                    Tóm Tắt Nhu Cầu Bán Thành Phẩm (BTP)
                </h3>
                <table id="btp-table" class="w-full border-collapse">
                    <thead class="table-subheader">
                        <tr>
                            <th>Stt.</th>
                            <th>Mã BTP</th>
                            <th>Số Cây Cắt YC</th>
                            <th class="text-red-600">Cần Sản Xuất (Cây)</th>
                            <th>Tồn Kho Vật Lý</th>
                            <th>Ghi Chú Tồn Kho Khả Dụng</th>
                        </tr>
                    </thead>
                    <tbody id="btp-table-body"></tbody>
                </table>
            </div>
            <div id="ula-lsx-container"></div>
            <div id="btp-lsx-container"></div>
        </main>
        <footer class="mt-12 pt-6 border-t text-center text-xs">
             <div class="flex justify-around items-start">
                <div class="w-1/4 px-2">
                    <p class="font-bold">Quản lý đơn</p>
                    <p class="mt-1 text-gray-500">(Ký, ghi rõ họ tên)</p>
                    <div class="h-16"></div>
                </div>
                <div class="w-1/4 px-2">
                    <p class="font-bold">Thủ kho</p>
                    <p class="mt-1 text-gray-500">(Ký, ghi rõ họ tên)</p>
                    <div class="h-16"></div>
                </div>
                <div class="w-1/4 px-2">
                    <p class="font-bold">Kế toán</p>
                    <p class="mt-1 text-gray-500">(Ký, ghi rõ họ tên)</p>
                    <div class="h-16"></div>
                </div>
                <div class="w-1/4 px-2">
                    <p class="font-bold">Giám đốc</p>
                    <p class="mt-1 text-gray-500">(Ký, ghi rõ họ tên)</p>
                    <div class="h-16"></div>
                </div>
            </div>
        </footer>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
   
</body>
</html>
