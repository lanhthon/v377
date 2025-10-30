<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Báo Giá</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Arial&display=swap" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
    .container {
        max-width: 100%;
        margin: 1rem auto;
        background: white;
        padding: 0;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        border-radius: 0.375rem;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 20px;
        border-bottom: 1px solid #ccc;
    }

    .header-left h1 {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 5px;
        text-align: center;
    }

    .header-left h2 {
        font-size: 14px;
        font-style: italic;
        margin-bottom: 5px;
        text-align: center;
    }

    .header-left p {
        font-size: 11px;
        margin: 5px 0;
        display: flex;
        align-items: center;
    }

    .header-left p strong {
        width: 40px;
    }

    .header-left p input {
        flex-grow: 1;
        border: none;
        border-bottom: 1px dotted #999;
        padding: 2px 4px;
        font-weight: bold;
    }

    .header-right {
        text-align: right;
    }

    .logo {
        width: 150px;
        height: auto;
        margin-right: 100px;
        margin-bottom: 10px;
        object-fit: cover;
    }

    .info-section {
        display: flex;
        padding: 20px;
        border-bottom: 1px solid #ccc;
        gap: 20px;
    }

    .info-left {
        flex: 1;
        background: #DBE5F1;
        padding: 15px;
        border-radius: 5px;
    }

    .info-right {
        width: 35%;
        background: #DBE5F1;
        padding: 15px;
        border-radius: 5px;
        display: flex;
        flex-direction: column;
    }

    .info-right h3 {
        text-align: center;
        background: none;
        color: #92D050;
        padding: 0;
        margin: 0 0 10px 0;
        border-radius: 0;
        font-size: 14px;
        font-weight: bold;
    }

    .info-right-body {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        gap: 10px;
    }

    .info-details-col {
        flex: 1;
    }

    .qr-code-col {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .qr-code {
        width: 60px;
        height: 60px;
        border-radius: 5px;
        object-fit: cover;
    }

    .info-row {
        display: flex;
        align-items: flex-start;
        margin: 5px 0;
        font-size: 11px;
    }

    .info-row .info-label {
        width: 80px;
        font-weight: bold;
        flex-shrink: 0;
        padding-top: 2px;
    }

    .info-row .info-value {
        flex: 1;
    }

    .info-row input[type="text"] {
        width: 100%;
        border: none;
        background: transparent;
        border-bottom: 1px dotted #999;
        padding: 2px 4px;
    }

    .info-right .info-label {
        width: 65px;
    }

    .products-section {
        padding: 10px;
    }

    .product-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
        font-size: 10px;
    }

    .product-table th {
        background: #92D050;
        color: white;
        padding: 8px 4px;
        text-align: center;
        font-weight: bold;
        border: 1px solid #5A8230;
        font-size: 9px;
        vertical-align: middle;
    }

    .product-table td {
        padding: 2px;
        text-align: center;
        border: 1px solid #92D050;
        background: #DBE5F1;
        vertical-align: middle;
    }

    .product-table input {
        width: 100%;
        border: none;
        background: transparent;
        padding: 4px;
        text-align: center;
    }

    .product-table input.text-right {
        text-align: right;
    }

    .product-table .product-code {
        text-align: left;
    }

    .shipping-fee-row td {
        background-color: #E9F4DE;
        font-weight: bold;
    }
    
    .group-header td {
        background-color: #E9F4DE !important;
        color: #5A8230;
        font-weight: bold;
        text-align: left;
        padding: 6px 10px !important;
    }

    .group-header div[contenteditable="true"] {
        outline: none;
        min-width: 100px;
        display: inline-block;
    }
    
    /* --- BẮT ĐẦU CSS MỚI CHO KHU VỰC --- */
    .area-block {
        border: 2px solid #FFD966;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
        background-color: #fff;
    }
    .area-header td, .area-footer td {
        background-color: #FFF2CC !important; /* Màu vàng nhạt */
        color: #855C00;
        font-weight: bold;
        border-left: 1px solid #FFD966;
        border-right: 1px solid #FFD966;
    }
    .area-header td {
        padding: 6px 10px !important;
        text-align: left;
    }
    .area-block .area-block-header {
        outline: none;
        min-width: 150px;
        display: inline-block;
    }
    .area-footer .area-subtotal-label {
        text-align: right;
        padding-right: 10px;
    }
    .area-footer .area-subtotal-value {
        text-align: right;
        padding-right: 4px;
    }
    /* --- KẾT THÚC CSS MỚI CHO KHU VỰC --- */

    .footer-section {
        display: flex;
        padding: 10px;
        gap: 20px;
    }

    .footer-left,
    .footer-right {
        flex: 1;
    }
    
    .product-image {
        width: 50px;
        height: 50px;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
        font-size: 11px;
        object-fit: cover;
    }

    .notes {
        font-size: 10px;
        line-height: 1.4;
        background: #F3F9EC;
        padding: 15px;
        border-radius: 5px;
    }

    .notes p {
        margin: 5px 0;
        display: flex;
    }

    .notes strong {
        width: 110px;
        flex-shrink: 0;
    }

    .notes input {
        flex-grow: 1;
        border: none;
        background: transparent;
        border-bottom: 1px dotted #999;
    }

    .totals-container {
        width: 100%;
        margin-bottom: 20px;
    }

    .total-line {
        display: flex;
        justify-content: flex-start;
        gap: 10px;
        padding: 10px 0;
        font-size: 11px;
        align-items: center;
    }

    .total-line .label {
        font-weight: bold;
        width: 140px;
        text-align: right;
    }

    .total-line .value {
        text-align: left;
        min-width: 100px;
        font-weight: bold;
        flex-grow: 1;
    }

    .total-line .value#amount-in-words {
        font-style: italic;
        font-weight: normal;
        text-align: left;
    }
    
    .total-line .label input[type="number"] {
        width: 50px;
        text-align: right;
        font-weight: bold;
        background: transparent;
        border: none;
        border-bottom: 1px dotted #999;
        padding: 2px;
        margin: 0 4px;
        -moz-appearance: textfield;
    }
    .total-line .label input[type="number"]::-webkit-outer-spin-button,
    .total-line .label input[type="number"]::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }

    .totals-container hr {
        border: 0;
        border-top: 1px solid #ccc;
        margin: 5px 0;
    }

    .company-info-footer {
        background: #F3F9EC;
        padding: 15px;
        border-radius: 5px;
        font-size: 10px;
        line-height: 1.4;
    }

    .company-info-footer h4 {
        color: #92D050;
        margin-bottom: 5px;
        font-size: 11px;
    }

    .signatures {
        display: flex;
        justify-content: space-around;
        text-align: center;
        padding: 20px 20px 30px 20px;
    }

    .signature-box {
        padding: 15px;
        border: 1px dashed #ccc;
        width: 150px;
        height: 80px;
        font-size: 10px;
        font-weight: bold;
    }

    .suggestion-box {
        position: absolute;
        z-index: 10;
        background: white;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        max-height: 250px;
        overflow-y: auto;
    }

    .suggestion-box ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .suggestion-box li {
        padding: 8px 12px;
        cursor: pointer;
    }

    .suggestion-box li:hover {
        background-color: #f0f0f0;
    }

    .suggestion-box li .f-key-hint {
        font-size: 0.8rem;
        font-weight: bold;
        color: #fff;
        background-color: #2563eb;
        border-radius: 5px;
        padding: 3px 8px;
        margin-left: 12px;
        box-shadow: 1px 1px 4px rgba(0, 0, 0, 0.4);
    }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background-color: white;
            padding: 0;
        }

        .container {
            margin: 0;
            box-shadow: none;
            border-radius: 0;
        }
    }
    
    /* --- BẮT ĐẦU MÃ MỚI --- */
    
    /* Container cho ảnh sản phẩm và ảnh QR */
    .image-container, .qr-container {
        position: relative; /* Rất quan trọng để định vị lớp phủ bên trong */
        cursor: pointer;
        overflow: hidden; /* Đảm bảo lớp phủ không bị tràn ra ngoài */
    }
    
    /* Di chuyển một số thuộc tính từ .product-image sang .image-container */
    .image-container {
        width: 120px; /* <-- THAY ĐỔI */
        height: 120px; /* <-- THAY ĐỔI */
        border: 2px solid #ccc;
        border-radius: 10px;
        margin-bottom: 10px;
        background: #f5f5f5;
    }
    
    /* Đảm bảo ảnh chiếm toàn bộ container */
    .product-image, .qr-code {
        display: block;
        width: 100%;
        height: 100%;
    }
    
    /* Gán kích thước cho qr-container */
    .qr-container {
        width: 60px;
        height: 60px;
        border-radius: 5px;
    }
    
    /* Lớp phủ mờ (overlay) */
    .overlay {
        position: absolute; /* Định vị tuyệt đối so với container */
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5); /* Nền đen mờ 50% */
        color: white;
        
        /* Căn giữa nội dung bên trong lớp phủ */
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    
        font-weight: bold;
        text-align: center;
    
        opacity: 0; /* Mặc định sẽ ẩn đi */
        transition: opacity 0.3s ease; /* Hiệu ứng mờ dần khi xuất hiện */
    }
    
    /* CSS riêng cho từng lớp phủ nếu cần */
    .image-container .overlay {
        font-size: 14px;
    }
    
    .qr-container .overlay {
        font-size: 11px;
    }
    
    
    /* Hiển thị overlay khi di chuột vào container */
    .image-container:hover .overlay,
    .qr-container:hover .overlay {
        opacity: 1; /* Hiện lớp phủ */
    }
    /* --- BẮT ĐẦU MÃ CSS CHO DROPDOWN --- */

