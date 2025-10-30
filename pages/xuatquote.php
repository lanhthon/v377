<?php
// Giả định bạn có một file config để kết nối CSDL
// include 'config/database.php'; 
// Nếu chưa có, bạn cần tự thêm code kết nối CSDL ở đây.
// Ví dụ kết nối mysqli:
$servername = "localhost";
$username = "root"; // Thay bằng username của bạn
$password = ""; // Thay bằng password của bạn
$dbname = "ten_csdl_cua_ban"; // Thay bằng tên CSDL của bạn

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);
mysqli_set_charset($conn, 'UTF8');

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}


// --- Hàm đọc số thành chữ (lấy từ logic JS của bạn) ---
function docSoThanhChu($number) {
    // Đây là một hàm ví dụ, bạn nên tìm một thư viện PHP đầy đủ hơn
    // hoặc tự phát triển hàm này dựa trên hàm docSo() trong file JS của bạn.
    // Ví dụ đơn giản:
    $f = new NumberFormatter("vi", NumberFormatter::SPELLOUT);
    return ucfirst($f->format($number)) . ' đồng';
}


// --- Lấy dữ liệu báo giá từ CSDL ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Không tìm thấy báo giá.');
}

$quoteId = intval($_GET['id']);
$quoteInfo = null;
$quoteItems = [];

// 1. Lấy thông tin chung của báo giá
// Tên bảng và cột được suy ra từ file JS và PHP của bạn (ví dụ: baogia, chitietbaogia)
// Bạn có thể cần điều chỉnh lại cho đúng với CSDL của mình.
$sqlInfo = "SELECT * FROM baogia WHERE BaoGiaID = ?";
$stmtInfo = $conn->prepare($sqlInfo);
$stmtInfo->bind_param("i", $quoteId);
$stmtInfo->execute();
$resultInfo = $stmtInfo->get_result();
if ($resultInfo->num_rows > 0) {
    $quoteInfo = $resultInfo->fetch_assoc();
} else {
    die('Không tìm thấy thông tin báo giá.');
}
$stmtInfo->close();


// 2. Lấy danh sách sản phẩm trong báo giá
$sqlItems = "SELECT ct.*, sp.MaHang, sp.ID_ThongSo, sp.DoDay, sp.BanRong 
             FROM chitietbaogia ct
             JOIN sanpham sp ON ct.SanPhamID = sp.SanPhamID
             WHERE ct.BaoGiaID = ? 
             ORDER BY ct.ThuTuHienThi ASC";

$stmtItems = $conn->prepare($sqlItems);
$stmtItems->bind_param("i", $quoteId);
$stmtItems->execute();
$resultItems = $stmtItems->get_result();
if ($resultItems->num_rows > 0) {
    while ($row = $resultItems->fetch_assoc()) {
        $quoteItems[] = $row;
    }
}
$stmtItems->close();
$conn->close();

