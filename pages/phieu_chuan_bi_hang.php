<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu Chuẩn Bị Hàng</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

    body {
        font-family: 'Roboto', sans-serif;
        background-color: #f0f2f5;
    }

    .printable-area {
        width: 210mm;
        min-height: 297mm;
        padding: 15mm;
        margin: 20px auto;
        background: white;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }

    th,
    td {
        border: 1px solid #666;
        padding: 6px 8px;
        text-align: left;
        vertical-align: top;
    }

    th {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: center;
    }

    .no-border {
        border: none;
    }

    .text-center {
        text-align: center;
    }

    .text-right {
        text-align: right;
    }

    .font-bold {
        font-weight: 700;
    }

    .text-lg {
        font-size: 1.125rem;
    }

    .text-xl {
        font-size: 1.25rem;
    }

    .mt-4 {
        margin-top: 1rem;
    }

    .mb-4 {
        margin-bottom: 1rem;
    }

    .w-12 {
        width: 3rem;
    }

    .header-info-table td {
        border: none;
        padding: 2px 4px;
    }

    .group-title {
        background-color: #e0e0e0;
        font-weight: bold;
        padding: 8px;
        margin-top: 1.5rem;
        font-size: 14px;
    }

    .signatures {
        margin-top: 40px;
        display: flex;
        justify-content: space-around;
        text-align: center;
    }

    .signatures div {
        font-weight: bold;
    }

    @media print {
        body {
            background-color: #fff;
        }

        .printable-area {
            margin: 0;
            padding: 10mm 5mm;
            box-shadow: none;
            width: 100%;
            min-height: 0;
        }

        .no-print {
            display: none;
        }
    }
    </style>
</head>

