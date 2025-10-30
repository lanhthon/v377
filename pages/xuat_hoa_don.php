<?php
// pages/xuat_hoa_don.php
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa Đơn Bán Hàng</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #e2e8f0; /* bg-gray-200 */
        }
        .invoice-container {
            max-width: 8.27in; /* A4 width */
            min-height: 11.69in; /* A4 height */
            margin: 20px auto;
            background-color: white;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 0.5in;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px; /* Adjusted for better fit */
            color: #000;
        }
        .font-bold { font-weight: bold; }
        .italic { font-style: italic; }
        hr.dashed {
            border: none;
            border-top: 1px dashed #000;
        }
        .logo {
            max-width: 200px; /* Increased size */
            max-height: 90px;
        }
        .invoice-table th, .invoice-table td {
             border: 1px solid #000;
             padding: 4px 6px; /* Adjusted padding */
             vertical-align: top;
        }
        .invoice-table-header {
            font-weight: bold;
            text-align: center;
        }

        @media print {
            body {
                background-color: white;
                margin: 0;
                font-size: 11.5px; /* Fine-tune for printing */
            }
            .no-print {
                display: none;
            }
            .invoice-container {
                box-shadow: none;
                margin: 0;
                padding: 0.4in;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="fixed top-4 right-4 no-print flex space-x-2">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-lg">
            <i class="fas fa-print mr-2"></i> In Hóa Đơn
        </button>
        <button onclick="window.close()" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded shadow-lg">
            <i class="fas fa-times mr-2"></i> Đóng
        </button>
    </div>

    <div id="invoice-wrapper">
         <div class="invoice-container flex items-center justify-center">
            <div>
                <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
                <p class="mt-4 text-lg">Đang tải dữ liệu hóa đơn...</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Hàm định dạng số
        function formatNumber(num) {
            if (isNaN(num) || num === null || num === undefined) return '0';
            return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
        }
        
        // Hàm đọc số thành chữ
        function numberToWords(number) {
            if (number === null || !isFinite(number) || number == 0) return "Không đồng";
            number = Math.round(number);
            let mangso = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
            function dochangchuc(so, daydu) {
                let chuoi = ""; let chuc = Math.floor(so / 10); let donvi = so % 10;
                if (chuc > 1) { chuoi = " " + mangso[chuc] + " mươi"; if (donvi == 1) { chuoi += " mốt"; } } 
                else if (chuc == 1) { chuoi = " mười"; if (donvi == 1) { chuoi += " một"; } } 
                else if (daydu && donvi > 0) { chuoi = " lẻ"; }
                if (donvi == 5 && chuc > 1) { chuoi += " lăm"; } 
                else if (donvi > 1 || (donvi == 1 && chuc == 0)) { chuoi += " " + mangso[donvi]; }
                return chuoi;
            }
            function docblock(so, daydu) {
                let chuoi = ""; let tram = Math.floor(so / 100); so = so % 100;
                if (daydu || tram > 0) { chuoi = " " + mangso[tram] + " trăm"; chuoi += dochangchuc(so, true); } 
                else { chuoi = dochangchuc(so, false); }
                return chuoi;
            }
            function dochangtrieu(so, daydu) {
                let chuoi = ""; let trieu = Math.floor(so / 1000000); so = so % 1000000;
                if (trieu > 0) { chuoi = docblock(trieu, daydu) + " triệu"; daydu = true; }
                let nghin = Math.floor(so / 1000); so = so % 1000;
                if (nghin > 0) { chuoi += docblock(nghin, daydu) + " nghìn"; daydu = true; }
                if (so > 0) { chuoi += docblock(so, daydu); }
                return chuoi;
            }
            let chuoi = ""; let hauto = "";
            do {
                let ty = number % 1000000000; number = Math.floor(number / 1000000000);
                if (number > 0) { chuoi = dochangtrieu(ty, true) + hauto + chuoi; } 
                else { chuoi = dochangtrieu(ty, false) + hauto + chuoi; }
                hauto = " tỷ";
            } while (number > 0);
            chuoi = chuoi.trim();
            return chuoi.charAt(0).toUpperCase() + chuoi.slice(1) + " đồng";
        }

        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            const ycsxId = urlParams.get('ycsx_id');

            if (!ycsxId) {
                $('#invoice-wrapper').html('<div class="invoice-container"><p class="text-red-500 text-center">Lỗi: Thiếu mã đơn hàng.</p></div>');
                return;
            }

            $.ajax({
                url: `api/get_invoice_data.php?ycsx_id=${ycsxId}`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderInvoice(response.data);
                    } else {
                        $('#invoice-wrapper').html(`<div class="invoice-container"><p class="text-red-500 text-center">Lỗi: ${response.message}</p></div>`);
                    }
                },
                error: function(xhr) {
                    $('#invoice-wrapper').html(`<div class="invoice-container"><p class="text-red-500 text-center">Lỗi hệ thống: Không thể tải dữ liệu. ${xhr.responseText}</p></div>`);
                }
            });
        });

        function renderInvoice(data) {
            const header = data.header;
            const items = data.items;
            const company = data.company_info;
            const logoSrc = 'logo.png'; // Path to your logo

            const ngay = new Date(header.NgayXuat).getDate();
            const thang = new Date(header.NgayXuat).getMonth() + 1;
            const nam = new Date(header.NgayXuat).getFullYear();
            
            let itemsHtml = '';
            // Loop through actual items
            items.forEach((item, i) => {
                 itemsHtml += `
                    <tr>
                        <td class="text-center">${i + 1}</td>
                        <td>${item.TenSanPham || ''}</td>
                        <td class="text-center">${item.DonViTinh || ''}</td>
                        <td class="text-center">${formatNumber(item.SoLuong)}</td>
                        <td class="text-right">${formatNumber(item.DonGia)}</td>
                        <td class="text-right">${formatNumber(item.ThanhTien)}</td>
                    </tr>
                `;
            });

            // If there are no items, display a message
            if (items.length === 0) {
                itemsHtml = `<tr><td colspan="6" class="text-center italic py-4">Không có sản phẩm nào.</td></tr>`;
            }
            
            const totalInWords = numberToWords(header.TongTienSauThue);
            
            const invoiceHtml = `
            <div class="invoice-container">
                <!-- Header -->
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 30%; text-align: left;">
                            <img src="${logoSrc}" alt="Logo" class="logo">
                        </td>
                        <td style="width: 70%; text-align: center;">
                            <p class="font-bold" style="font-size: 20px;">HÓA ĐƠN BÁN HÀNG</p>
                            <p class="italic">Ngày ${String(ngay).padStart(2, '0')} tháng ${String(thang).padStart(2, '0')} năm ${nam}</p>
                            <p>Số: <span style="color: red; font-weight: bold;">${header.SoHoaDon}</span></p>
                        </td>
                    </tr>
                </table>
                
                <hr style="border: 1px solid #000; margin: 15px 0;">

                <!-- Info -->
                <div style="padding: 5px 0;">
                    <p><span class="font-bold">Đơn vị bán hàng:</span> ${company.ten_cong_ty}</p>
                    <p><span class="font-bold">Mã số thuế:</span> ${company.ma_so_thue}</p>
                    <p><span class="font-bold">Địa chỉ:</span> ${company.dia_chi}</p>
                    <p><span class="font-bold">Điện thoại:</span> ${company.so_dien_thoai} &nbsp;&nbsp;&nbsp; <span class="font-bold">Email:</span> ${company.email || ''}</p>
                    <hr class="dashed" style="margin: 8px 0;">
                    <p><span class="font-bold">Họ và tên người mua hàng:</span></p>
                    <p><span class="font-bold">Tên đơn vị:</span> ${header.TenCongTy}</p>
                    <p><span class="font-bold">Mã số thuế:</span> ${header.MaSoThue}</p>
                    <p><span class="font-bold">Địa chỉ:</span> ${header.DiaChi}</p>
                    <p><span class="font-bold">Hình thức thanh toán:</span> Chuyển khoản &nbsp;&nbsp;&nbsp; <span class="font-bold">Số tài khoản:</span> ${company.so_tai_khoan} tại ${company.ngan_hang}</p>
                </div>
                
                <!-- Items table -->
                <table class="invoice-table" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <thead>
                        <tr class="invoice-table-header" style="background-color: #f2f2f2;">
                            <th style="width: 5%;">STT</th>
                            <th style="width: 40%;">Tên hàng hóa, dịch vụ</th>
                            <th style="width: 8%;">ĐVT</th>
                            <th style="width: 10%;">Số lượng</th>
                            <th style="width: 15%;">Đơn giá</th>
                            <th style="width: 22%;">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                    <!-- Summary Row -->
                    <tr>
                        <td colspan="5" class="font-bold text-right">Cộng tiền hàng:</td>
                        <td class="font-bold text-right">${formatNumber(header.TongTienTruocThue)}</td>
                    </tr>
                </table>
                
                <!-- Totals -->
                 <table style="width: 100%; margin-top: 5px;">
                    <tr>
                         <td style="width: 60%; vertical-align: top; padding-left: 5px;">
                            <p><span class="font-bold">Tiền thuế GTGT (${header.ThueVAT_PhanTram}%):</span></p>
                            <p class="font-bold">Tổng cộng tiền thanh toán:</p>
                            <p><span class="font-bold">Số tiền viết bằng chữ:</span> <span class="italic">${totalInWords}.</span></p>
                        </td>
                        <td style="width: 40%; text-align: right; vertical-align: top;">
                             <p>${formatNumber(header.TienThueVAT)}</p>
                             <p class="font-bold">${formatNumber(header.TongTienSauThue)}</p>
                        </td>
                    </tr>
                </table>

                <!-- Signatures -->
                <table style="width: 100%; text-align: center; margin-top: 25px; page-break-inside: avoid;">
                    <tr>
                        <td style="width: 50%;">
                            <p class="font-bold">Người mua hàng</p>
                            <p class="italic">(Ký, ghi rõ họ tên)</p>
                        </td>
                        <td style="width: 50%;">
                            <p class="font-bold">Người bán hàng</p>
                             <p class="italic">(Ký, đóng dấu, ghi rõ họ tên)</p>
                        </td>
                    </tr>
                     <tr><td style="height: 60px;"></td><td style="height: 60px;"></td></tr>
                </table>
                
            </div>`;

            $('#invoice-wrapper').html(invoiceHtml);
        }
    </script>
</body>
</html>

