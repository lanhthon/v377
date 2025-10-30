<?php
// pages/cccl_view.php (Đã cập nhật để cho phép chỉnh sửa và cải thiện CSS)
$cccl_id = $_GET['id'] ?? 0;
?>
<style>
    /* --- CSS CHO CÁC Ô INPUT CÓ THỂ CHỈNH SỬA CỦA CCCL --- */
    .editable-cccl-field {
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
    .editable-cccl-field:hover {
        border-color: #e2e8f0; /* gray-200 */
    }
    .editable-cccl-field:focus {
        background-color: #ffffff;
        border-color: #a0aec0; /* gray-400 */
        outline: none;
        box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.5);
        border-radius: 3px;
    }
    textarea.editable-cccl-field {
        resize: vertical;
    }
    input[type="date"].editable-cccl-field {
        width: auto;
        min-width: 120px;
    }

    /* --- CSS chống xuống dòng cho cột Mã hàng và Tên sản phẩm --- */
    #cccl-items-body td:nth-child(2),
    #cccl-items-body td:nth-child(3) {
        white-space: nowrap;
    }
    
    /* --- [SỬA LỖI] Thêm CSS để căn giữa input trong bảng --- */
    #cccl-items-body td .editable-cccl-field.text-center {
        text-align: center;
    }


    /* --- CSS CHO IN ẤN --- */
    @media print {
        @page { size: auto; margin: 0.5cm; }
        .no-print { display: none !important; }
        body, .p-6 { padding: 0 !important; margin: 0 !important; }
        #printable-area { box-shadow: none !important; margin: 0 !important; max-width: 100% !important; border: none !important; }
        thead tr, .bg-gray-100 { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .bg-gray-100 { background-color: #f3f4f6 !important; }
        thead[class*="font-bold"] { background-color: #92D050 !important; }
        
        /* Ẩn viền và nền của input khi in, logic JS sẽ thay bằng span */
        .editable-cccl-field,
        #cccl-items-body input.editable-cccl-field {
            border: none !important;
            background-color: transparent !important;
            box-shadow: none !important;
            padding: 2px 4px !important;
            color: #000 !important;
            border-radius: 0 !important; /* [SỬA LỖI] Bỏ bo góc khi in */
        }
    }
</style>
<div class="p-6 bg-gray-50 min-h-full print:bg-white">
    <div class="flex justify-between items-center mb-6 no-print">
        <h1 id="page-title" class="text-3xl font-bold text-gray-800">Chi Tiết Chứng Chỉ Chất Lượng</h1>
        <div class="flex gap-x-3">
           <button id="print-cccl-btn" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                <i class="fas fa-print mr-2"></i>In
            </button>
            <button id="save-cccl-btn" class="bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700">
                <i class="fas fa-save mr-2"></i>Lưu thay đổi
            </button>
            <button id="export-excel-btn-cccl" data-cccl-id="<?php echo $cccl_id; ?>" class="bg-green-700 text-white px-4 py-2 rounded-md hover:bg-green-800">
                <i class="fas fa-file-excel mr-2"></i>Xuất Excel
            </button>
            <button id="export-pdf-btn-cccl" data-cccl-id="<?php echo $cccl_id; ?>" class="bg-red-700 text-white px-4 py-2 rounded-md hover:bg-red-800">
                <i class="fas fa-file-pdf mr-2"></i>Xuất PDF
            </button>
            <button id="back-to-issued-list-btn" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>Quay Lại
            </button>
        </div>
    </div>

    <div id="printable-area" class="bg-white p-8 shadow-lg print:shadow-none max-w-4xl mx-auto font-sans text-sm border border-gray-300">
        <header class="flex justify-between items-stretch gap-x-4">
            <!-- Khối bên trái -->
            <div class="w-1/2 p-4 bg-gray-100 flex flex-col gap-y-2">
                <div>
                    <strong>Số:</strong> <span id="info-socccl" class="font-semibold">...</span>
                </div>
                <div>
                    <i><strong>Ngày cấp:</strong> <input type="date" id="cccl-ngay-cap" class="editable-cccl-field p-1"></i>
                </div>
                
                <div class="mt-4 flex flex-col gap-y-2">
                    <p class="font-bold">KHÁCH HÀNG:</p>
                    <input type="text" id="cccl-tencongty-khach" class="editable-cccl-field font-semibold w-full p-1" placeholder="Tên công ty khách hàng">
                    
                    <div>
                        <strong>Địa chỉ khách hàng:</strong>
                        <textarea id="cccl-diachi-khach" rows="2" class="editable-cccl-field w-full p-1" placeholder="Địa chỉ khách hàng"></textarea>
                    </div>
                     <div>
                        <strong>Tên dự án:</strong>
                        <input type="text" id="cccl-duan" class="editable-cccl-field w-full p-1" placeholder="Tên dự án">
                    </div>
                    <div>
                        <strong>Địa chỉ dự án:</strong>
                        <textarea id="cccl-diachi-duan" rows="2" class="editable-cccl-field w-full p-1" placeholder="Địa chỉ dự án"></textarea>
                    </div>
                    <div>
                        <strong>Tên sản phẩm:</strong>
                        <input type="text" id="cccl-sanpham" class="editable-cccl-field font-semibold w-full p-1" placeholder="Tên sản phẩm chung">
                    </div>
                    <p><strong>Số YCSX gốc:</strong> <span id="cccl-soycsx-goc" class="font-semibold">...</span></p>
                </div>
            </div>

            <!-- Khối bên phải -->
            <div class="w-1/2 p-4 bg-gray-100">
                <div class="text-center">
                    <h2 class="text-xl font-bold uppercase text-green-800">CHỨNG NHẬN XUẤT XƯỞNG</h2>
                    <h3 class="text-lg font-bold uppercase text-green-800">CHẤT LƯỢNG</h3>
                    <img src="logo.png" alt="Logo 3i-Fix" class="mx-auto my-4" style="max-height: 120px;">
                </div>
                <div class="mt-4 text-center">
                    <p class="font-bold">NHÀ SẢN XUẤT:</p>
                    <p class="font-bold">CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG VẬT LIỆU XANH 3I</p>
                    <p class="text-xs">Địa chỉ: Số 14 Lô D31 – BT2 Tại Khu D, Khu Đô Thị Mới Hai Bên Đường Lê Trọng Tấn, Phường Dương Nội, TP Hà Nội, Việt Nam</p>
                </div>
            </div>
        </header>
        
        <div class="mt-4">
            <table class="min-w-full text-sm border-collapse border border-black">
                <thead class="font-bold text-center">
                    <tr class="border border-black">
                        <th class="p-2 border border-black w-[5%]">Stt.</th>
                        <th class="p-2 border border-black w-[15%]">Mã hàng</th>
                        <th class="p-2 border border-black w-[35%]">Tên sản phẩm</th>
                        <th class="p-2 border border-black w-[8%]">ĐVT</th>
                        <th class="p-2 border border-black w-[10%]">Số lượng</th>
                        <th class="p-2 border border-black w-[12%]">Tiêu chuẩn</th>
                        <th class="p-2 border border-black">Ghi chú</th>
                    </tr>
                </thead>
                <tbody id="cccl-items-body">
                    <tr><td colspan="7" class="text-center p-4">Đang tải...</td></tr>
                </tbody>
            </table>
        </div>

        <footer class="mt-16">
            <div class="flex justify-end">
                <div class="text-center font-semibold text-sm w-2/5">
                    <p class="uppercase">TP. QUẢN LÝ CHẤT LƯỢNG</p>
                    <p class="italic text-xs">(Ký, họ tên)</p>
                    <div class="h-24"></div>
                    <input type="text" id="cccl-footer-nguoikiemtra" class="editable-cccl-field w-full text-center font-semibold p-1" placeholder="Tên người kiểm tra">
                </div>
            </div>
        </footer>
    </div>
</div>

