<?php
// File: api/debug_production_check.php
// Công cụ giúp kiểm tra tại sao một báo giá không tạo ra lệnh sản xuất.
header('Content-Type: text/html; charset=utf-8');
echo "<style>body { font-family: monospace; line-height: 1.6; } .ok { color: green; } .fail { color: red; } .info { color: blue; }</style>";

require_once '../config/database.php';

$baoGiaID = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($baoGiaID <= 0) {
    die("Vui lòng cung cấp ID báo giá trên URL, ví dụ: ?id=91");
}

echo "<h1>Bắt đầu kiểm tra Báo giá ID: $baoGiaID</h1>";

// Lấy chi tiết báo giá
$sql_items = "SELECT ctb.*, sp.LoaiID FROM chitietbaogia ctb JOIN sanpham sp ON ctb.SanPhamID = sp.SanPhamID WHERE ctb.BaoGiaID = ? ORDER BY ThuTuHienThi";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $baoGiaID);
$stmt_items->execute();
$items_result = $stmt_items->get_result();

if ($items_result->num_rows === 0) {
    die("Không tìm thấy sản phẩm nào trong báo giá này.");
}

// Chuẩn bị các câu lệnh
$stmt_inv = $conn->prepare("SELECT TonKhoBo, TonKhoCay FROM sanpham_tonkho WHERE SanPhamID = ?");
$stmt_dmc = $conn->prepare("SELECT SoBoTrenCay FROM dinh_muc_cat dmc JOIN sanpham sp ON sp.BanRong = dmc.BanRong AND CAST(sp.ID_ThongSo AS UNSIGNED) BETWEEN dmc.MinDN AND dmc.MaxDN WHERE sp.SanPhamID = ?");

while ($item = $items_result->fetch_assoc()) {
    $sanPhamID = (int)$item['SanPhamID'];
    $soLuongYeuCau = (int)$item['SoLuong'];

    echo "<hr><h2>Sản phẩm: {$item['MaHang']} ({$item['TenSanPham']})</h2>";
    echo "<ul>";

    // Lấy tồn kho
    $stmt_inv->bind_param("i", $sanPhamID);
    $stmt_inv->execute();
    $inv = $stmt_inv->get_result()->fetch_assoc();
    $tonKhoBo = $inv['TonKhoBo'] ?? 0;
    $tonKhoCay = $inv['TonKhoCay'] ?? 0;

    echo "<li>Số lượng yêu cầu: $soLuongYeuCau</li>";
    echo "<li>Tồn kho (bộ): $tonKhoBo</li>";

    $boConThieu = $soLuongYeuCau - $tonKhoBo;

    if ($boConThieu <= 0) {
        echo "<li class='ok'>Lý do: Tồn kho thành phẩm (bộ) đã đủ.</li>";
        echo "</ul>";
        continue;
    }
    echo "<li>=> <span class='fail'>Số bộ còn thiếu: $boConThieu</span></li>";

    if ($item['LoaiID'] == 2) {
        echo "<li class='ok'>Lý do: Đây là hàng mua ngoài (Cùm Ula), không sản xuất.</li>";
        echo "</ul>";
        continue;
    }

    // Lấy định mức cắt
    $stmt_dmc->bind_param("i", $sanPhamID);
    $stmt_dmc->execute();
    $dmc = $stmt_dmc->get_result()->fetch_assoc();
    $soBoTrenCay = $dmc['SoBoTrenCay'] ?? 0;
    
    echo "<li>Định mức cắt: $soBoTrenCay (bộ/cây)</li>";

    if ($soBoTrenCay <= 0) {
        echo "<li class='fail'>Lý do: Không tìm thấy định mức cắt cho sản phẩm này trong CSDL. Vui lòng kiểm tra bảng 'dinh_muc_cat'.</li>";
        echo "</ul>";
        continue;
    }

    $cayTuongDuong = ceil($boConThieu / $soBoTrenCay);
    echo "<li>Số cây tương đương cần để sản xuất: ceil($boConThieu / $soBoTrenCay) = <span class='info'>$cayTuongDuong</span></li>";
    echo "<li>Tồn kho (cây): $tonKhoCay</li>";

    $cayCanSanXuat = $cayTuongDuong - $tonKhoCay;

    if ($cayCanSanXuat <= 0) {
        echo "<li class='ok'>Lý do: Tồn kho bán thành phẩm (cây) đã đủ để sản xuất số bộ còn thiếu.</li>";
        echo "</ul>";
        continue;
    }
    
    echo "<li>=> <span class='fail'>Số cây cần sản xuất mới: $cayCanSanXuat</span></li>";
    echo "<li class='ok'><b>KẾT LUẬN: SẢN PHẨM NÀY SẼ TẠO LỆNH SẢN XUẤT.</b></li>";

    echo "</ul>";
}

$stmt_items->close();
$stmt_inv->close();
$stmt_dmc->close();
$conn->close();

?>