.dropdown-menu {
    position: absolute;
    right: 0;
    top: 100%; /* Vị trí ngay bên dưới nút cha */
    margin-top: 0.5rem; /* 8px */
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 0.375rem; /* 6px */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 100;
    min-width: 180px; /* Độ rộng tối thiểu của menu */
    padding: 0.5rem 0; /* 8px */
}

.dropdown-item {
    display: block;
    padding: 0.5rem 1rem; /* 8px 16px */
    font-size: 14px;
    color: #333;
    text-decoration: none;
    white-space: nowrap; /* Ngăn không cho chữ xuống dòng */
}

.dropdown-item:hover {
    background-color: #f5f5f5;
}

/* --- KẾT THÚC MÃ CSS CHO DROPDOWN --- */
    
    /* --- KẾT THÚC MÃ MỚI --- */
    </style>
</head>

<body>
    <input type="hidden" id="quote-id-input" value="">
    <input type="hidden" id="company-id-input" value="">
    <input type="hidden" id="contact-id-input" value="">
    <input type="hidden" id="project-id-input" value="">

    <div
        class="no-print bg-[#92D050] text-white py-2 px-3 shadow-md sticky top-0 z-50 flex justify-between items-center mb-4">
        <h2 id="page-title" class="text-xl font-bold text-white">Tạo Báo Giá Mới</h2>
        <div class="flex items-center gap-3">
            <div>
                <label for="quote-status-select" class="font-bold text-sm">Trạng thái:</label>
                <select id="quote-status-select"
                    class="bg-white border border-gray-300 rounded-md py-1 px-2 text-sm font-bold text-gray-800">
                    <option value="Mới tạo">Mới tạo</option>
                    <option value="Đấu thầu">Đấu thầu</option>
                    <option value="Đàm phán">Đàm phán</option>
                    <option value="Chốt">Chốt</option>
                    <option value="Tạch">Tạch</option>
                </select>
            </div>
            <div>
                <label for="price-schema" class="font-bold text-sm">Cơ chế giá:</label>
                <select id="price-schema"
                    class="bg-white border border-gray-300 rounded-md py-1 px-2 text-sm font-bold text-gray-800">
                    <option value="">Đang tải...</option>
                </select>
            </div>
            <div>
                <label for="price-adjustment-percentage-input" class="font-bold text-sm">P.trăm điều chỉnh (%):</label>
                <input type="number" id="price-adjustment-percentage-input" value="0" min="-100" step="0.1"
                    class="bg-white border border-gray-300 rounded-md py-1 px-2 w-20 text-sm font-bold text-gray-800">
            </div>
           <div class="flex items-center space-x-2">
    <button id="save-quote-btn"
        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded-md shadow-md transition duration-300 ease-in-out transform hover:scale-105 text-sm">
        <i class="fas fa-save mr-1"></i>Lưu
    </button>

    <div class="relative dropdown-container">
        <button
            class="export-btn bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded-md shadow-md transition duration-300 ease-in-out transform hover:scale-105 text-sm flex items-center">
            <i class="fas fa-file-pdf mr-1"></i>Xuất PDF <i class="fas fa-caret-down ml-2"></i>
        </button>
        <div class="dropdown-menu hidden">
            <a href="#" id="export-pdf-bg-btn" class="dropdown-item">Báo giá Tiếng Việt</a>
            <a href="#" id="export-pdf-TQ-btn" class="dropdown-item">Báo giá Việt-Trung</a>
        </div>
    </div>

    <div class="relative dropdown-container">
        <button
            class="export-btn bg-green-700 hover:bg-green-800 text-white font-bold py-1 px-3 rounded-md shadow-md transition duration-300 ease-in-out transform hover:scale-105 text-sm flex items-center">
            <i class="fas fa-file-excel mr-1"></i>Xuất Excel <i class="fas fa-caret-down ml-2"></i>
        </button>
        <div class="dropdown-menu hidden">
            <a href="#" id="export-excel-btn" class="dropdown-item">Báo giá Tiếng Việt</a>
            <a href="#" id="export-excel-TQ-btn" class="dropdown-item">Báo giá Việt-Trung</a>
        </div>
    </div>