<body>

    <div class="no-print p-4 bg-gray-800 text-white flex justify-center items-center gap-4">
        <h1 class="text-xl">Xem Trước Phiếu Chuẩn Bị Hàng</h1>
        <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-print mr-2"></i>In Phiếu
        </button>
    </div>

    <div id="phieu-container" class="printable-area">
        <!-- Header -->
        <table class="w-full mb-4">
            <tr>
                <td class="no-border w-1/4">
                    <!-- Có thể thêm logo ở đây -->
                    <img src="https://placehold.co/150x60?text=3i-FIX" alt="Logo" style="width: 120px;">
                </td>
                <td class="no-border w-3/4 text-center">
                    <h1 class="font-bold text-xl uppercase">CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG VẬT LIỆU XANH 31</h1>
                    <p class="text-xs">Office: Số 14 Lô D31 - BT12 tại khu D, KĐT Mới Hai Bên Đường Lê Trọng Tấn, Phường
                        Dương Nội, Quận Hà Đông, TP. Hà Nội</p>
                    <p class="text-xs">Hotline: 0973098338 - MST: 0110886479</p>
                </td>
            </tr>
        </table>

        <h2 class="font-bold text-lg text-center uppercase my-4">YCSX - Phiếu Chuẩn Bị Hàng</h2>

        <!-- Order Info -->
        <table class="header-info-table mb-4">
            <tr>
                <td class="font-bold">Bộ phận:</td>
                <td>Kho - Logistic</td>
                <td class="font-bold">Ngày gửi YCSX:</td>
                <td id="info-ngay-gui"></td>
            </tr>
            <tr>
                <td class="font-bold">Phụ trách:</td>
                <td id="info-phu-trach"></td>
                <td class="font-bold">Ngày giao:</td>
                <td id="info-ngay-giao"></td>
            </tr>
            <tr>
                <td class="font-bold">Số đơn:</td>
                <td id="info-so-don" class="font-bold"></td>
                <td class="font-bold">Người nhận hàng:</td>
                <td id="info-nguoi-nhan"></td>
            </tr>
            <tr>
                <td class="font-bold">Mã đơn:</td>
                <td></td>
                <td class="font-bold">Địa điểm giao hàng:</td>
                <td id="info-dia-diem"></td>
            </tr>
            <tr>
                <td class="font-bold">Quy cách thùng:</td>
                <td>Thùng có logo</td>
                <td class="font-bold">Loại xe:</td>
                <td id="info-loai-xe"></td>
            </tr>
        </table>

        <!-- Product Tables Container -->
        <div id="product-tables-container">
            <!-- JS sẽ điền các bảng sản phẩm vào đây -->
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div>Quản lý đơn</div>
            <div>Thủ kho</div>
            <div>Kế toán</div>
            <div>Giám đốc</div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        initializePhieuChuanBiHangPage();
    });

    function initializePhieuChuanBiHangPage() {
        const params = new URLSearchParams(window.location.search);
        const donhangId = params.get('id');
        if (!donhangId) {
            $('#phieu-container').html('<p class="text-red-500 text-center">Không tìm thấy ID đơn hàng.</p>');
            return;
        }

        $.ajax({
            url: `../api/get_donhang_details.php?id=${donhangId}`, // Chú ý đường dẫn có thể cần thay đổi
            dataType: 'json',
            success: function(res) {
                if (res.success && res.donhang) {
                    renderPhieu(res.donhang);
                } else {
                    $('#phieu-container').html(
                        `<p class="text-red-500 text-center">Lỗi: ${res.message || 'Không thể tải dữ liệu đơn hàng.'}</p>`
                    );
                }
            },
            error: function() {
                $('#phieu-container').html('<p class="text-red-500 text-center">Lỗi kết nối server.</p>');
            }
        });
    }

    function renderPhieu(donhang) {
        const {
            info,
            items
        } = donhang;

        // Điền thông tin chung
        document.title = `PCB Hàng - ${info.SoYCSX}`;
        $('#info-so-don').text(info.SoYCSX || 'N/A');
        $('#info-phu-trach').text(info.NguoiBaoGia || 'N/A');
        $('#info-ngay-gui').text(info.NgayTao ? new Date(info.NgayTao).toLocaleDateString('vi-VN') : 'N/A');
        $('#info-ngay-giao').text(info.NgayGiaoDuKien ? new Date(info.NgayGiaoDuKien).toLocaleDateString('vi-VN') :
            'N/A');
        $('#info-nguoi-nhan').text(info.TenCongTy || 'N/A'); // Giả định người nhận là tên công ty
        $('#info-dia-diem').text(info.TenDuAn || 'N/A'); // Giả định địa điểm là tên dự án

        // Nhóm sản phẩm
        const groupedItems = items.reduce((acc, item) => {
            const groupName = item.TenNhom || 'Sản phẩm khác';
            if (!acc[groupName]) {
                acc[groupName] = [];
            }
            acc[groupName].push(item);
            return acc;
        }, {});

        const tablesContainer = $('#product-tables-container').empty();

        // Tạo bảng cho từng nhóm
        for (const groupName of Object.keys(groupedItems).sort()) {
            const groupItems = groupedItems[groupName];

            const tableHeader = `
                    <thead>
                        <tr>
                            <th>Stt.</th>
                            <th>Mã hàng</th>
                            <th>ID</th>
                            <th colspan="2">Kích thước (mm)</th>
                            <th>Số lượng (bộ)</th>
                            <th>Đóng thùng</th>
                            <th>Tồn kho (bộ)</th>
                            <th>Cây cắt</th>
                            <th>Ghi chú</th>
                        </tr>
                        <tr>
                            <th colspan="3"></th>
                            <th>Bản rộng</th>
                            <th>Độ dày</th>
                            <th colspan="5"></th>
                        </tr>
                    </thead>`;

            let tableBody = '<tbody>';
            groupItems.forEach((item, index) => {
                tableBody += `
                        <tr>
                            <td class="text-center">${index + 1}</td>
                            <td>${item.MaHang || ''}</td>
                            <td class="text-center">${item.ID_ThongSo || ''}</td>
                            <td class="text-center">${item.BanRongItem || ''}</td>
                            <td class="text-center">${item.DoDayItem || ''}</td>
                            <td class="text-center font-bold">${item.SoLuong || 0}</td>
                            <td></td> <!-- Cột Đóng thùng để trống -->
                            <td class="text-center">${item.TonKhoVatLy || 0}</td>
                            <td class="text-center">${(typeof item.SoCayPhaiCat === 'number') ? item.SoCayPhaiCat : ''}</td>
                            <td>${item.GhiChu || ''}</td>
                        </tr>
                    `;
            });
            tableBody += '</tbody>';

            const groupHtml = `
                    <div class="group-title">${groupName}</div>
                    <table>
                        ${tableHeader}
                        ${tableBody}
                    </table>
                `;
            tablesContainer.append(groupHtml);
        }
    }
    </script>
</body>

</html>