// Tách sản phẩm PUR và ULA
$pur_items = [];
$ula_items = [];
foreach ($quoteItems as $item) {
    // Giả định mã hàng của PUR bắt đầu bằng "PUR", ULA bắt đầu bằng "ULA"
    // Bạn có thể thay đổi logic này nếu cần
    if (strpos(strtoupper($item['MaHang']), 'PUR') === 0) {
        $pur_items[] = $item;
    } else {
        $ula_items[] = $item;
    }
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo giá - <?php echo htmlspecialchars($quoteInfo['SoBaoGia']); ?></title>
    <style>
    /* CSS bạn đã cung cấp */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        background-color: white;
        padding: 0;
        font-size: 12px;
    }

    .container {
        max-width: 210mm;
        margin: 0 auto;
        background: white;
        padding: 0;
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
        margin: 2px 0;
    }

    .header-right {
        text-align: right;
    }

    .logo {
        width: 80px;
        height: 60px;
        border-radius: 10px;
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
        background: #E8F5E8;
        padding: 15px;
        border-radius: 5px;
    }

    .info-right {
        width: 250px;
        background: #E8F5E8;
        padding: 15px;
        border-radius: 5px;
        display: flex;
        flex-direction: column;
    }

    .info-right h3 {
        text-align: center;
        background: none;
        color: #4CAF50;
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
        background: #333;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 8px;
    }

    .info-row {
        display: flex;
        align-items: flex-start;
        margin: 3px 0;
        font-size: 11px;
    }

    .info-row span:last-child {
        flex: 1;
    }

    .info-label {
        width: 80px;
        font-weight: bold;
        flex-shrink: 0;
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
        margin-bottom: 10px;
        font-size: 10px;
    }

    .product-table th {
        background: #4CAF50;
        color: white;
        padding: 8px 4px;
        text-align: center;
        font-weight: bold;
        border: 1px solid #2E7D32;
        font-size: 9px;
        vertical-align: middle;
    }

    .product-table td {
        padding: 6px 4px;
        text-align: center;
        border: 1px solid #4CAF50;
        background: #F1F8E9;
    }

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
        width: 200px;
        height: 150px;
        border: 2px solid #ccc;
        border-radius: 10px;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
        font-size: 11px;
        margin-bottom: 10px;
    }

    .notes {
        font-size: 10px;
        line-height: 1.4;
        background: #E8F5E8;
        padding: 15px;
        border-radius: 5px;
    }

    .notes p {
        margin: 2px 0;
    }

    .totals-container {
        width: 100%;
        margin-bottom: 20px;
    }

    .total-line {
        display: flex;
        justify-content: space-between;
        padding: 3px 0;
        font-size: 11px;
    }

    .total-line .label {
        font-weight: bold;
    }

    .total-line .value {
        text-align: right;
        min-width: 100px;
    }

    .totals-container hr {
        border: 0;
        border-top: 1px solid #ccc;
        margin: 5px 0;
    }

    .company-info-footer {
        background: #E8F5E8;
        padding: 15px;
        border-radius: 5px;
        font-size: 10px;
        line-height: 1.4;
    }

    .company-info-footer h4 {
        color: #4CAF50;
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
    </style>
</head>

<body>
    <div class="container" id="printable-quote-area">
        <div class="header">
            <div class="header-left">
                <h1>QUOTATION</h1>
                <h2>BÁO GIÁ</h2>
                <h2>KIÊM XÁC NHẬN ĐẶT HÀNG</h2>
                <p><strong>Số:</strong> <span><?php echo htmlspecialchars($quoteInfo['SoBaoGia']); ?></span></p>
                <p><strong>Ngày:</strong> <span><?php echo date("d/m/Y", strtotime($quoteInfo['NgayBaoGia'])); ?></span>
                </p>
            </div>
            <div class="header-right">
                <img src="https://placehold.co/80x60/4CAF50/FFFFFF?text=Logo" alt="Logo Công ty" class="logo">
            </div>
        </div>

        <div class="info-section">
            <div class="info-left">
                <div class="info-row">
                    <span class="info-label">Gửi tới:</span>
                    <span><strong><?php echo htmlspecialchars($quoteInfo['TenCongTy']); ?></strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Địa chỉ:</span>
                    <span><?php echo htmlspecialchars($quoteInfo['DiaChiKhach']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Người nhận:</span>
                    <span style="margin-right: 30px;"><?php echo htmlspecialchars($quoteInfo['NguoiNhan']); ?></span>
                    <span class="info-label" style="width: auto;">Di động:</span>
                    <span><?php echo htmlspecialchars($quoteInfo['SoDiDongKhach']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Hạng mục:</span>
                    <span><?php echo htmlspecialchars($quoteInfo['HangMuc']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Dự án:</span>
                    <span><?php echo htmlspecialchars($quoteInfo['TenDuAn']); ?></span>
                </div>
            </div>
            <div class="info-right">
                <h3>3iGREEN</h3>
                <div class="info-right-body">
                    <div class="info-details-col">
                        <div class="info-row">
                            <span class="info-label">Ng.báo giá:</span>
                            <span><?php echo htmlspecialchars($quoteInfo['NguoiBaoGia']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Chức vụ:</span>
                            <span><?php echo htmlspecialchars($quoteInfo['ChucVuNguoiBaoGia']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Di động:</span>
                            <span><?php echo htmlspecialchars($quoteInfo['DiDongNguoiBaoGia']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Hiệu lực:</span>
                            <span><?php echo htmlspecialchars($quoteInfo['HieuLucBaoGia']); ?></span>
                        </div>
                    </div>
                    <div class="qr-code-col">
                        <img src="https://placehold.co/60x60/333333/FFFFFF?text=QR" alt="QR Code" class="qr-code">
                    </div>
                </div>
            </div>
        </div>

        <div class="products-section">
            <table class="product-table">
                <thead>
                    <tr>
                        <th rowspan="2">Stt.</th>
                        <th rowspan="2">Mã hàng</th>
                        <th colspan="3">Kích thước PUR (mm)</th>
                        <th rowspan="2">Đơn vị</th>
                        <th rowspan="2">Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                        <th rowspan="2">Ghi chú</th>
                    </tr>
                    <tr>
                        <th>ID</th>
                        <th>(t)<br>Độ dày</th>
                        <th>(w)<br>Bản rộng</th>
                        <th>VNĐ</th>
                        <th>VNĐ</th>
                    </tr>
                </thead>
                <tbody id="pur-items-bom">
                    <?php 
                        $stt_pur = 1;
                        foreach ($pur_items as $item): ?>
                    <tr>
                        <td><?php echo $stt_pur++; ?></td>
                        <td><?php echo htmlspecialchars($item['MaHang']); ?></td>
                        <td><?php echo htmlspecialchars($item['ID_ThongSo']); ?></td>
                        <td><?php echo htmlspecialchars($item['DoDay']); ?></td>
                        <td><?php echo htmlspecialchars($item['BanRong']); ?></td>
                        <td>Bộ</td>
                        <td><?php echo number_format($item['SoLuong']); ?></td>
                        <td><?php echo number_format($item['DonGia']); ?></td>
                        <td><?php echo number_format($item['ThanhTien']); ?></td>
                        <td><?php echo htmlspecialchars($item['GhiChu']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <table class="product-table">
                <thead>
                    <tr>
                        <th rowspan="2">Stt.</th>
                        <th rowspan="2">Mã hàng</th>
                        <th colspan="3">Kích thước ULA (mm)</th>
                        <th rowspan="2">Đơn vị</th>
                        <th rowspan="2">Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                        <th rowspan="2">Ghi chú</th>
                    </tr>
                    <tr>
                        <th>ID</th>
                        <th>(T)<br>Độ dày</th>
                        <th>(L)<br>Bản rộng</th>
                        <th>VNĐ</th>
                        <th>VNĐ</th>
                    </tr>
                </thead>
                <tbody id="ula-items-bom">
                    <?php 
                        $stt_ula = 1;
                        foreach ($ula_items as $item): ?>
                    <tr>
                        <td><?php echo $stt_ula++; ?></td>
                        <td><?php echo htmlspecialchars($item['MaHang']); ?></td>
                        <td><?php echo htmlspecialchars($item['ID_ThongSo']); ?></td>
                        <td><?php echo htmlspecialchars($item['DoDay']); ?></td>
                        <td><?php echo htmlspecialchars($item['BanRong']); ?></td>
                        <td>Bộ</td>
                        <td><?php echo number_format($item['SoLuong']); ?></td>
                        <td><?php echo number_format($item['DonGia']); ?></td>
                        <td><?php echo number_format($item['ThanhTien']); ?></td>
                        <td><?php echo htmlspecialchars($item['GhiChu']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="footer-section">
            <div class="footer-left">
                <img src="<?php echo htmlspecialchars($quoteInfo['HinhAnh1'] ?? 'https://placehold.co/200x150/f5f5f5/999?text=Ảnh+minh+họa'); ?>"
                    alt="Hình minh họa sản phẩm" class="product-image">
                <div class="notes">
                    <p><strong>Xuất xứ:</strong> <span>3iGreen</span></p>
                    <p><strong>- T.gian giao hàng:</strong>
                        <span><?php echo htmlspecialchars($quoteInfo['ThoiGianGiaoHang']); ?></span></p>
                    <p><strong>- Điều kiên thanh toán:</strong>
                        <span><?php echo htmlspecialchars($quoteInfo['DieuKienThanhToan']); ?></span></p>
                    <p><strong>- Địa điểm giao hàng:</strong>
                        <span><?php echo htmlspecialchars($quoteInfo['DiaChiGiaoHang']); ?></span></p>
                </div>
            </div>
            <div class="footer-right">
                <div class="totals-container">
                    <div class="total-line">
                        <span class="label">Tổng cộng trước thuế:</span>
                        <span class="value"><?php echo number_format($quoteInfo['TongTienTruocThue']); ?></span>
                    </div>
                    <div class="total-line">
                        <span class="label">VAT 10%:</span>
                        <span class="value"><?php echo number_format($quoteInfo['VAT']); ?></span>
                    </div>
                    <hr>
                    <div class="total-line">
                        <span class="label">Tổng tiền sau thuế:</span>
                        <span class="value"><?php echo number_format($quoteInfo['TongTienSauThue']); ?></span>
                    </div>
                    <div class="total-line">
                        <span class="label">Bằng chữ:</span>
                        <span class="value"><?php echo docSoThanhChu($quoteInfo['TongTienSauThue']); ?></span>
                    </div>
                </div>
                <div class="company-info-footer">
                    <h4>CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG VẬT LIỆU XANH 3I</h4>
                    <p>Địa chỉ: Số 14 Lô D31 - BT12 tại khu D, KĐT Mới Hai Bên Đường Lê Trọng Tấn, Phường Dương Nội,
                        Quận Hà Đông, TP. Hà Nội</p>
                    <p><strong>MST:</strong> 0110886479</p>
                    <p><strong>Thông tin chuyển khoản:</strong></p>
                    <p>Chủ tài khoản: Công ty TNHH sản xuất và ứng dụng vật liệu xanh 3i</p>
                    <p>Số tài khoản: 46668888, Ngân hàng TMCP Hàng Hải Việt Nam (MSB) - chi nhánh Thanh Xuân</p>
                </div>
            </div>
        </div>

        <div class="signatures">
            <div class="signature-box">
                Đại diện mua hàng
            </div>
            <div class="signature-box">
                Đại diện bán hàng
            </div>
        </div>
    </div>
</body>

</html>