</div>
        </div>
    </div>

    <div class="container" id="printable-quote-area">
        <div class="header">
            <div class="header-left">
                <h1>QUOTATION</h1>
                <h2>BÁO GIÁ</h2>
                <h2>KIÊM XÁC NHẬN ĐẶT HÀNG</h2>
                <p><strong>Số:</strong> <input size="50%" type="text" id="quote-number-input"
                        placeholder="3iG/P...(Tự động)"></p>
                <p><strong>Ngày:</strong> <input type="text" id="quote_date" placeholder="dd/mm/yyyy"></p>
            </div>
            <div class="header-right">
                <img src="logo.png" alt="Logo Công ty" class="logo">
            </div>
        </div>

        <!-- Info section -->
        <div class="info-section">
            <div class="info-left">
                <!-- Khách hàng -->
                <div class="info-row">
                    <span class="info-label">Gửi tới:</span>
                    <div class="info-value" style="display: flex; align-items: center; gap: 10px;">
                        <input type="text" id="customer-name-input" 
                            placeholder="Gõ để tìm công ty...(vui lòng chọn khách hàng có trong danh sách nếu chưa có nhấn dấu + bên phải để thêm)" 
                            style="flex: 1;">
                        <i class="fas fa-plus-circle text-[#5A8230] cursor-pointer" id="add_KH" title="Thêm khách hàng mới"></i>
                    </div>
                </div>

                <!-- Địa chỉ khách hàng -->
                <div class="info-row">
                    <span class="info-label">Địa chỉ:</span>
                    <div class="info-value">
                        <input type="text" id="customer-address-input" placeholder="Địa chỉ công ty">
                    </div>
                </div>

                <!-- Người nhận và Điện thoại -->
                <div class="info-row">
                    <span class="info-label">Người nhận:</span>
                    <div class="info-value" style="display: flex; gap: 10px;">
                        <input type="text" id="recipient-name-input" placeholder="Tên người nhận" style="flex: 1;">
                        <span class="info-label" style="width: auto;">Di động:</span>
                        <input type="text" id="recipient-phone-input" placeholder="SĐT người nhận" style="flex: 1;">
                    </div>
                </div>

                <!-- Hạng mục -->
                <div class="info-row">
                    <span class="info-label">Hạng mục:</span>
                    <div class="info-value">
                        <input type="text" id="category-input" value="Gối đỡ PU & Cùm Ula 3i-Fix">
                    </div>
                </div>

                <!-- Dự án -->
                <div class="info-row">
                    <span class="info-label">Dự án:</span>
                    <div class="info-value" style="display: flex; align-items: center; gap: 10px;">
                        <input type="text" id="project-name-input" 
                            placeholder="Gõ để tìm dự án... (có thể bỏ trống)" 
                            style="flex: 1;">
                        <i class="fas fa-plus-circle text-[#5A8230] cursor-pointer" id="add_DA" title="Thêm dự án mới"></i>
                    </div>
                </div>

                <!-- Địa chỉ dự án (readonly - tự động điền) -->
                <div class="info-row">
                    <span class="info-label">Địa chỉ DA:</span>
                    <div class="info-value">
                        <input type="text" id="project-address-input" 
                            placeholder="Địa chỉ dự án (tự động điền khi chọn)" 
                            readonly 
                            style="background-color: #f9fafb; cursor: not-allowed; color: #6b7280;">
                    </div>
                </div>
            </div>

            <div class="info-right">
                <h3>3iGREEN</h3>
                <div class="info-right-body">
                    <div class="info-details-col">
                        <div class="info-row">
                            <span class="info-label">Ng.báo giá:</span>
                            <div class="info-value"><input type="text" id="quote-person-input"></div>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Chức vụ:</span>
                            <div class="info-value"><input type="text" id="position-input"></div>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Di động:</span>
                            <div class="info-value"><input type="text" id="mobile-input"></div>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Hiệu lực:</span>
                            <div class="info-value"><input type="text" id="quote-validity-input"
                                    value="20 ngày kể từ ngày báo giá"></div>
                        </div>
                    </div>
                    <div class="qr-code-col">
                        <input type="file" id="qr-upload-input" class="hidden" accept="image/*">
                        <input type="hidden" id="qr-path" value="">
                        <div class="qr-container" onclick="document.getElementById('qr-upload-input').click();">
                            <img id="qr-preview" src="uploads/qr.png" alt="QR Code" class="qr-code">
                            <div class="overlay">
                                <i class="fas fa-sync-alt" style="margin-bottom: 4px;"></i>
                                <span>Đổi QR</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="products-section">
            <!-- Container cho các khu vực sẽ được thêm vào đây bằng JavaScript -->
            <div id="areas-container"></div>

            <!-- Container cho các sản phẩm không thuộc khu vực nào -->
            <div id="default-products-container">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 3%;">Stt.</th>
                            <th rowspan="2" style="width: 15%;">Mã hàng</th>
                            <th colspan="3">Kích thước PUR (mm)</th>
                            <th rowspan="2" style="width: 5%;">Đơn vị</th>
                            <th rowspan="2" style="width: 7%;">Số lượng</th>
                            <th style="width: 10%;">Đơn giá</th>
                            <th style="width: 12%;">Thành tiền</th>
                            <th rowspan="2">Ghi chú</th>
                            <th rowspan="2" class="no-print" style="width: 3%;"></th>
                        </tr>
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 10%;">(T) <br>Độ dày</th>
                            <th style="width: 10%;">(L)<br> Bản rộng</th>
                            <th>VNĐ</th>
                            <th>VNĐ</th>
                        </tr>
                    </thead>
                    <tbody class="pur-items-bom"></tbody>
                </table>
    
                <table class="product-table mt-4">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 3%;">Stt.</th>
                            <th rowspan="2" style="width: 15%;">Mã hàng</th>
                            <th colspan="3">Kích thước ULA (mm)</th>
                            <th rowspan="2" style="width: 5%;">Đơn vị</th>
                            <th rowspan="2" style="width: 7%;">Số lượng</th>
                             <th style="width: 10%;">Đơn giá</th>
                            <th style="width: 12%;">Thành tiền</th>
                            <th rowspan="2">Ghi chú</th>
                            <th rowspan="2" class="no-print" style="width: 3%;"></th>
                        </tr>
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 10%;">(t) <br>Độ dày</th>
                            <th style="width: 10%;">(w) <br>Bản rộng</th>
                            <th>VNĐ</th>
                            <th>VNĐ</th>
                        </tr>
                    </thead>
                    <tbody class="ula-items-bom"></tbody>
                </table>
            </div>

            <table class="product-table mt-4">
                <tbody id="shipping-fee-bom">
                </tbody>
            </table>

            <div class="mt-2 no-print flex items-center space-x-2">
                <button class="px-3 py-1 text-xs bg-[#5A8230] text-white rounded-md hover:bg-[#4C6B28] shadow-sm"
                    id="add-pur-row-btn"><i class="fas fa-plus-circle mr-2"></i>Thêm dòng PUR (chung)</button>
                <button class="px-3 py-1 text-xs bg-[#5A8230] text-white rounded-md hover:bg-[#4C6B28] shadow-sm"
                    id="add-ula-row-btn"><i class="fas fa-plus-circle mr-2"></i>Thêm dòng ULA (chung)</button>
                <div class="h-5 border-l border-gray-400"></div>
                <button class="px-3 py-1 text-xs bg-yellow-500 text-yellow-900 rounded-md hover:bg-yellow-600 shadow-sm"
                    id="add-area-btn"><i class="fas fa-map-marker-alt mr-2"></i>Thêm Khu vực</button>
            </div>
        </div>

        <div class="footer-section">
            <div class="footer-left">
                <input type="file" id="image-upload-input" class="hidden" accept="image/*">
                <input type="hidden" id="image-path" value="">
                <div class="image-container" onclick="document.getElementById('image-upload-input').click();">
                    <img id="image-preview" src="uploads/default_image.png" alt="Hình minh họa sản phẩm"
                          class="product-image">
                    <div class="overlay">
                        <i class="fas fa-camera" style="font-size: 24px; margin-bottom: 8px;"></i>
                        <span>Nhấn để đổi ảnh</span>
                    </div>
                </div>
                <div class="notes">
                    <p><strong>Xuất xứ:</strong> <input type="text" id="origin" value="3iGreen"></p>
                    <p><strong>- T.gian giao hàng:</strong> <input type="text" id="delivery-conditions-input"
                            value="3-5 ngày sau khi nhận được xác nhận đặt hàng."></p>
                    <p><strong>- Điều kiện thanh toán:</strong> <input type="text" id="payment-terms-input"
                            value="Theo thỏa thuận"></p>
                    <p><strong>- Địa điểm giao hàng:</strong> <input type="text" id="delivery-location-input"
                            placeholder="Địa chỉ giao hàng"></p>
                </div>
            </div>
            <div class="footer-right">
                <div class="totals-container">
                    <div class="total-line">
                        <span class="label">Tổng cộng trước thuế:</span>
                        <span class="value" id="subtotal">0</span>
                    </div>
                    <div class="total-line">
                        <span class="label">VAT (<input type="number" id="tax-percentage-input" value="8" min="0" step="0.1" >%):</span>
                        <span class="value" id="vat">0</span>
                    </div>
                    <hr>
                    <div class="total-line">
                        <span class="label">Tổng tiền sau thuế:</span>
                        <span class="value" id="total">0</span>
                    </div>
                    <div class="total-line">
                        <span class="label">Bằng chữ:</span>
                        <span class="value" id="amount-in-words">-</span>
                    </div>
                </div>
                <div class="company-info-footer">
                    <h4>CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG VẬT LIỆU XANH 3I</h4>
                    <p>Địa chỉ: Số 14 Lô D31 – BT2 Tại Khu D, Khu Đô Thị Mới Hai Bên Đường Lê Trọng Tấn, Phường Dương Nội, TP Hà Nội, Việt Nam</p>
                    <p><strong>MST:</strong> 0110886479</p>
                    <p><strong>Thông tin chuyển khoản:</strong></p>
                    <p>Chủ tài khoản: Công ty TNHH sản xuất và ứng dụng vật liệu xanh 3i</p>
                    <p>Số tài khoản: 46668888, Ngân hàng TMCP Hàng Hải Việt Nam (MSB) - chi nhánh Thanh Xuân</p>
                </div>
            </div>
        </div>

        <div class="signatures">
            <div class="signature-box">Đại diện mua hàng</div>
            <div class="signature-box">Đại diện bán hàng</div>
        </div>
    </div>

    <div id="product-suggestion-box" class="suggestion-box hidden"></div>
    <div id="customer-suggestion-box" class="suggestion-box hidden"></div>
    <div id="project-suggestion-box" class="suggestion-box hidden"></div>
 <div id="product-category-filter-bar" class="no-print fixed bottom-4 right-4 bg-green-700 text-white p-3 rounded-lg shadow-lg z-50 flex items-center gap-3">
                <label for="product-category-filter-select" class="text-sm font-bold flex-shrink-0">Chọn nhóm trước khi nhập mã tìm kiếm:</label>
                <select id="product-category-filter-select" class="bg-white border border-gray-300 rounded-md py-1 px-2 text-sm font-bold text-gray-800 w-auto" style="max-width: 300px;">
                </select>
            </div>
</body>

</html>

